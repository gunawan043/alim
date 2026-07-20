<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\DeliveryTracking;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public const TRANSITIONS = [
        'goods_receipt' => [
            GoodsReceipt::STATUS_RECEIVED => [
                GoodsReceipt::STATUS_UNDER_INSPECTION,
                GoodsReceipt::STATUS_PARTIAL,
            ],
            GoodsReceipt::STATUS_UNDER_INSPECTION => [
                GoodsReceipt::STATUS_ACCEPTED,
                GoodsReceipt::STATUS_REJECTED,
                GoodsReceipt::STATUS_PARTIAL,
            ],
            GoodsReceipt::STATUS_PARTIAL => [
                GoodsReceipt::STATUS_ACCEPTED,
                GoodsReceipt::STATUS_REJECTED,
            ],
            GoodsReceipt::STATUS_ACCEPTED => [
                GoodsReceipt::STATUS_CLOSED,
            ],
            GoodsReceipt::STATUS_REJECTED => [
                GoodsReceipt::STATUS_CLOSED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(PurchaseOrder $po, int $userId, ?DeliveryTracking $delivery = null, array $data = [], array $items = []): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $userId, $delivery, $data, $items) {
            $gr = GoodsReceipt::create([
                'gr_number' => (new GoodsReceipt())->generateNumber(),
                'purchase_order_id' => $po->id,
                'delivery_id' => $delivery?->id,
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'status' => GoodsReceipt::STATUS_RECEIVED,
                'warehouse_location' => $data['warehouse_location'] ?? null,
                'received_by' => $userId,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'supplier_delivery_note' => $data['supplier_delivery_note'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $items = $items ?: $this->generateItemsFromPO($po);

            foreach ($items as $itemData) {
                $gr->items()->create($itemData);
            }

            $this->audit($gr, 'created');

            return $gr->fresh('items');
        });
    }

    private function generateItemsFromPO(PurchaseOrder $po): array
    {
        return $po->items->map(fn (PurchaseOrderItem $item) => [
            'purchase_order_item_id' => $item->id,
            'item_name' => $item->item_name ?? $item->description,
            'item_code' => $item->item_code,
            'expected_quantity' => $item->quantity,
            'received_quantity' => $item->quantity,
            'accepted_quantity' => 0,
            'rejected_quantity' => 0,
            'unit' => $item->unit ?? 'pcs',
            'notes' => null,
        ])->toArray();
    }

    public function startInspection(GoodsReceipt $gr): GoodsReceipt
    {
        $this->machine->assert('goods_receipt', $gr->status, GoodsReceipt::STATUS_UNDER_INSPECTION);

        $gr->update(['status' => GoodsReceipt::STATUS_UNDER_INSPECTION]);
        $this->audit($gr, 'inspection_started');

        return $gr;
    }

    public function recordAcceptedQuantities(GoodsReceipt $gr, array $acceptedQuantities): GoodsReceipt
    {
        return DB::transaction(function () use ($gr, $acceptedQuantities) {
            foreach ($gr->items as $item) {
                $key = (string) $item->id;
                $accepted = $acceptedQuantities[$key] ?? $item->received_quantity;
                $item->update([
                    'accepted_quantity' => $accepted,
                    'rejected_quantity' => max(0, $item->received_quantity - $accepted),
                ]);
            }

            $this->audit($gr, 'quantities_recorded', $acceptedQuantities);

            return $gr->fresh('items');
        });
    }

    public function accept(GoodsReceipt $gr): GoodsReceipt
    {
        $this->machine->assert('goods_receipt', $gr->status, GoodsReceipt::STATUS_ACCEPTED);

        $gr->update(['status' => GoodsReceipt::STATUS_ACCEPTED]);

        $this->audit($gr, 'accepted');

        return $gr;
    }

    public function reject(GoodsReceipt $gr, string $reason): GoodsReceipt
    {
        $this->machine->assert('goods_receipt', $gr->status, GoodsReceipt::STATUS_REJECTED);

        $gr->update([
            'status' => GoodsReceipt::STATUS_REJECTED,
            'notes' => $gr->notes . "\n[Rejected] {$reason}",
        ]);

        $this->audit($gr, 'rejected', ['reason' => $reason]);

        return $gr;
    }

    public function markPartial(GoodsReceipt $gr): GoodsReceipt
    {
        $this->machine->assert('goods_receipt', $gr->status, GoodsReceipt::STATUS_PARTIAL);

        $gr->update(['status' => GoodsReceipt::STATUS_PARTIAL]);
        $this->audit($gr, 'partial');

        return $gr;
    }

    public function close(GoodsReceipt $gr): GoodsReceipt
    {
        $this->machine->assert('goods_receipt', $gr->status, GoodsReceipt::STATUS_CLOSED);

        $gr->update(['status' => GoodsReceipt::STATUS_CLOSED]);
        $this->audit($gr, 'closed');

        return $gr;
    }

    public function hasDiscrepancies(GoodsReceipt $gr): bool
    {
        return $gr->items->contains(
            fn ($i) => (int) $i->received_quantity !== (int) $i->expected_quantity
        );
    }

    private function audit(GoodsReceipt $gr, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "goods_receipt.{$action}",
            'entity_type' => GoodsReceipt::class,
            'entity_id' => $gr->id,
            'metadata' => array_merge([
                'gr_number' => $gr->gr_number,
                'status' => $gr->status,
            ], $meta),
        ]);
    }
}