<?php

namespace App\StateMachines;

use App\Events\PoAccepted;
use App\Events\PoDelivered;
use App\Events\PoQcCompleted;
use App\Events\PoShipped;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\PurchaseOrder;
use App\Services\Vendor\AuditTrailService;
use Illuminate\Support\Facades\DB;

class PurchaseOrderStateMachine
{
    public const TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['accepted', 'rejected'],
        'accepted' => ['in_production'],
        'rejected' => [],
        'in_production' => ['ready_to_ship'],
        'ready_to_ship' => ['shipped'],
        'shipped' => ['in_transit'],
        'in_transit' => ['delivered'],
        'delivered' => ['qc_in_progress'],
        'qc_in_progress' => ['qc_passed', 'qc_failed'],
        'qc_passed' => ['invoiced', 'closed'],
        'qc_failed' => ['qc_in_progress'],
        'invoiced' => ['paid'],
        'paid' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function __construct(protected AuditTrailService $auditTrail) {}

    public function canTransition(PurchaseOrder $po, string $toState): bool
    {
        return in_array($toState, self::TRANSITIONS[$po->status] ?? [], true);
    }

    public function assertCanTransition(PurchaseOrder $po, string $toState): void
    {
        if (! $this->canTransition($po, $toState)) {
            throw new InvalidStateTransitionException(
                $po->status,
                $toState,
                'purchase_order',
                $po->id
            );
        }
    }

    public function transition(PurchaseOrder $po, string $toState, array $payload = [], $actor = null): PurchaseOrder
    {
        $this->assertCanTransition($po, $toState);

        return DB::transaction(function () use ($po, $toState, $payload, $actor) {
            $fromState = $po->status;

            switch ($toState) {
                case PurchaseOrder::STATUS_SENT:
                    $po->sent_at = now();
                    break;

                case PurchaseOrder::STATUS_ACCEPTED:
                    $po->accepted_by = $actor instanceof \App\Models\Vendor ? $actor->id : null;
                    $po->accepted_at = now();
                    break;

                case PurchaseOrder::STATUS_REJECTED:
                    $po->rejected_at = now();
                    $po->rejection_reason = $payload['reason'] ?? $po->rejection_reason;
                    break;

                case PurchaseOrder::STATUS_SHIPPED:
                    $po->shipped_at = now();
                    break;

                case PurchaseOrder::STATUS_DELIVERED:
                    $po->delivered_at = now();
                    $po->actual_delivery_date = now()->toDateString();
                    break;
            }

            $po->status = $toState;
            $po->save();

            $this->auditTrail->recordStateTransition($po, $fromState, $toState, $actor);

            if ($toState === PurchaseOrder::STATUS_ACCEPTED) {
                PoAccepted::dispatch($po);
            }
            if ($toState === PurchaseOrder::STATUS_SHIPPED) {
                PoShipped::dispatch($po);
            }
            if ($toState === PurchaseOrder::STATUS_DELIVERED) {
                PoDelivered::dispatch($po);
            }
            if ($toState === PurchaseOrder::STATUS_QC_PASSED || $toState === PurchaseOrder::STATUS_QC_FAILED) {
                PoQcCompleted::dispatch($po);
            }

            return $po->fresh();
        });
    }

    public function send(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_SENT, [], $actor);
    }

    public function accept(PurchaseOrder $po, $vendor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_ACCEPTED, [], $vendor);
    }

    public function reject(PurchaseOrder $po, string $reason, $actor = null): PurchaseOrder
    {
        return $this->transition(
            $po,
            PurchaseOrder::STATUS_REJECTED,
            ['reason' => $reason],
            $actor
        );
    }

    public function startProduction(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_IN_PRODUCTION, [], $actor);
    }

    public function markReadyToShip(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_READY_TO_SHIP, [], $actor);
    }

    public function markShipped(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_SHIPPED, [], $actor);
    }

    public function markDelivered(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_DELIVERED, [], $actor);
    }

    public function startQc(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_QC_IN_PROGRESS, [], $actor);
    }

    public function qcPassed(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_QC_PASSED, [], $actor);
    }

    public function qcFailed(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_QC_FAILED, [], $actor);
    }

    public function markInvoiced(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_INVOICED, [], $actor);
    }

    public function markPaid(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_PAID, [], $actor);
    }

    public function close(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        return $this->transition($po, PurchaseOrder::STATUS_CLOSED, [], $actor);
    }

    public function cancel(PurchaseOrder $po, $actor = null): PurchaseOrder
    {
        // Cancel must transition from draft or sent
        if (in_array($po->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])) {
            return $this->transition($po, PurchaseOrder::STATUS_CANCELLED, [], $actor);
        }

        // From other active states, close it
        return $this->transition($po, PurchaseOrder::STATUS_CLOSED, ['cancelled' => true], $actor);
    }
}
