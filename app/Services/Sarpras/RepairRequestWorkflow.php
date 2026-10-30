<?php

namespace App\Services\Sarpras;

use App\Events\Sarpras\RepairApproved;
use App\Events\Sarpras\RepairRejected;
use App\Events\Sarpras\RepairRequestSubmitted;
use App\Events\Sarpras\WarrantyClaimOpportunity;
use App\Events\Sarpras\WorkOrderAssigned;
use App\Events\Sarpras\WorkOrderCompleted;
use App\Events\Sarpras\WorkOrderProgressAdded;
use App\Events\Sarpras\WorkOrderStarted;
use App\Models\Asset;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProgress;
use Illuminate\Support\Facades\DB;

/**
 * Workflow 1+2 driver for RepairRequest + WorkOrder.
 *
 * Status transitions are validated against StateMachineRegistry.
 * Every transition persists a row in asset_event_logs and updates the
 * asset.status when the workflow drives the asset into / out of repair.
 */
class RepairRequestWorkflow
{
    public function __construct(
        protected AssetEventLogger $eventLogger,
        protected StateMachine $stateMachine
    ) {
        $this->stateMachine->define(
            StateMachineRegistry::REPAIR_REQUEST,
            StateMachineRegistry::REPAIR_REQUEST_TRANSITIONS
        );
        $this->stateMachine->define(
            StateMachineRegistry::WORK_ORDER,
            StateMachineRegistry::WORK_ORDER_TRANSITIONS
        );
        $this->stateMachine->define(
            StateMachineRegistry::ASSET_STATUS,
            StateMachineRegistry::ASSET_STATUS_TRANSITIONS
        );
    }

    /* =========================================================
     * Workflow 1 — Damage Reporting
     * ========================================================= */

    /**
     * Submit a damage report. Creates a RepairRequest in `submitted`,
     * ready for the Sarpras verification step.
     */
    public function submitDamageReport(
        Asset $asset,
        User $reporter,
        string $title,
        string $description,
        string $priority = 'normal'
    ): RepairRequest {
        return DB::transaction(function () use ($asset, $reporter, $title, $description, $priority) {
            $request = RepairRequest::create([
                'asset_id' => $asset->id,
                'reported_by' => $reporter->id,
                'title' => $title,
                'description' => $description,
                'priority' => $priority,
                'status' => 'submitted',
            ]);

            $this->eventLogger->logRepairSubmitted(
                $asset,
                $request->request_number,
                $title,
                $reporter->id
            );

            RepairRequestSubmitted::dispatch($request, $asset, $reporter);

            return $request->fresh();
        });
    }

    /**
     * Sarpras verification step: status -> verified or rejected.
     * If verified, the asset status moves to `damaged` (if it was active),
     * and the request is parked at `waiting_work_order` ready for assignment.
     */
    public function verify(
        RepairRequest $request,
        User $reviewer,
        bool $approved,
        ?string $notes = null,
        ?string $rejectedReason = null
    ): RepairRequest {
        return DB::transaction(function () use ($request, $reviewer, $approved, $notes, $rejectedReason) {
            $fromStatus = $request->status;

            if ($approved) {
                $this->moveRepairRequest($request, 'verification_pending', $reviewer->id, [
                    'verification_notes' => $notes,
                ], [
                    'verified_at' => now(),
                    'reviewer_id' => $reviewer->id,
                ]);

                $this->eventLogger->logRepairVerified(
                    $request->asset,
                    $request->request_number,
                    $reviewer->id
                );

                // Move the asset into "damaged" if it was previously active/borrowed.
                $asset = $request->asset;
                if (in_array($asset->status, ['active', 'borrowed'], true)) {
                    $this->moveAssetStatus($asset, 'damaged', $reviewer->id, 'damage verified');
                }

                // Auto-progress to approval_pending — ready for work-order assignment.
                $this->moveRepairRequest($request, 'approval_pending', $reviewer->id, [
                    'note' => 'queued for work-order assignment',
                ]);

                RepairApproved::dispatch($request, $reviewer);

                if ($asset->garansi_berakhir && $asset->garansi_berakhir->isFuture()) {
                    $daysRemaining = (int) now()->diffInDays($asset->garansi_berakhir, false);
                    WarrantyClaimOpportunity::dispatch($asset, $request, $daysRemaining);
                }
            } else {
                $this->moveRepairRequest($request, 'verification_rejected', $reviewer->id, [
                    'rejected_reason' => $rejectedReason,
                ], [
                    'rejected_reason' => $rejectedReason,
                ]);

                $this->eventLogger->logRepairRejected(
                    $request->asset,
                    $request->request_number,
                    (string) $rejectedReason,
                    $reviewer->id
                );

                RepairRejected::dispatch($request, $reviewer, $rejectedReason);
            }

            return $request->fresh();
        });
    }

    /**
     * Generate the Work Order that the technician will execute.
     * Returns a WorkOrder in `created` status ready to be assigned.
     */
    public function generateWorkOrder(
        RepairRequest $request,
        User $creator,
        string $scopeOfWork,
        ?string $scheduledDate = null
    ): WorkOrder {
        return DB::transaction(function () use ($request, $creator, $scopeOfWork, $scheduledDate) {
            $this->stateMachine->assert(
                StateMachineRegistry::REPAIR_REQUEST,
                $request->status,
                'execution_pending'
            );

            $order = WorkOrder::create([
                'repair_request_id' => $request->id,
                'asset_id' => $request->asset_id,
                'type' => 'corrective',
                'scope_of_work' => $scopeOfWork,
                'scheduled_date' => $scheduledDate,
                'status' => 'draft',
            ]);

            $this->eventLogger->logWorkOrderCreated(
                $request->asset,
                $order->order_number,
                $creator->id
            );

            $progress = WorkOrderProgress::create([
                'work_order_id' => $order->id,
                'step_number' => '1',
                'status' => 'pending',
                'description' => 'Work order dibuat',
                'notes' => $scopeOfWork,
                'performed_by' => $creator->id,
            ]);

            WorkOrderProgressAdded::dispatch($order, $progress, $creator);

            // Move the request to `execution_pending` so the lifecycle matches the work-order.
            $this->moveRepairRequest($request, 'execution_pending', $creator->id, [
                'order_number' => $order->order_number,
            ]);

            // Asset is now under repair.
            $this->moveAssetStatus($request->asset, 'under_repair', $creator->id, 'work order generated');

            return $order->fresh();
        });
    }

    /* =========================================================
     * Workflow 2 — Repair Execution
     * ========================================================= */

    public function assignTechnician(WorkOrder $order, User $assigner, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($order, $assigner, $technician) {
            $this->stateMachine->assert(
                StateMachineRegistry::WORK_ORDER,
                $order->status,
                'dispatched'
            );

            $order->update([
                'assignee_id' => $technician->id,
                'status' => 'dispatched',
            ]);

            $this->eventLogger->logWorkOrderAssigned(
                $order->asset,
                $order->order_number,
                $technician->id,
                $assigner->id
            );

            WorkOrderAssigned::dispatch($order, $technician, $assigner);

            return $order->fresh();
        });
    }

    /**
     * Teknisi mengakui/menerima penugasan. Status tetap `dispatched`;
     * pengerjaan sesungguhnya dimulai lewat startWork().
     */
    public function acceptWorkOrder(WorkOrder $order, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($order) {
            $order->update([
                'actual_start' => $order->actual_start ?? now(),
            ]);

            return $order->fresh();
        });
    }

    public function startWork(WorkOrder $order, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($order, $technician) {
            if ($order->status === 'in_progress') {
                return $order->fresh();
            }

            $this->stateMachine->assert(
                StateMachineRegistry::WORK_ORDER,
                $order->status,
                'in_progress'
            );

            $order->update([
                'status' => 'in_progress',
                'actual_start' => $order->actual_start ?? now(),
            ]);

            // Permintaan perbaikan ikut bergerak ke `started`.
            $request = $order->repairRequest;
            if ($request && $request->status === 'execution_pending') {
                $this->moveRepairRequest($request, 'started', $technician->id, [
                    'order_number' => $order->order_number,
                    'started_at' => now(),
                ]);
            }

            WorkOrderStarted::dispatch($order, $technician);

            return $order->fresh();
        });
    }

    public function markWaitingSparepart(WorkOrder $order, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($order) {
            $this->stateMachine->assert(
                StateMachineRegistry::WORK_ORDER,
                $order->status,
                'waiting_parts'
            );

            $order->update(['status' => 'waiting_parts']);

            return $order->fresh();
        });
    }

    /**
     * Complete the work order. Drives the lifecycle forward:
     *  - the request moves `in_progress` -> `completed`
     *  - the asset condition is updated
     *  - if PIC confirms -> `verified_by_pic` -> `closed`
     */
    public function completeRepair(
        WorkOrder $order,
        User $technician,
        string $completionNotes,
        string $conditionAfter,
        float $totalCost = 0
    ): WorkOrder {
        return DB::transaction(function () use ($order, $technician, $completionNotes, $conditionAfter, $totalCost) {
            $this->stateMachine->assert(
                StateMachineRegistry::WORK_ORDER,
                $order->status,
                'completed'
            );

            $order->update([
                'status' => 'completed',
                'actual_end' => now(),
                'completion_notes' => $completionNotes,
                'total_cost' => $totalCost,
            ]);

            // Move the underlying request into `completed` (from `started`).
            $request = $order->repairRequest;
            if ($request && $request->status === 'started') {
                $this->moveRepairRequest($request, 'completed', $technician->id, [
                    'order_number' => $order->order_number,
                    'completion_notes' => $completionNotes,
                    'result_description' => $completionNotes,
                    'completed_at' => now(),
                ]);
            }

            // Log the lifecycle event so the asset condition listener fires.
            $this->eventLogger->logRepairCompleted(
                $order->asset,
                $order->order_number,
                $conditionAfter,
                $totalCost,
                $technician->id
            );

            WorkOrderCompleted::dispatch($order, $technician, $completionNotes);

            return $order->fresh();
        });
    }

    /**
     * PIC of the room confirms the completed repair, after which
     * the request is closed and the asset status is restored.
     */
    public function verifyByPic(RepairRequest $request, User $pic, ?string $notes = null): RepairRequest
    {
        return DB::transaction(function () use ($request, $pic, $notes) {
            $request->refresh();

            $this->moveRepairRequest($request, 'closed', $pic->id, [
                'verification_notes' => $notes,
                'closed_at' => now(),
            ]);

            // Asset returns to `active` unless it is still damaged.
            if ($request->asset->status === 'under_repair') {
                $this->moveAssetStatus(
                    $request->asset,
                    'active',
                    $pic->id,
                    'repair verified by PIC'
                );
            }

            // Close the work order.
            $order = $request->workOrders()->latest()->first();
            if ($order && $order->status === 'completed') {
                $this->stateMachine->assert(
                    StateMachineRegistry::WORK_ORDER,
                    $order->status,
                    'closed'
                );
                $order->update(['status' => 'closed']);

                $this->eventLogger->logRepairClosed(
                    $request->asset,
                    $order->order_number,
                    $pic->id
                );
            }

            return $request->fresh();
        });
    }

    /**
     * Helper: validate and persist a repair-request status transition.
     */
    public function moveRepairRequest(
        RepairRequest $request,
        string $targetStatus,
        ?string $actorId,
        array $eventDetail = [],
        array $modelUpdates = []
    ): RepairRequest {
        $from = $request->status;
        $this->stateMachine->assert(
            StateMachineRegistry::REPAIR_REQUEST,
            $from,
            $targetStatus
        );

        $updates = array_merge($modelUpdates, ['status' => $targetStatus]);
        $request->update($updates);

        return $request;
    }

    /**
     * Helper: validate and persist an asset.status transition.
     */
    public function moveAssetStatus(
        Asset $asset,
        string $targetStatus,
        ?string $actorId,
        string $reason
    ): Asset {
        $from = $asset->status ?: 'active';
        if ($from === $targetStatus) {
            return $asset;
        }

        $this->stateMachine->assert(
            StateMachineRegistry::ASSET_STATUS,
            $from,
            $targetStatus
        );

        $asset->update(['status' => $targetStatus]);

        $this->eventLogger->logAssetStatusChanged(
            $asset,
            $from,
            $targetStatus,
            $reason,
            $actorId
        );

        return $asset;
    }
}
