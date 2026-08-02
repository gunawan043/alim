<?php

namespace App\Services\Sarpras;

use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Thin notification dispatcher for Sarpras lifecycle events. Logs structured
 * events; if a queue notification class exists it will be dispatched.
 */
class SarprasNotificationService
{
    public function dispatchRepairStatusChange(RepairRequest $repair): void
    {
        Log::info('sarpras.repair.status_changed', [
            'repair_id' => $repair->id,
            'request_number' => $repair->request_number,
            'status' => $repair->status,
            'asset_id' => $repair->asset_id,
            'reporter_id' => $repair->reported_by,
            'assigned_to' => $repair->assigned_to,
        ]);

        $this->broadcast($repair);
    }

    public function dispatchWorkOrderCreated(WorkOrder $wo): void
    {
        Log::info('sarpras.wo.created', [
            'wo_id' => $wo->id,
            'wo_number' => $wo->wo_number,
            'asset_id' => $wo->asset_id,
            'technician_id' => $wo->technician_id,
        ]);

        $this->broadcast($wo);
    }

    protected function broadcast($subject): void
    {
        try {
            $recipients = $this->resolveRecipients($subject);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new \App\Notifications\SarprasLifecycleNotification($subject));
            }
        } catch (\Throwable $e) {
            Log::warning('sarpras.notification.broadcast_failed', [
                'message' => $e->getMessage(),
                'subject_type' => $subject::class,
            ]);
        }
    }

    protected function resolveRecipients($subject): \Illuminate\Support\Collection
    {
        if ($subject instanceof RepairRequest) {
            return collect([$subject->reported_by, $subject->assigned_to])
                ->filter()
                ->unique()
                ->map(fn ($id) => User::find($id))
                ->filter();
        }
        if ($subject instanceof WorkOrder) {
            return collect([$subject->technician_id])
                ->filter()
                ->map(fn ($id) => User::find($id))
                ->filter();
        }

        return collect();
    }
}
