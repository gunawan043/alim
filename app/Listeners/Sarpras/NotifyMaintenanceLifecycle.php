<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\MaintenanceDue;
use App\Events\Sarpras\MaintenanceOverdue;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyMaintenanceLifecycle
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        if ($event instanceof MaintenanceDue) {
            $this->notifier->dispatch(
                eventType: 'MaintenanceDue',
                recipientUserIds: $this->sarprasAdmins(),
                title: 'Maintenance Due',
                message: "Maintenance dijadwalkan untuk {$event->asset->asset_code}.",
                context: [
                    'reference_type' => 'maintenance',
                    'reference_id' => $event->history->id,
                    'asset_id' => $event->asset->id,
                    'asset_code' => $event->asset->asset_code,
                ],
                priority: 'medium',
            );

            return;
        }

        if ($event instanceof MaintenanceOverdue) {
            $this->notifier->dispatch(
                eventType: 'MaintenanceOverdue',
                recipientUserIds: $this->sarprasAdmins(),
                title: 'Maintenance Overdue',
                message: "Maintenance {$event->asset->asset_code} terlambat {$event->overdueDays} hari.",
                context: [
                    'reference_type' => 'maintenance',
                    'reference_id' => $event->history->id,
                    'asset_id' => $event->asset->id,
                    'asset_code' => $event->asset->asset_code,
                    'overdue_days' => $event->overdueDays,
                ],
                priority: 'high',
            );
        }
    }

    protected function sarprasAdmins(): array
    {
        try {
            return usersHavingPermission('sarpras.administrator.accessible');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
