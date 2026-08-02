<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notification fan-out. Stub implementation — actual WA
 * gateway integration (e.g. Fonnte, Wablas) plugs in here.
 */
class DispatchWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public string $queue = 'whatsapp';

    public function __construct(
        public readonly string $eventName,
        public readonly array $audience,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $waliIds = $this->audience['wali'] ?? [];

        foreach ($waliIds as $userId) {
            // Implementation placeholder: gateway call goes here.
            // Kept idempotent — failures should not cascade.
            Log::info('WhatsApp notification queued', [
                'event' => $this->eventName,
                'user_id' => $userId,
                'payload' => $this->payload,
            ]);
        }
    }
}
