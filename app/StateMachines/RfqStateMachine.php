<?php

namespace App\StateMachines;

use App\Events\RfqPublished;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\RfqRequest;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Support\Facades\DB;

class RfqStateMachine
{
    public const TRANSITIONS = [
        'draft' => ['published', 'cancelled'],
        'published' => ['awaiting_quotations', 'cancelled', 'closed'],
        'awaiting_quotations' => ['under_evaluation', 'closed', 'cancelled'],
        'under_evaluation' => ['awarded', 'closed', 'cancelled'],
        'awarded' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function canTransition(RfqRequest $rfq, string $toState): bool
    {
        return in_array($toState, self::TRANSITIONS[$rfq->status] ?? [], true);
    }

    public function assertCanTransition(RfqRequest $rfq, string $toState): void
    {
        if (! $this->canTransition($rfq, $toState)) {
            throw new InvalidStateTransitionException(
                $rfq->status,
                $toState,
                'rfq',
                $rfq->id
            );
        }
    }

    public function transition(RfqRequest $rfq, string $toState, array $payload = [], $actor = null): RfqRequest
    {
        $this->assertCanTransition($rfq, $toState);

        return DB::transaction(function () use ($rfq, $toState, $payload, $actor) {
            $fromState = $rfq->status;

            switch ($toState) {
                case RfqRequest::STATUS_PUBLISHED:
                    $this->validateForPublishing($rfq);
                    $rfq->published_at = now();
                    break;

                case RfqRequest::STATUS_CLOSED:
                    $rfq->closed_at = now();
                    break;

                case RfqRequest::STATUS_CANCELLED:
                    $rfq->closed_at = now();
                    break;

                case RfqRequest::STATUS_AWARDED:
                    if (empty($payload['awarded_quotation_id'])) {
                        throw new \InvalidArgumentException('awarded_quotation_id is required for award transition');
                    }
                    $rfq->awarded_quotation_id = $payload['awarded_quotation_id'];
                    break;
            }

            $rfq->status = $toState;
            $rfq->save();

            $this->auditTrail->recordStateTransition($rfq, $fromState, $toState, $actor);

            if ($toState === RfqRequest::STATUS_PUBLISHED) {
                RfqPublished::dispatch($rfq);
            }

            return $rfq->fresh();
        });
    }

    public function publish(RfqRequest $rfq, $actor = null): RfqRequest
    {
        return $this->transition($rfq, RfqRequest::STATUS_PUBLISHED, [], $actor);
    }

    public function cancel(RfqRequest $rfq, ?string $reason = null, $actor = null): RfqRequest
    {
        return $this->transition(
            $rfq,
            RfqRequest::STATUS_CANCELLED,
            ['cancellation_reason' => $reason],
            $actor
        );
    }

    public function close(RfqRequest $rfq, $actor = null): RfqRequest
    {
        return $this->transition($rfq, RfqRequest::STATUS_CLOSED, [], $actor);
    }

    public function moveToAwaitingQuotations(RfqRequest $rfq, $actor = null): RfqRequest
    {
        return $this->transition($rfq, RfqRequest::STATUS_AWAITING_QUOTATIONS, [], $actor);
    }

    public function moveToUnderEvaluation(RfqRequest $rfq, $actor = null): RfqRequest
    {
        return $this->transition($rfq, RfqRequest::STATUS_UNDER_EVALUATION, [], $actor);
    }

    public function award(RfqRequest $rfq, int $quotationId, $actor = null): RfqRequest
    {
        return $this->transition(
            $rfq,
            RfqRequest::STATUS_AWARDED,
            ['awarded_quotation_id' => $quotationId],
            $actor
        );
    }

    protected function validateForPublishing(RfqRequest $rfq): void
    {
        if ($rfq->items()->count() === 0) {
            throw new \DomainException('RFQ must have at least one item before publishing.');
        }

        if ($rfq->invitations()->count() === 0) {
            throw new \DomainException('RFQ must invite at least one vendor before publishing.');
        }

        if ($rfq->quotation_deadline->isPast()) {
            throw new \DomainException('Quotation deadline cannot be in the past.');
        }
    }
}
