<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Mobile push notification fan-out. Uses FCM/APNS bridge.
 */
class DispatchPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 30;

    public string $queue = 'push';

    public function __construct(
        public readonly string $eventName,
        public readonly array $audience,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $waliIds = $this->audience['wali'] ?? [];

        foreach ($waliIds as $userId) {
            Log::info('Push notification queued', [
                'event' => $this->eventName,
                'user_id' => $userId,
                'payload' => $this->payload,
            ]);
        }
    }
}