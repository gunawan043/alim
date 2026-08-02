<?php

namespace App\Jobs;

use App\Models\IntegrationEventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * The single point of notification fan-out. Takes an event payload + audience
 * and dispatches to all configured channels (DB, email, WhatsApp, push, etc).
 *
 * Each channel is a separate handler so failures in one don't break others.
 */
class DispatchNotificationBusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $eventName,
        public readonly array $audience,
        public readonly array $payload,
        public readonly array $channels,
        public readonly string $sourceModule,
        public readonly string $targetModule,
        public readonly ?string $aggregateId = null,
        public readonly ?string $aggregateType = null,
    ) {}

    public function handle(): void
    {
        $logPayload = [
            'event' => $this->eventName,
            'channels' => $this->channels,
            'audience' => $this->audience,
        ];

        try {
            // 1. Always log into database as fallback audit
            IntegrationEventLog::record(
                eventName: $this->eventName,
                sourceModule: $this->sourceModule,
                targetModule: $this->targetModule,
                aggregateId: $this->aggregateId,
                aggregateType: $this->aggregateType,
                payload: array_merge($this->payload, $logPayload),
                dispatchedBy: auth()->id(),
            );

            // 2. Internal notifications: store in DB notifications table
            if (in_array('internal', $this->channels, true) || in_array('parent_portal', $this->channels, true)) {
                $this->fanOutInternalNotifications();
            }

            // 3. Email
            if (in_array('email', $this->channels, true)) {
                $this->fanOutEmail();
            }

            // 4. WhatsApp — dispatched via dedicated job so it doesn't block
            if (in_array('whatsapp', $this->channels, true)) {
                DispatchWhatsAppNotificationJob::dispatch(
                    eventName: $this->eventName,
                    audience: $this->audience,
                    payload: $this->payload,
                );
            }

            // 5. Mobile Push
            if (in_array('push', $this->channels, true)) {
                DispatchPushNotificationJob::dispatch(
                    eventName: $this->eventName,
                    audience: $this->audience,
                    payload: $this->payload,
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification bus failed', [
                'event' => $this->eventName,
                'error' => $e->getMessage(),
            ]);
            IntegrationEventLog::record(
                eventName: $this->eventName,
                sourceModule: $this->sourceModule,
                targetModule: $this->targetModule,
                aggregateId: $this->aggregateId,
                aggregateType: $this->aggregateType,
                payload: $this->payload,
                status: IntegrationEventLog::STATUS_FAILED,
                error: $e->getMessage(),
                dispatchedBy: auth()->id(),
            );

            throw $e;
        }
    }

    private function fanOutInternalNotifications(): void
    {
        $waliIds = $this->audience['wali'] ?? [];

        foreach ($waliIds as $userId) {
            \App\Models\Notification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'integration.'.$this->eventName,
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'event' => $this->eventName,
                    'source_module' => $this->sourceModule,
                    'payload' => $this->payload,
                    'aggregate_id' => $this->aggregateId,
                    'aggregate_type' => $this->aggregateType,
                ]),
            ]);
        }
    }

    private function fanOutEmail(): void
    {
        // Email fan-out is intentionally non-blocking.
        // Each wali gets a dedicated SendEmailJob queued to mail queue.
        $waliIds = $this->audience['wali'] ?? [];

        foreach ($waliIds as $userId) {
            SendNotificationEmailJob::dispatch(
                userId: $userId,
                eventName: $this->eventName,
                payload: $this->payload,
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Notification bus permanently failed', [
            'event' => $this->eventName,
            'error' => $e->getMessage(),
        ]);
    }
}
