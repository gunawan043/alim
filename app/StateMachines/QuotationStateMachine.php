<?php

namespace App\StateMachines;

use App\Events\QuotationAccepted;
use App\Events\QuotationSubmitted;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Quotation;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Support\Facades\DB;

class QuotationStateMachine
{
    public const TRANSITIONS = [
        'draft' => ['submitted', 'withdrawn'],
        'submitted' => ['under_review', 'withdrawn'],
        'under_review' => ['accepted', 'rejected'],
        'accepted' => [],
        'rejected' => [],
        'withdrawn' => [],
        'expired' => [],
    ];

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function canTransition(Quotation $quotation, string $toState): bool
    {
        return in_array($toState, self::TRANSITIONS[$quotation->status] ?? [], true);
    }

    public function assertCanTransition(Quotation $quotation, string $toState): void
    {
        if (! $this->canTransition($quotation, $toState)) {
            throw new InvalidStateTransitionException(
                $quotation->status,
                $toState,
                'quotation',
                $quotation->id
            );
        }
    }

    public function transition(Quotation $quotation, string $toState, array $payload = [], $actor = null): Quotation
    {
        $this->assertCanTransition($quotation, $toState);

        return DB::transaction(function () use ($quotation, $toState, $payload, $actor) {
            $fromState = $quotation->status;

            switch ($toState) {
                case Quotation::STATUS_SUBMITTED:
                    $quotation->submitted_by = $actor instanceof \App\Models\Vendor ? $actor->id : null;
                    $quotation->submitted_at = now();
                    break;

                case Quotation::STATUS_UNDER_REVIEW:
                    $quotation->reviewed_by = $actor instanceof \App\Models\User ? $actor->id : null;
                    $quotation->reviewed_at = now();
                    break;

                case Quotation::STATUS_REJECTED:
                    $quotation->reviewed_by = $actor instanceof \App\Models\User ? $actor->id : null;
                    $quotation->reviewed_at = now();
                    $quotation->rejection_reason = $payload['reason'] ?? $quotation->rejection_reason;
                    break;
            }

            $quotation->status = $toState;
            $quotation->save();

            $this->auditTrail->recordStateTransition($quotation, $fromState, $toState, $actor);

            if ($toState === Quotation::STATUS_SUBMITTED) {
                QuotationSubmitted::dispatch($quotation);
            }

            if ($toState === Quotation::STATUS_ACCEPTED) {
                QuotationAccepted::dispatch($quotation);
            }

            return $quotation->fresh();
        });
    }

    public function submit(Quotation $quotation, $vendor = null): Quotation
    {
        return $this->transition($quotation, Quotation::STATUS_SUBMITTED, [], $vendor);
    }

    public function accept(Quotation $quotation, $actor = null): Quotation
    {
        return $this->transition($quotation, Quotation::STATUS_ACCEPTED, [], $actor);
    }

    public function reject(Quotation $quotation, string $reason, $actor = null): Quotation
    {
        return $this->transition(
            $quotation,
            Quotation::STATUS_REJECTED,
            ['reason' => $reason],
            $actor
        );
    }

    public function withdraw(Quotation $quotation, $actor = null): Quotation
    {
        return $this->transition($quotation, Quotation::STATUS_WITHDRAWN, [], $actor);
    }

    public function expire(Quotation $quotation): Quotation
    {
        $quotation->status = Quotation::STATUS_EXPIRED;
        $quotation->save();

        return $this->auditTrail->recordStateTransition($quotation, Quotation::STATUS_DRAFT, Quotation::STATUS_EXPIRED);
    }
}
