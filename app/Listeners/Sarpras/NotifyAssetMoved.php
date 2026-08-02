<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\AssetMoved;
use App\Events\Sarpras\LoanOverdue;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyAssetMoved
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle($event): void
    {
        if ($event instanceof AssetMoved) {
            $this->notifier->dispatch(
                eventType: 'AssetMoved',
                recipientUserIds: $this->sarprasAdmins(),
                title: 'Asset Moved',
                message: "{$event->asset->asset_code}: {$event->fromLocation} → {$event->toLocation}",
                context: [
                    'reference_type' => 'asset',
                    'reference_id' => $event->asset->id,
                    'asset_code' => $event->asset->asset_code,
                    'from' => $event->fromLocation,
                    'to' => $event->toLocation,
                ],
                priority: 'low',
            );

            return;
        }

        if ($event instanceof LoanOverdue) {
            $recipients = [];
            if ($event->borrower) {
                $recipients[] = $event->borrower->id;
            }
            $recipients = array_merge($recipients, $this->sarprasAdmins());

            $this->notifier->dispatch(
                eventType: 'LoanOverdue',
                recipientUserIds: $recipients,
                title: 'Loan Overdue',
                message: "Peminjaman {$event->asset->asset_code} terlambat {$event->overdueDays} hari.",
                context: [
                    'reference_type' => 'asset',
                    'reference_id' => $event->asset->id,
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
            return usersHavingPermission('sarpras.manager.approvable');
        } catch (\Throwable $e) {
            return [];
        }
    }
}
