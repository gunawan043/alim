<?php

namespace App\Listeners\Sarpras;

use App\Events\Sarpras\WarrantyExpired;
use App\Services\Sarpras\Automation\SarprasNotificationService;

class NotifyWarrantyExpired
{
    public function __construct(protected SarprasNotificationService $notifier) {}

    public function handle(WarrantyExpired $event): void
    {
        $isExpiring = $event->daysUntilExpiry >= 0 && $event->daysUntilExpiry <= 30;
        $message = $isExpiring
            ? "Garansi {$event->asset->asset_code} akan habis dalam {$event->daysUntilExpiry} hari."
            : "Garansi {$event->asset->asset_code} sudah berakhir.";

        $this->notifier->dispatch(
            eventType: 'WarrantyExpired',
            recipientUserIds: $this->sarprasAdmins(),
            title: $isExpiring ? 'Warranty Expiring Soon' : 'Warranty Expired',
            message: $message,
            context: [
                'reference_type' => 'asset',
                'reference_id' => $event->asset->id,
                'asset_code' => $event->asset->asset_code,
                'days_until_expiry' => $event->daysUntilExpiry,
            ],
            priority: $isExpiring ? 'medium' : 'high',
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