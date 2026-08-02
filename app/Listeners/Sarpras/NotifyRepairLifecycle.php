<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\RepairApproved;
use App\Events\Sarpras\RepairRejected;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyRepairLifecycle
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        if ($event instanceof RepairApproved) {
            $this->dispatch(
                $event->repair,
                $event->approver,
                'RepairApproved',
                'Laporan Disetujui',
                "Laporan {$event->repair->request_number} disetujui oleh {$event->approver->name}.",
            );

            return;
        }

        if ($event instanceof RepairRejected) {
            $this->dispatch(
                $event->repair,
                $event->rejecter,
                'RepairRejected',
                'Laporan Ditolak',
                "Laporan {$event->repair->request_number} ditolak. Alasan: {$event->reason}",
                'high',
            );
        }
    }

    protected function dispatch($repair, $actor, $type, $title, $message, $priority = 'medium'): void
    {
        $reporter = $repair->reporter ?? null;
        $recipients = $reporter ? [$reporter->id] : [];
        if ($actor && (! $reporter || $actor->id !== $reporter->id)) {
            $recipients[] = $actor->id;
        }

        $this->notifier->dispatch(
            eventType: $type,
            recipientUserIds: $recipients,
            title: $title,
            message: $message,
            context: [
                'reference_type' => 'repair_request',
                'reference_id' => $repair->id,
                'reference_code' => $repair->request_number,
            ],
            priority: $priority,
        );
    }
}
