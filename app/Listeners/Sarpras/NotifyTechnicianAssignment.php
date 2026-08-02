<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\WorkOrderAssigned;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyTechnicianAssignment
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle(WorkOrderAssigned $event): void
    {
        $order = $event->workOrder;
        $recipients = [$event->technician->id];

        if ($event->assigner && $event->assigner->id !== $event->technician->id) {
            $recipients[] = $event->assigner->id;
        }

        $this->notifier->dispatch(
            eventType: 'WorkOrderAssigned',
            recipientUserIds: $recipients,
            title: 'Work Order Assigned',
            message: "WO {$order->order_number} assigned to {$event->technician->name}.",
            context: [
                'reference_type' => 'work_order',
                'reference_id' => $order->id,
                'reference_code' => $order->order_number,
                'priority' => $order->priority ?? 'medium',
            ],
            priority: $order->priority ?? 'medium',
        );
    }
}
