<?php

namespace App\Jobs;

use App\Notifications\IntegrationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Sends an email via Laravel's notification channel for integration events.
 */
class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public string $queue = 'email';

    public function __construct(
        public readonly int $userId,
        public readonly string $eventName,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $user = \App\Models\User::find($this->userId);
        if (! $user) {
            Log::warning('SendNotificationEmailJob: User not found', ['user_id' => $this->userId]);

            return;
        }

        try {
            NotificationFacade::send($user, new IntegrationNotification(
                eventName: $this->eventName,
                payload: $this->payload,
            ));
        } catch (\Throwable $e) {
            Log::error('SendNotificationEmailJob failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
