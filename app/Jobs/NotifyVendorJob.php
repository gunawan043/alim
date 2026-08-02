<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyVendorJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $vendorId,
        public string $subject,
        public string $message,
        public array $data = []
    ) {}

    public function handle(): void
    {
        $vendor = Vendor::find($this->vendorId);
        if (! $vendor) {
            return;
        }

        // Vendor-side: dispatch notification to all vendor admin users
        // (If user accounts exist)
        $userIds = $vendor->users()->pluck('users.id')->toArray();

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'vendor_notification',
                'title' => $this->subject,
                'message' => $this->message,
                'data' => array_merge($this->data, ['vendor_id' => $vendor->id]),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Log or record the failure
    }
}
