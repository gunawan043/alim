<?php

namespace App\Services\Push;

use App\Exceptions\InvalidPushTokenException;
use App\Models\NotificationUniversal;
use App\Models\UserDevice;
use App\Support\ApnsConfig;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pengirim APNs token-based (iOS).
 *
 * APNs tidak menagih per notifikasi; yang diperlukan adalah Apple Developer
 * Program dan key .p8 yang diatur di Super Admin.
 */
class ApnsService
{
    public function send(UserDevice $device, NotificationUniversal $notification): bool
    {
        if (! ApnsConfig::isConfigured()) {
            throw new RuntimeException('Konfigurasi APNs belum lengkap.');
        }

        $payload = [
            'aps' => [
                'alert' => [
                    'title' => Str::limit(strip_tags((string) $notification->title), 100),
                    'body' => Str::limit(strip_tags((string) $notification->message), 500),
                ],
                'sound' => 'default',
            ],
            ...$this->dataPayload($notification),
        ];

        $response = $this->request($device, $notification, $payload);

        // Provider token JWT dapat kedaluwarsa/ditolak; refresh sekali.
        if ($response->status() === 403) {
            $this->forgetJwt();
            $response = $this->request($device, $notification, $payload);
        }

        if ($response->successful()) {
            return true;
        }

        $reason = trim((string) preg_replace('/\s+/', ' ', (string) $response->body()));

        if ($response->status() === 410
            || in_array($reason, ['BadDeviceToken', 'DeviceTokenNotForTopic', 'Unregistered'], true)) {
            throw new InvalidPushTokenException('Token APNs tidak valid atau sudah dicabut.');
        }

        Log::warning('APNs push failed', [
            'device_id' => $device->id,
            'notification_id' => $notification->id,
            'status' => $response->status(),
            'reason' => Str::limit($reason, 300),
        ]);

        throw new RuntimeException("APNs push failed with HTTP {$response->status()}.");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function request(UserDevice $device, NotificationUniversal $notification, array $payload): Response
    {
        $options = ['version' => '2.0'];

        if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_2_0')) {
            $options['curl'] = [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0];
        }

        return Http::withToken($this->providerToken())
            ->withHeaders([
                'apns-topic' => ApnsConfig::bundleId(),
                'apns-push-type' => 'alert',
                'apns-priority' => in_array($notification->priority, ['high', 'urgent'], true) ? '10' : '5',
                'apns-id' => (string) $notification->id,
            ])
            ->withOptions($options)
            ->acceptJson()
            ->timeout(20)
            ->post(ApnsConfig::baseUrl().'/3/device/'.$device->push_token, $payload);
    }

    private function providerToken(): string
    {
        $keyId = ApnsConfig::keyId();
        $teamId = ApnsConfig::teamId();
        $privateKey = ApnsConfig::privateKey();
        $cacheKey = 'apns_jwt:'.sha1($keyId.'|'.$teamId);

        return Cache::remember($cacheKey, 3000, function () use ($keyId, $teamId, $privateKey) {
            try {
                return JWT::encode([
                    'iss' => $teamId,
                    'iat' => time(),
                ], $privateKey, 'ES256', $keyId);
            } catch (\Throwable $e) {
                Log::warning('Gagal membuat JWT APNs', ['error' => $e->getMessage()]);

                throw new RuntimeException('Gagal membuat provider token APNs.');
            }
        });
    }

    private function forgetJwt(): void
    {
        Cache::forget('apns_jwt:'.sha1(ApnsConfig::keyId().'|'.ApnsConfig::teamId()));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataPayload(NotificationUniversal $notification): array
    {
        return array_filter([
            'notification_id' => (string) $notification->id,
            'module' => $notification->module,
            'action' => $notification->action,
            'reference_type' => $notification->reference_type,
            'reference_id' => $notification->reference_id,
            'action_url' => $notification->action_url,
            ...($notification->data ?? []),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
