<?php

namespace App\Services\Vendor;

use App\Events\VendorNotificationDispatched;
use App\Models\Vendor;
use App\Models\VendorNotification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function send(Vendor|int $vendor, string $title, ?string $body = null, array $data = []): VendorNotification
    {
        $vendorId = $vendor instanceof Vendor ? $vendor->id : $vendor;

        $notification = DB::transaction(function () use ($vendorId, $title, $body, $data) {
            return VendorNotification::create([
                'vendor_id' => $vendorId,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'created_at' => now(),
            ]);
        });

        VendorNotificationDispatched::dispatch($notification);

        return $notification;
    }

    public function sendBulk(iterable $vendorIds, string $title, ?string $body = null, array $data = []): int
    {
        $count = 0;
        foreach ($vendorIds as $vendorId) {
            $this->send($vendorId, $title, $body, $data);
            $count++;
        }

        return $count;
    }

    public function markRead(int $notificationId, int $vendorId): bool
    {
        $notification = VendorNotification::where('id', $notificationId)
            ->where('vendor_id', $vendorId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllRead(int $vendorId): int
    {
        return VendorNotification::where('vendor_id', $vendorId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCount(int $vendorId): int
    {
        return VendorNotification::where('vendor_id', $vendorId)
            ->whereNull('read_at')
            ->count();
    }
}