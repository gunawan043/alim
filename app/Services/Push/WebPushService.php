<?php

namespace App\Services\Push;

use App\Models\NotificationUniversal;
use App\Models\User;
use App\Models\WebPushSubscription;
use App\Support\WebPushConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

/**
 * Pengirim Web Push VAPID untuk browser.
 *
 * Bekerja walaupun tab/browser ditutup, selama user pernah mengaktifkan
 * notifikasi browser dan subscription-nya masih aktif.
 */
class WebPushService
{
    public function isConfigured(): bool
    {
        return WebPushConfig::isConfigured();
    }

    /**
     * @return array{sent:int,failed:int,expired:int}
     */
    public function sendToUser(User $user, NotificationUniversal $notification): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'expired' => 0];

        if (! $this->isConfigured()) {
            return $result;
        }

        $subscriptions = WebPushSubscription::query()
            ->where('user_id', $user->id)
            ->active()
            ->get();

        if ($subscriptions->isEmpty()) {
            return $result;
        }

        $payload = json_encode($this->payload($notification), JSON_THROW_ON_ERROR);

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => WebPushConfig::subject(),
                    'publicKey' => WebPushConfig::vapidPublicKey(),
                    'privateKey' => WebPushConfig::vapidPrivateKey(),
                ],
            ], ['TTL' => 3600]);
        } catch (\Throwable $e) {
            Log::warning('Web Push tidak dapat diinisialisasi', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Web Push tidak dapat diinisialisasi.', 0, $e);
        }

        foreach ($subscriptions as $subscription) {
            try {
                $report = $webPush->sendOneNotification(
                    new Subscription(
                        $subscription->endpoint,
                        $subscription->p256dh,
                        $subscription->auth_token,
                        $subscription->content_encoding,
                    ),
                    $payload,
                    [
                        'TTL' => 3600,
                        'urgency' => in_array($notification->priority, ['high', 'urgent'], true) ? 'high' : 'normal',
                    ],
                );

                if ($report->isSuccess()) {
                    $result['sent']++;
                    $subscription->update(['last_seen_at' => now()]);
                } elseif ($report->isSubscriptionExpired()) {
                    $subscription->update(['revoked_at' => now()]);
                    $result['expired']++;
                } else {
                    $result['failed']++;
                    Log::warning('Web Push ditolak', [
                        'subscription_id' => $subscription->id,
                        'notification_id' => $notification->id,
                        'reason' => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('Web Push gagal', [
                    'subscription_id' => $subscription->id,
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(NotificationUniversal $notification): array
    {
        $data = $notification->data ?? [];
        $url = $notification->action_url ?: '/';

        return [
            'title' => Str::limit(strip_tags((string) $notification->title), 100),
            'body' => Str::limit(strip_tags((string) $notification->message), 500),
            'icon' => asset('build/images/alim-sm-light.png'),
            'badge' => asset('build/images/alim-sm-light.png'),
            'url' => $url,
            'tag' => 'alim-'.$notification->id,
            'notification_id' => (string) $notification->id,
            'module' => $notification->module,
            'action' => $notification->action,
            'screen' => $data['screen'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'reference_type' => $notification->reference_type,
            'reference_id' => $notification->reference_id,
        ];
    }
}
