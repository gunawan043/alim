<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job push universal. Saat ini menjadi adapter aman untuk pipeline push;
 * pengiriman FCM/APNS dapat dihubungkan di sini tanpa mengubah pemanggil.
 */
class SendUniversalPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 30;


    public function __construct(
        public mixed $user,
        public mixed $notification,
    ) {}

    public function handle(): void
    {
        Log::info('Universal push notification queued', [
            'user_id' => $this->user->id ?? null,
            'notification_id' => $this->notification->id ?? null,
            'title' => $this->notification->title ?? null,
        ]);
    }
}
