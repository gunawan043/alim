<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\WorkOrderCompleted;
use App\Events\Sarpras\WorkOrderStarted;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyWorkOrderLifecycle
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        if ($event instanceof WorkOrderStarted) {
            $this->notifier->dispatch(
                eventType: 'WorkOrderStarted',
                recipientUserIds: [$event->technician->id],
                title: 'Work Order Started',
                message: "WO {$event->workOrder->order_number} mulai dikerjakan.",
                context: [
                    'reference_type' => 'work_order',
                    'reference_id' => $event->workOrder->id,
                    'reference_code' => $event->workOrder->order_number,
                ],
                priority: 'medium',
            );

            return;
        }

        if ($event instanceof WorkOrderCompleted) {
            $reporter = $event->workOrder->repairRequest?->reporter ?? null;
            $recipients = [$event->technician->id];
            if ($reporter && $reporter->id !== $event->technician->id) {
                $recipients[] = $reporter->id;
            }

            $this->notifier->dispatch(
                eventType: 'WorkOrderCompleted',
                recipientUserIds: $recipients,
                title: 'Work Order Completed',
                message: "WO {$event->workOrder->order_number} telah selesai.",
                context: [
                    'reference_type' => 'work_order',
                    'reference_id' => $event->workOrder->id,
                    'reference_code' => $event->workOrder->order_number,
                ],
                priority: 'low',
            );
        }
    }
}
