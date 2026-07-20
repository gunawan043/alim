<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\Quotation;
use App\Models\RfqRequest;
use App\Models\RfqItem;
use App\Models\RfqInvitation;
use App\Models\AuditTrail;
use App\Models\Notification;
use App\Services\Sarpras\StateMachine;
use Illuminate\Support\Facades\DB;

class RfqService
{
    public const TRANSITIONS = [
        'rfq' => [
            RfqRequest::STATUS_DRAFT => [
                RfqRequest::STATUS_PUBLISHED,
                RfqRequest::STATUS_CANCELLED,
            ],
            RfqRequest::STATUS_PUBLISHED => [
                RfqRequest::STATUS_AWAITING_QUOTATIONS,
                RfqRequest::STATUS_CANCELLED,
            ],
            RfqRequest::STATUS_AWAITING_QUOTATIONS => [
                RfqRequest::STATUS_UNDER_EVALUATION,
            ],
            RfqRequest::STATUS_UNDER_EVALUATION => [
                RfqRequest::STATUS_AWARDED,
                RfqRequest::STATUS_CLOSED,
                RfqRequest::STATUS_CANCELLED,
            ],
            RfqRequest::STATUS_AWARDED => [
                RfqRequest::STATUS_CLOSED,
            ],
        ],
    ];

    public function __construct(private StateMachine $machine) {}

    public function create(int $userId, array $data, array $items = []): RfqRequest
    {
        return DB::transaction(function () use ($userId, $data, $items) {
            $rfq = RfqRequest::create([
                'rfq_number' => (new RfqRequest())->generateNumber(),
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'quotation_deadline' => $data['quotation_deadline'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'delivery_location' => $data['delivery_location'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'created_by' => $userId,
                'status' => RfqRequest::STATUS_DRAFT,
            ]);

            foreach ($items as $item) {
                $rfq->items()->create($item);
            }

            $this->audit($rfq, 'created', [], $userId);

            return $rfq->fresh('items');
        });
    }

    public function update(RfqRequest $rfq, array $data, array $items = []): RfqRequest
    {
        if ($rfq->status === RfqRequest::STATUS_CANCELLED) {
            throw new \InvalidArgumentException('Cannot edit cancelled RFQ.');
        }

        if (in_array($rfq->status, [
            RfqRequest::STATUS_AWARDED,
            RfqRequest::STATUS_CLOSED,
        ], true)) {
            throw new \InvalidArgumentException('Cannot edit closed RFQ.');
        }

        $rfq->update(array_filter(array_merge([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'quotation_deadline' => $data['quotation_deadline'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'terms_conditions' => $data['terms_conditions'] ?? null,
        ])));

        if ($items) {
            $rfq->items()->delete();
            foreach ($items as $item) {
                $rfq->items()->create($item);
            }
        }

        return $rfq->fresh('items');
    }

    public function publish(RfqRequest $rfq, ?array $vendorIds = []): RfqRequest
    {
        $this->machine->assert('rfq', $rfq->status, RfqRequest::STATUS_PUBLISHED);

        if ($rfq->items()->count() === 0) {
            throw new \InvalidArgumentException('RFQ must have at least one item.');
        }

        $rfq->update([
            'status' => RfqRequest::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        if ($vendorIds) {
            $this->inviteVendors($rfq, $vendorIds);
        }

        $this->audit($rfq, 'published');

        return $rfq;
    }

    public function markAwaitingQuotations(RfqRequest $rfq): RfqRequest
    {
        $this->machine->assert('rfq', $rfq->status, RfqRequest::STATUS_AWAITING_QUOTATIONS);

        $rfq->update(['status' => RfqRequest::STATUS_AWAITING_QUOTATIONS]);

        $this->audit($rfq, 'awaiting_quotations');

        return $rfq;
    }

    public function startEvaluation(RfqRequest $rfq): RfqRequest
    {
        $this->machine->assert('rfq', $rfq->status, RfqRequest::STATUS_UNDER_EVALUATION);

        $rfq->update(['status' => RfqRequest::STATUS_UNDER_EVALUATION]);

        $this->audit($rfq, 'under_evaluation');

        return $rfq;
    }

    public function award(RfqRequest $rfq, Quotation $quotation): RfqRequest
    {
        $this->machine->assert('rfq', $rfq->status, RfqRequest::STATUS_AWARDED);

        if ($quotation->rfq_id !== $rfq->id) {
            throw new \InvalidArgumentException('Quotation does not belong to RFQ.');
        }

        $rfq->update([
            'status' => RfqRequest::STATUS_AWARDED,
            'awarded_quotation_id' => $quotation->id,
            'closed_at' => now(),
        ]);

        $this->audit($rfq, 'awarded', ['quotation_id' => $quotation->id]);

        return $rfq;
    }

    public function close(RfqRequest $rfq): RfqRequest
    {
        if (!in_array($rfq->status, [
            RfqRequest::STATUS_DRAFT,
            RfqRequest::STATUS_PUBLISHED,
            RfqRequest::STATUS_AWAITING_QUOTATIONS,
            RfqRequest::STATUS_UNDER_EVALUATION,
        ], true)) {
            throw new \InvalidArgumentException('Cannot close RFQ in current state.');
        }

        $rfq->update([
            'status' => RfqRequest::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->audit($rfq, 'closed');

        return $rfq;
    }

    public function cancel(RfqRequest $rfq, ?string $reason = null): RfqRequest
    {
        $this->machine->assert('rfq', $rfq->status, RfqRequest::STATUS_CANCELLED);

        $rfq->update(['status' => RfqRequest::STATUS_CANCELLED]);

        $this->audit($rfq, 'cancelled', ['reason' => $reason]);

        return $rfq;
    }

    public function inviteVendors(RfqRequest $rfq, array $vendorIds): void
    {
        $vendorIds = array_unique(array_filter($vendorIds));

        foreach ($vendorIds as $vendorId) {
            $invitation = $rfq->invitations()->updateOrCreate(
                ['vendor_id' => $vendorId],
                ['status' => 'invited']
            );

            if (!$invitation->wasRecentlyCreated) {
                $invitation->update(['status' => 'invited']);
            }

            Notification::create([
                'user_id' => $vendorId,
                'type' => 'rfq_published',
                'title' => 'New RFQ',
                'message' => "You have been invited to RFQ {$rfq->rfq_number}",
                'data' => ['rfq_id' => $rfq->id],
            ]);
        }
    }

    public function getVendorQuotations(RfqRequest $rfq, Vendor $vendor): array
    {
        return $rfq->quotations()
            ->where('vendor_id', $vendor->id)
            ->get()
            ->all();
    }

    private function audit(RfqRequest $rfq, string $action, array $meta = [], ?int $actorId = null): void
    {
        AuditTrail::create([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => "rfq.{$action}",
            'entity_type' => RfqRequest::class,
            'entity_id' => $rfq->id,
            'metadata' => array_merge([
                'rfq_number' => $rfq->rfq_number,
                'status' => $rfq->status,
            ], $meta),
        ]);
    }
}