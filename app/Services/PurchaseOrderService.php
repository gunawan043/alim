<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public const TRANSITIONS = [
        'po' => [
            PurchaseOrder::STATUS_DRAFT => [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_CANCELLED,
            ],
            PurchaseOrder::STATUS_SENT => [
                PurchaseOrder::STATUS_ACCEPTED,
                PurchaseOrder::STATUS_REJECTED,
                PurchaseOrder::STATUS_CANCELLED,
            ],
            PurchaseOrder::STATUS_ACCEPTED => [
                PurchaseOrder::STATUS_IN_PRODUCTION,
                PurchaseOrder::STATUS_READY_TO_SHIP,
            ],
            PurchaseOrder::STATUS_IN_PRODUCTION => [
                PurchaseOrder::STATUS_READY_TO_SHIP,
            ],
            PurchaseOrder::STATUS_READY_TO_SHIP => [
                PurchaseOrder::STATUS_SHIPPED,
            ],
            PurchaseOrder::STATUS_SHIPPED => [
                PurchaseOrder::STATUS_IN_TRANSIT,
                PurchaseOrder::STATUS_DELIVERED,
            ],
            PurchaseOrder::STATUS_IN_TRANSIT => [
                PurchaseOrder::STATUS_DELIVERED,
            ],
            PurchaseOrder::STATUS_DELIVERED => [
                PurchaseOrder::STATUS_QC_IN_PROGRESS,
            ],
            PurchaseOrder::STATUS_QC_IN_PROGRESS => [
                PurchaseOrder::STATUS_QC_PASSED,
                PurchaseOrder::STATUS_QC_FAILED,
            ],
            PurchaseOrder::STATUS_QC_PASSED => [
                PurchaseOrder::STATUS_INVOICED,
                PurchaseOrder::STATUS_CLOSED,
            ],
            PurchaseOrder::STATUS_QC_FAILED => [
                PurchaseOrder::STATUS_CLOSED,
            ],
            PurchaseOrder::STATUS_INVOICED => [
                PurchaseOrder::STATUS_PAID,
                PurchaseOrder::STATUS_CLOSED,
            ],
            PurchaseOrder::STATUS_PAID => [
                PurchaseOrder::STATUS_CLOSED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(
        int $userId,
        Vendor $vendor,
        ?Quotation $quotation = null,
        array $data = [],
        array $items = []
    ): PurchaseOrder {
        return DB::transaction(function () use ($userId, $vendor, $quotation, $data, $items) {
            $po = PurchaseOrder::create([
                'po_number' => (new PurchaseOrder())->generateNumber(),
                'vendor_id' => $vendor->id,
                'rfq_id' => $quotation?->rfq_id,
                'quotation_id' => $quotation?->id,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'shipping_notes' => $data['shipping_notes'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'currency' => $data['currency'] ?? 'IDR',
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'created_by' => $userId,
            ]);

            if ($quotation) {
                foreach ($quotation->items as $qItem) {
                    $po->items()->create([
                        'description' => $qItem->description ?? $qItem->name,
                        'item_name' => $qItem->name ?? null,
                        'item_code' => $qItem->code ?? null,
                        'specification' => $qItem->specification ?? null,
                        'quantity' => $qItem->quantity,
                        'unit' => $qItem->unit ?? 'pcs',
                        'unit_price' => $qItem->unit_price,
                        'line_total' => (float) $qItem->quantity * (float) $qItem->unit_price,
                    ]);
                }
            }

            foreach ($items as $item) {
                $po->items()->create($item);
            }

            $po->recalculateTotals()->save();

            $this->audit($po, 'created');

            return $po->fresh('items');
        });
    }

    public function update(PurchaseOrder $po, array $data, array $items = []): PurchaseOrder
    {
        if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Cannot edit PO in current state.');
        }

        return DB::transaction(function () use ($po, $data, $items) {
            $po->update(array_filter($data, fn ($v) => !is_null($v)));

            if ($items) {
                $po->items()->delete();
                foreach ($items as $item) {
                    $po->items()->create($item);
                }
                $po->recalculateTotals()->save();
            }

            $this->audit($po, 'updated');

            return $po->fresh('items');
        });
    }

    public function send(PurchaseOrder $po, ?string $notes = null): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_SENT);

        $po->update([
            'status' => PurchaseOrder::STATUS_SENT,
            'sent_at' => now(),
            'special_instructions' => $notes
                ? ($po->special_instructions . "\n[Sent] " . $notes)
                : $po->special_instructions,
        ]);

        $this->audit($po, 'sent');

        return $po;
    }

    public function accept(PurchaseOrder $po, int $vendorUserId): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_ACCEPTED);

        $po->update([
            'status' => PurchaseOrder::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'accepted_by' => $vendorUserId,
        ]);

        $this->audit($po, 'accepted');

        return $po;
    }

    public function reject(PurchaseOrder $po, int $vendorUserId, string $reason): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_REJECTED);

        $po->update([
            'status' => PurchaseOrder::STATUS_REJECTED,
            'rejected_at' => now(),
            'accepted_by' => $vendorUserId,
            'rejection_reason' => $reason,
        ]);

        $this->audit($po, 'rejected', ['reason' => $reason]);

        return $po;
    }

    public function startProduction(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_IN_PRODUCTION);

        $po->update(['status' => PurchaseOrder::STATUS_IN_PRODUCTION]);

        $this->audit($po, 'production_started');

        return $po;
    }

    public function markReadyToShip(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_READY_TO_SHIP);

        $po->update(['status' => PurchaseOrder::STATUS_READY_TO_SHIP]);
        $this->audit($po, 'ready_to_ship');

        return $po;
    }

    public function markShipped(PurchaseOrder $po, array $shipmentInfo = []): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_SHIPPED);

        $po->update([
            'status' => PurchaseOrder::STATUS_SHIPPED,
            'shipped_at' => now(),
        ]);

        $po->deliveries()->create(array_merge([
            'courier' => null,
            'tracking_number' => null,
            'shipped_at' => now(),
            'destination' => $po->delivery_address,
        ], $shipmentInfo));

        $this->audit($po, 'shipped', $shipmentInfo);

        return $po;
    }

    public function markInTransit(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_IN_TRANSIT);

        $po->update(['status' => PurchaseOrder::STATUS_IN_TRANSIT]);
        $this->audit($po, 'in_transit');

        return $po;
    }

    public function markDelivered(PurchaseOrder $po, ?\Illuminate\Http\UploadedFile $bastFile = null): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_DELIVERED);

        $bastPath = null;
        if ($bastFile) {
            $bastPath = $bastFile->store('purchase-orders/bast', 'public');
        }

        $po->update([
            'status' => PurchaseOrder::STATUS_DELIVERED,
            'delivered_at' => now(),
            'actual_delivery_date' => now()->toDateString(),
        ]);

        $this->audit($po, 'delivered');

        return $po;
    }

    public function startQc(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_QC_IN_PROGRESS);

        $po->update(['status' => PurchaseOrder::STATUS_QC_IN_PROGRESS]);
        $this->audit($po, 'qc_started');

        return $po;
    }

    public function completeQc(PurchaseOrder $po, bool $passed): PurchaseOrder
    {
        $target = $passed
            ? PurchaseOrder::STATUS_QC_PASSED
            : PurchaseOrder::STATUS_QC_FAILED;

        $this->machine->assert('po', $po->status, $target);

        $po->update(['status' => $target]);
        $this->audit($po, $passed ? 'qc_passed' : 'qc_failed');

        return $po;
    }

    public function markInvoiced(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_INVOICED);

        $po->update(['status' => PurchaseOrder::STATUS_INVOICED]);
        $this->audit($po, 'invoiced');

        return $po;
    }

    public function markPaid(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_PAID);

        $po->update(['status' => PurchaseOrder::STATUS_PAID]);
        $this->audit($po, 'paid');

        return $po;
    }

    public function close(PurchaseOrder $po): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_CLOSED);

        $po->update(['status' => PurchaseOrder::STATUS_CLOSED]);
        $this->audit($po, 'closed');

        return $po;
    }

    public function cancel(PurchaseOrder $po, ?string $reason = null): PurchaseOrder
    {
        $this->machine->assert('po', $po->status, PurchaseOrder::STATUS_CANCELLED);

        $po->update([
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'rejection_reason' => $reason,
        ]);
        $this->audit($po, 'cancelled', ['reason' => $reason]);

        return $po;
    }

    private function audit(PurchaseOrder $po, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "po.{$action}",
            'entity_type' => PurchaseOrder::class,
            'entity_id' => $po->id,
            'metadata' => array_merge([
                'po_number' => $po->po_number,
                'status' => $po->status,
            ], $meta),
        ]);
    }
}