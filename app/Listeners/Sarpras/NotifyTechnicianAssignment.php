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
            message: "Work order {$order->order_number} ditugaskan kepada {$event->technician->name}. Mohon segera ditindaklanjuti.",
            context: [
                'reference_type' => 'work_order',
                'reference_id' => $order->id,
                'reference_code' => $order->order_number,
                'action_url' => $this->workOrderUrl($order),
                'action_text' => 'Buka Work Order',
                'priority' => $order->priority ?? 'medium',
            ],
            priority: $order->priority ?? 'medium',
        );
    }

    /**
     * URL work order untuk penerima. Route butuh {userId}; bila listener
     * dipanggil di luar request web (job/console) defaults belum tersedia,
     * jadi kegagalan pembuatan URL tidak boleh menggagalkan notifikasi.
     */
    private function workOrderUrl($order): ?string
    {
        try {
            return route('sarpras.teknisi.show', ['orderId' => $order->id]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
