<?php

namespace App\Jobs;

use App\Models\NotificationUniversal;
use App\Models\User;
use App\Services\Push\PushNotificationService;
use App\Services\Push\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Job push universal. Mengirim notifikasi in-app yang sudah tersimpan ke
 * perangkat mobile melalui FCM/APNs dan browser melalui Web Push VAPID.
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

    public function handle(PushNotificationService $push, WebPushService $webPush): void
    {
        if (! $this->user instanceof User || ! $this->notification instanceof NotificationUniversal) {
            Log::warning('Universal push skipped: invalid user/notification payload', [
                'user_id' => $this->user->id ?? null,
                'notification_id' => $this->notification->id ?? null,
            ]);

            return;
        }

        $mobileConfigured = $push->isConfigured();
        $webConfigured = $webPush->isConfigured();

        if (! $mobileConfigured && ! $webConfigured) {
            Log::info('Universal push skipped: FCM/APNs/Web Push is not configured', [
                'user_id' => $this->user->id,
                'notification_id' => $this->notification->id,
            ]);

            return;
        }

        if ($this->notification->is_push_sent) {
            return;
        }

        $result = ['sent' => 0, 'failed' => 0, 'invalid' => 0, 'expired' => 0, 'skipped' => 0];

        if ($mobileConfigured) {
            $mobileResult = $push->sendToUser($this->user, $this->notification);
            foreach ($result as $key => $value) {
                $result[$key] = $value + (int) ($mobileResult[$key] ?? 0);
            }
        }

        if ($webConfigured) {
            try {
                $webResult = $webPush->sendToUser($this->user, $this->notification);
                foreach ($result as $key => $value) {
                    $result[$key] = $value + (int) ($webResult[$key] ?? 0);
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Web Push job gagal', [
                    'user_id' => $this->user->id,
                    'notification_id' => $this->notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (($result['sent'] ?? 0) < 1) {
            Log::info('Universal push not delivered to any device', [
                'user_id' => $this->user->id,
                'notification_id' => $this->notification->id,
                'result' => $result,
            ]);

            // Hanya retry bila provider aktif benar-benar gagal. Token invalid
            // sudah dicabut; perangkat yang belum terdaftar tidak perlu retry.
            if (($result['failed'] ?? 0) > 0) {
                throw new RuntimeException('Semua pengiriman push gagal.');
            }

            return;
        }

        $this->notification->forceFill([
            'is_push_sent' => true,
            'push_sent_at' => now(),
        ])->save();

        Log::info('Universal push accepted by provider', [
            'user_id' => $this->user->id,
            'notification_id' => $this->notification->id,
            'result' => $result,
        ]);
    }
}
