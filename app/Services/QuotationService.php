<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\Quotation;
use App\Models\RfqRequest;
use App\Models\Vendor;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public const TRANSITIONS = [
        'quotation' => [
            Quotation::STATUS_DRAFT => [
                Quotation::STATUS_SUBMITTED,
                Quotation::STATUS_WITHDRAWN,
            ],
            Quotation::STATUS_SUBMITTED => [
                Quotation::STATUS_UNDER_REVIEW,
                Quotation::STATUS_REJECTED,
                Quotation::STATUS_WITHDRAWN,
            ],
            Quotation::STATUS_UNDER_REVIEW => [
                Quotation::STATUS_ACCEPTED,
                Quotation::STATUS_REJECTED,
                Quotation::STATUS_EXPIRED,
            ],
            Quotation::STATUS_ACCEPTED => [
                Quotation::STATUS_EXPIRED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(RfqRequest $rfq, Vendor $vendor, int $userId, array $data = [], array $items = []): Quotation
    {
        return DB::transaction(function () use ($rfq, $vendor, $userId, $data, $items) {
            $quotation = Quotation::create([
                'quotation_number' => (new Quotation)->generateNumber(),
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(30)->toDateString(),
                'lead_time_days' => $data['lead_time_days'] ?? null,
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'currency' => $data['currency'] ?? 'IDR',
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'status' => Quotation::STATUS_DRAFT,
                'submitted_by' => $userId,
            ]);

            foreach ($items as $item) {
                $quotation->items()->create($item);
            }

            $quotation->recalculateTotals()->save();

            $this->audit($quotation, 'created');

            return $quotation->fresh('items');
        });
    }

    public function update(Quotation $quotation, array $data, array $items = []): Quotation
    {
        if ($quotation->status !== Quotation::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Cannot edit quotation in current state.');
        }

        return DB::transaction(function () use ($quotation, $data, $items) {
            $quotation->update(array_filter($data, fn ($v) => ! is_null($v)));

            if ($items) {
                $quotation->items()->delete();
                foreach ($items as $item) {
                    $quotation->items()->create($item);
                }
                $quotation->recalculateTotals()->save();
            }

            $this->audit($quotation, 'updated');

            return $quotation->fresh('items');
        });
    }

    public function submit(Quotation $quotation, int $userId): Quotation
    {
        $this->machine->assert('quotation', $quotation->status, Quotation::STATUS_SUBMITTED);

        if ($quotation->items()->count() === 0) {
            throw new \InvalidArgumentException('Quotation must have items.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by' => $userId,
        ]);

        $this->audit($quotation, 'submitted');

        // Notify evaluator
        if ($quotation->rfq?->created_by) {
            Notification::create([
                'user_id' => $quotation->rfq->created_by,
                'type' => 'quotation_submitted',
                'title' => 'Quotation submitted',
                'message' => "Vendor submitted quotation {$quotation->quotation_number}",
                'data' => ['quotation_id' => $quotation->id],
            ]);
        }

        return $quotation;
    }

    public function startReview(Quotation $quotation): Quotation
    {
        $this->machine->assert('quotation', $quotation->status, Quotation::STATUS_UNDER_REVIEW);

        $quotation->update(['status' => Quotation::STATUS_UNDER_REVIEW]);

        $this->audit($quotation, 'under_review');

        return $quotation;
    }

    public function negotiate(Quotation $quotation, array $adjustments): Quotation
    {
        if (! in_array($quotation->status, [
            Quotation::STATUS_UNDER_REVIEW,
            Quotation::STATUS_SUBMITTED,
        ], true)) {
            throw new \InvalidArgumentException('Cannot negotiate in current state.');
        }

        $quotation->update($adjustments);
        $quotation->recalculateTotals()->save();

        $this->audit($quotation, 'negotiated', $adjustments);

        return $quotation->fresh();
    }

    public function accept(Quotation $quotation, int $userId, ?string $comments = null): Quotation
    {
        $this->machine->assert('quotation', $quotation->status, Quotation::STATUS_ACCEPTED);

        $quotation->update([
            'status' => Quotation::STATUS_ACCEPTED,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'notes' => $comments ? "{$quotation->notes}\n[Accept] $comments" : $quotation->notes,
        ]);

        // Reject other quotations for same RFQ
        Quotation::where('rfq_id', $quotation->rfq_id)
            ->where('id', '!=', $quotation->id)
            ->whereNotIn('status', [Quotation::STATUS_REJECTED, Quotation::STATUS_WITHDRAWN])
            ->update([
                'status' => Quotation::STATUS_REJECTED,
                'rejection_reason' => 'Not selected as awarded quotation',
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);

        $this->audit($quotation, 'accepted', ['comments' => $comments]);

        return $quotation;
    }

    public function reject(Quotation $quotation, int $userId, string $reason): Quotation
    {
        $this->machine->assert('quotation', $quotation->status, Quotation::STATUS_REJECTED);

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->audit($quotation, 'rejected', ['reason' => $reason]);

        return $quotation;
    }

    public function withdraw(Quotation $quotation, int $userId, ?string $reason = null): Quotation
    {
        $this->machine->assert('quotation', $quotation->status, Quotation::STATUS_WITHDRAWN);

        $quotation->update([
            'status' => Quotation::STATUS_WITHDRAWN,
            'notes' => $reason ? "{$quotation->notes}\n[Withdraw] $reason" : $quotation->notes,
            'submitted_by' => $userId,
        ]);

        $this->audit($quotation, 'withdrawn', ['reason' => $reason]);

        return $quotation;
    }

    public function expire(Quotation $quotation): Quotation
    {
        if ($quotation->status !== Quotation::STATUS_UNDER_REVIEW) {
            return $quotation;
        }

        $quotation->update(['status' => Quotation::STATUS_EXPIRED]);
        $this->audit($quotation, 'expired');

        return $quotation;
    }

    public function getComparison(RfqRequest $rfq): array
    {
        $quotations = $rfq->quotations()->with('items')->get();

        return $quotations->map(function (Quotation $q) {
            return [
                'quotation_id' => $q->id,
                'vendor_id' => $q->vendor_id,
                'vendor_name' => $q->vendor?->name,
                'total' => (float) $q->total,
                'lead_time_days' => $q->lead_time_days,
                'terms' => $q->terms,
                'currency' => $q->currency,
                'status' => $q->status,
                'items' => $q->items->map(fn ($i) => [
                    'description' => $i->description ?? $i->name ?? '',
                    'unit_price' => (float) $i->unit_price,
                    'quantity' => (float) $i->quantity,
                    'total' => (float) $i->line_total,
                ]),
            ];
        })->toArray();
    }

    private function audit(Quotation $quotation, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "quotation.{$action}",
            'entity_type' => Quotation::class,
            'entity_id' => $quotation->id,
            'metadata' => array_merge([
                'quotation_number' => $quotation->quotation_number,
                'status' => $quotation->status,
            ], $meta),
        ]);
    }
}
