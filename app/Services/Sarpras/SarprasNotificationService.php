<?php

namespace App\Services\Sarpras;

use App\Models\RepairRequest;
use App\Models\WorkOrder;
use App\Services\Sarpras\Automation\SarprasNotificationService as UniversalNotifier;
use Illuminate\Support\Facades\Log;

/**
 * Notifikasi lifecycle Sarpras untuk pemohon dan petugas yang ditugaskan.
 */
class SarprasNotificationService
{
    public function dispatchRepairStatusChange(RepairRequest|WorkOrder $subject): void
    {
        if ($subject instanceof WorkOrder) {
            $this->dispatchWorkOrderCreated($subject);
            return;
        }

        $recipients = collect([$subject->reported_by, $subject->assigned_to])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->send(
            eventType: 'RepairStatusChanged',
            recipientUserIds: $recipients,
            title: 'Status perbaikan: '.str_replace('_', ' ', $subject->status),
            message: "Permintaan {$subject->request_number} — {$subject->title}",
            context: [
                'reference_type' => 'repair_request',
                'reference_id' => $subject->id,
                'reference_code' => $subject->request_number,
                'action_url' => route('sarpras.repair.show', ['repairId' => $subject->id]),
                'action_text' => 'Buka Antrean Perbaikan',
            ],
            priority: $subject->priority ?? 'medium'
        );
    }

    public function dispatchWorkOrderCreated(WorkOrder $wo): void
    {
        $recipients = collect([$wo->assignee_id, $wo->created_by])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->send(
            eventType: 'WorkOrderAssigned',
            recipientUserIds: $recipients,
            title: 'Work Order baru: '.$wo->order_number,
            message: 'Anda ditugaskan pada work order '.$wo->order_number.'.',
            context: [
                'reference_type' => 'work_order',
                'reference_id' => $wo->id,
                'reference_code' => $wo->order_number,
                'action_url' => route('sarpras.teknisi.show', ['orderId' => $wo->id]),
                'action_text' => 'Buka Work Order',
            ],
            priority: $wo->maintenance_schedule_id ? 'medium' : 'high'
        );
    }

    private function send(
        string $eventType,
        array $recipientUserIds,
        string $title,
        string $message,
        array $context = [],
        string $priority = 'medium'
    ): void {
        try {
            app(UniversalNotifier::class)->dispatch(
                eventType: $eventType,
                recipientUserIds: $recipientUserIds,
                title: $title,
                message: $message,
                context: $context,
                priority: $priority
            );
        } catch (\Throwable $e) {
            Log::warning('sarpras.notification.failed', [
                'event' => $eventType,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
