<?php

namespace App\Listeners;

use App\Events\VendorNotificationDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendVendorNotificationListener implements ShouldQueue
{
    public string $queue = 'vendor-notifications';

    public function handle(VendorNotificationDispatched $event): void
    {
        $notification = $event->notification;

        $notification->update(['delivered_at' => now()]);

        Log::info('Vendor notification delivered', [
            'vendor_id' => $notification->vendor_id,
            'notification_id' => $notification->id,
            'title' => $notification->title,
        ]);
    }
}
