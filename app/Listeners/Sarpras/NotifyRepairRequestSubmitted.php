<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\RepairRequestSubmitted;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyRepairRequestSubmitted
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle(RepairRequestSubmitted $event): void
    {
        $sarprasAdmins = $this->sarprasAdmins();
        $recipients = array_unique(array_merge($sarprasAdmins, [$event->reporter->id]));

        $this->notifier->dispatch(
            eventType: 'RepairRequestSubmitted',
            recipientUserIds: $recipients,
            title: 'New Damage Report',
            message: "Laporan kerusakan baru: {$event->repair->request_number}",
            context: [
                'reference_type' => 'repair_request',
                'reference_id' => $event->repair->id,
                'reference_code' => $event->repair->request_number,
                'asset_id' => $event->asset->id,
                'priority' => $event->repair->priority ?? 'medium',
            ],
            priority: $event->repair->priority ?? 'medium',
        );
    }

    protected function sarprasAdmins(): array
    {
        try {
            return usersHavingPermission('sarpras.manager.approvable');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
