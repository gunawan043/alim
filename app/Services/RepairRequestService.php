<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\RepairRequest;
use App\Models\WorkOrder;
use App\Services\Sarpras\SarprasNotificationService;
use Illuminate\Support\Facades\DB;

class RepairRequestService
{
    private const FLOW = [
        'verification' => [
            RepairRequest::STATUS_VERIFICATION_PENDING => 'verification_in_progress',
            RepairRequest::STATUS_VERIFICATION_IN_PROGRESS => 'approved_for_repair',
            RepairRequest::STATUS_VERIFICATION_IN_PROGRESS => 'rejected_verification',
            RepairRequest::STATUS_VERIFICATION_IN_PROGRESS => 'needs_additional_info',
        ],
        'approval' => [
            RepairRequest::STATUS_APPROVAL_PENDING => 'approved_for_repair',
            RepairRequest::STATUS_APPROVAL_PENDING => 'rejected_approval',
        ],
        'execution' => [
            RepairRequest::STATUS_EXECUTION_PENDING => 'started',
            RepairRequest::STATUS_STARTED => 'completed',
            RepairRequest::STATUS_COMPLETED => 'closed',
            RepairRequest::STATUS_STARTED => 'stopped',
            RepairRequest::STATUS_STOPPED => 'started',
            RepairRequest::STATUS_STOPPED => 'closed',
            RepairRequest::STATUS_COMPLETED => 'stopped',
        ],
    ];

    public function __construct(
        private RepairCostHistoryService $costHistoryService,
        private ?SarprasNotificationService $notifier = null,
    ) {}

    public function create(int $userId, int $assetId, array $data): RepairRequest
    {
        $rr = RepairRequest::create([
            'asset_id' => $assetId,
            'reported_by' => $userId,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => RepairRequest::STATUS_VERIFICATION_PENDING,
        ]);

        $this->audit($rr, 'created');

        return $rr;
    }

    public function assignInspector(RepairRequest $rr, string $userId, array $inspectors, int $selectedUserId): RepairRequest
    {
        return DB::transaction(function () use ($rr, $inspectors, $selectedUserId) {
            $rr->update([
                'status' => RepairRequest::STATUS_VERIFICATION_PENDING,
                'assigned_to' => $selectedUserId,
            ]);

            $this->audit($rr, 'inspector_assigned', [
                'inspector_ids' => $inspectors,
                'assigned_to' => $selectedUserId,
            ]);

            // Notify the assigned inspector
            Notification::create([
                'user_id' => $selectedUserId,
                'type' => 'repair_assignment',
                'title' => 'New Repair Assignment',
                'message' => "You have been assigned as inspector for repair request: {$rr->request_number}",
                'data' => ['repair_request_id' => $rr->id, 'inspector_ids' => $inspectors],
            ]);

            return $rr;
        });
    }

    public function startVerification(RepairRequest $rr): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_VERIFICATION_IN_PROGRESS,
        ]);

        $this->audit($rr, 'verification_started');

        return $rr;
    }

    public function submitVerification(RepairRequest $rr, int $userId, array $data): RepairRequest
    {
        return DB::transaction(function () use ($rr, $userId, $data) {
            $rr->update([
                'verification_notes' => $data['notes'] ?? null,
                'recommended_action' => $data['recommended_action'] ?? null,
            ]);

            $action = $data['recommended_action'] ?? 'approved';

            if ($action === RepairRequest::RECOMMENDATION_APPROVED) {
                return $this->approveForRepair($rr, $userId);
            } elseif ($action === RepairRequest::RECOMMENDATION_REJECTED) {
                return $this->rejectVerification($rr, $userId, $data['rejection_reason'] ?? 'No further action');
            } else {
                return $this->requestAdditionalInfo($rr, $userId, $data['feedback'] ?? 'More information needed');
            }
        });
    }

    public function requestAdditionalInfo(RepairRequest $rr, int $userId, string $feedback): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_ADDITIONAL_INFO,
            'feedback_for_reporter' => $feedback,
        ]);

        $this->audit($rr, 'additional_info_requested');

        return $rr;
    }

    public function resubmitAfterInfoUpdate(RepairRequest $rr, int $userId): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_VERIFICATION_IN_PROGRESS,
        ]);

        $this->audit($rr, 'info_updated_resubmitted');

        return $rr;
    }

    public function requestApproval(RepairRequest $rr): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_APPROVAL_PENDING,
        ]);

        $this->audit($rr, 'approval_requested');

        return $rr;
    }

    public function approveForRepair(RepairRequest $rr, int $userId = 0): RepairRequest
    {
        $rr = DB::transaction(function () use ($rr) {
            $rr->update([
                'status' => RepairRequest::STATUS_EXECUTION_PENDING,
                'approved_at' => now(),
            ]);

            $this->audit($rr, 'approved_for_repair');

            // Move the reporter from inspector to assigned technician
            $technician = $rr->assigned_to;
            $reporter = $rr->reported_by;

            $rr->update([
                'assigned_to' => $technician ?: $reporter,
            ]);

            $this->audit($rr, 'assigned_to_technician', ['assigned_to' => $technician ?: $reporter]);

            return $rr->fresh();
        });

        // Auto-create Work Order unless the caller (who is the project owner) opted out
        // by passing userId=0 and the repair was just for notification purposes. For all
        // PIC approvals, userId > 0, so a WO is created.
        if ($userId > 0) {
            try {
                $existingWo = WorkOrder::where('repair_request_id', $rr->id)->first();
                if (! $existingWo) {
                    $wo = WorkOrder::create([
                        'repair_request_id' => $rr->id,
                        'asset_id' => $rr->asset_id,
                        'type' => 'corrective',
                        'scope_of_work' => $rr->title.' — '.$rr->description,
                        'scheduled_date' => now(),
                        'status' => 'created',
                    ]);
                    $this->audit($rr, 'work_order_auto_created', [
                        'work_order_number' => $wo->wo_number ?? $wo->id,
                    ]);
                    if ($this->notifier) {
                        $this->notifier->dispatchWorkOrderCreated($wo);
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning('sarpras.repair.wo_auto_create_failed', [
                    'repair_id' => $rr->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rr;
    }

    public function rejectVerification(RepairRequest $rr, int $userId, string $reason): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_VERIFICATION_REJECTED,
            'rejected_reason' => $reason,
        ]);

        $this->audit($rr, 'verification_rejected');

        return $rr;
    }

    public function rejectApproval(RepairRequest $rr, string $reason): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_VERIFICATION_PENDING,
            'rejected_reason' => $reason,
        ]);

        $this->audit($rr, 'approval_rejected');

        return $rr;
    }

    public function startExecution(RepairRequest $rr, int $userId): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_STARTED,
            'assigned_to' => $userId,
            'started_at' => now(),
        ]);

        $this->audit($rr, 'execution_started');

        return $rr;
    }

    public function stopExecution(RepairRequest $rr, string $reason): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_STOPPED,
        ]);

        $this->audit($rr, 'execution_stopped', ['reason' => $reason]);

        return $rr;
    }

    public function completeExecution(RepairRequest $rr, string $resultDescription, float $laborCost): RepairRequest
    {
        return DB::transaction(function () use ($rr, $resultDescription, $laborCost) {
            $rr->update([
                'status' => RepairRequest::STATUS_COMPLETED,
                'result_description' => $resultDescription,
                'labor_cost' => $laborCost,
                'completed_at' => now(),
            ]);

            $this->costHistoryService->record($rr, $laborCost, $resultDescription);
            $this->audit($rr, 'execution_completed');

            return $rr;
        });
    }

    public function close(RepairRequest $rr): RepairRequest
    {
        $rr->update([
            'status' => RepairRequest::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->audit($rr, 'closed');

        return $rr;
    }

    public function getPendingVerifications(): array
    {
        return RepairRequest::whereIn('status', [
            RepairRequest::STATUS_VERIFICATION_PENDING,
            RepairRequest::STATUS_ADDITIONAL_INFO,
        ])
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    private function audit(RepairRequest $rr, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "repair.{$action}",
            'entity_type' => RepairRequest::class,
            'entity_id' => $rr->id,
            'metadata' => array_merge([
                'request_number' => $rr->request_number,
                'status' => $rr->status,
            ], $meta),
        ]);
    }
}
