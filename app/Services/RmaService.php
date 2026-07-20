<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\Rma;
use App\Services\Sarpras\StateMachine;

class RmaService
{
    public const TRANSITIONS = [
        'rma' => [
            Rma::STATUS_OPEN => [
                Rma::STATUS_APPROVED,
                Rma::STATUS_CANCELLED,
            ],
            Rma::STATUS_APPROVED => [
                Rma::STATUS_IN_RETURN,
                Rma::STATUS_CANCELLED,
            ],
            Rma::STATUS_IN_RETURN => [
                Rma::STATUS_RECEIVED_BY_VENDOR,
                Rma::STATUS_REPLACEMENT_RECEIVED,
                Rma::STATUS_REFUNDED,
                Rma::STATUS_CREDITED,
                Rma::STATUS_CANCELLED,
            ],
            Rma::STATUS_RECEIVED_BY_VENDOR => [
                Rma::STATUS_REPLACEMENT_RECEIVED,
            ],
            Rma::STATUS_REPLACEMENT_RECEIVED => [
                Rma::STATUS_CLOSED,
            ],
            Rma::STATUS_REFUNDED => [
                Rma::STATUS_CLOSED,
            ],
            Rma::STATUS_CREDITED => [
                Rma::STATUS_CLOSED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(PurchaseOrder $po, int $userId, string $type, array $data): Rma
    {
        $rma = new Rma();
        $rma->setAttribute('rma_number', $rma->generateNumber());
        $rma->setAttribute('purchase_order_id', $po->id);
        $rma->setAttribute('vendor_id', $po->vendor_id);
        $rma->setAttribute('goods_receipt_id', $data['goods_receipt_id'] ?? null);
        $rma->setAttribute('quality_check_id', $data['quality_check_id'] ?? null);
        $rma->setAttribute('vendor_reference', $data['vendor_reference'] ?? null);
        $rma->setAttribute('status', Rma::STATUS_OPEN);
        $rma->setAttribute('type', $type);
        $rma->setAttribute('quantity', $data['quantity'] ?? 1);
        $rma->setAttribute('request_date', now()->toDateString());
        $rma->setAttribute('estimated_return_date', $data['estimated_return_date'] ?? null);
        $rma->setAttribute('description', $data['description'] ?? '');
        $rma->setAttribute('refund_amount', $data['refund_amount'] ?? 0);
        $rma->setAttribute('cost_deduction', $data['cost_deduction'] ?? 0);
        $rma->setAttribute('evidence', $data['evidence'] ?? null);
        $rma->setAttribute('created_by', $userId);
        $rma->save();

        $this->audit($rma, 'created');

        return $rma;
    }

    public function approve(Rma $rma, ?string $notes = null): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_APPROVED);

        $rma->update([
            'status' => Rma::STATUS_APPROVED,
            'resolution' => $notes,
        ]);

        $this->audit($rma, 'approved');

        return $rma;
    }

    public function reject(Rma $rma, string $reason): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_CANCELLED);

        $rma->update([
            'status' => Rma::STATUS_CANCELLED,
            'resolution' => $reason,
        ]);

        $this->audit($rma, 'rejected', ['reason' => $reason]);

        return $rma;
    }

    public function startReturn(Rma $rma, ?string $notes = null): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_IN_RETURN);

        $rma->update([
            'status' => Rma::STATUS_IN_RETURN,
            'resolution' => $notes,
        ]);

        $this->audit($rma, 'return_started');

        return $rma;
    }

    public function markReceivedByVendor(Rma $rma, ?string $notes = null): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_RECEIVED_BY_VENDOR);

        $rma->update([
            'status' => Rma::STATUS_RECEIVED_BY_VENDOR,
            'actual_return_date' => now()->toDateString(),
            'resolution' => $notes,
        ]);

        $this->audit($rma, 'received_by_vendor');

        return $rma;
    }

    public function receiveReplacement(Rma $rma, ?array $evidence = null): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_REPLACEMENT_RECEIVED);

        $rma->update([
            'status' => Rma::STATUS_REPLACEMENT_RECEIVED,
            'evidence' => $evidence,
            'actual_return_date' => now()->toDateString(),
        ]);

        $this->audit($rma, 'replacement_received');

        return $rma;
    }

    public function refund(Rma $rma, float $amount): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_REFUNDED);

        $rma->update([
            'status' => Rma::STATUS_REFUNDED,
            'refund_amount' => $amount,
            'resolved_at' => now(),
        ]);

        $this->audit($rma, 'refunded', ['amount' => $amount]);

        return $rma;
    }

    public function credit(Rma $rma, float $amount): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_CREDITED);

        $rma->update([
            'status' => Rma::STATUS_CREDITED,
            'cost_deduction' => $amount,
            'resolved_at' => now(),
        ]);

        $this->audit($rma, 'credited', ['amount' => $amount]);

        return $rma;
    }

    public function close(Rma $rma): Rma
    {
        $this->machine->assert('rma', $rma->status, Rma::STATUS_CLOSED);

        $rma->update(['status' => Rma::STATUS_CLOSED]);
        $this->audit($rma, 'closed');

        return $rma;
    }

    public function vendorRespond(Rma $rma, string $response, string $vendorId): Rma
    {
        $rma->update([
            'vendor_response' => $response,
            'vendor_responded_at' => now(),
        ]);

        $this->audit($rma, 'vendor_responded');

        // Notify admin
        Notification::create([
            'user_id' => $rma->created_by,
            'type' => 'rma_vendor_responded',
            'title' => 'Vendor responded to RMA',
            'message' => "Vendor has responded to RMA {$rma->rma_number}",
            'data' => ['rma_id' => $rma->id],
        ]);

        return $rma;
    }

    public function getOpenRmas(): array
    {
        return Rma::whereIn('status', [
            Rma::STATUS_OPEN,
            Rma::STATUS_APPROVED,
            Rma::STATUS_IN_RETURN,
            Rma::STATUS_RECEIVED_BY_VENDOR,
        ])
            ->orderBy('request_date')
            ->get()
            ->all();
    }

    private function audit(Rma $rma, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "rma.{$action}",
            'entity_type' => Rma::class,
            'entity_id' => $rma->id,
            'metadata' => array_merge([
                'rma_number' => $rma->rma_number,
                'status' => $rma->status,
            ], $meta),
        ]);
    }
}