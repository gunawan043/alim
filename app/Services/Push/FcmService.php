<?php

namespace App\Services\Push;

use App\Exceptions\InvalidPushTokenException;
use App\Models\NotificationUniversal;
use App\Models\UserDevice;
use App\Support\FcmConfig;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pengirim FCM HTTP v1 (Android).
 *
 * FCM hanya membutuhkan registration token perangkat dan service account
 * Firebase. Tidak ada biaya per pesan.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(UserDevice $device, NotificationUniversal $notification): bool
    {
        $projectId = FcmConfig::projectId();
        $account = FcmConfig::serviceAccount();

        if ($projectId === '' || ! is_array($account)) {
            throw new RuntimeException('Konfigurasi FCM belum lengkap.');
        }

        $accessToken = $this->accessToken($account);
        $payload = [
            'message' => [
                'token' => $device->push_token,
                'notification' => [
                    'title' => Str::limit(strip_tags((string) $notification->title), 100),
                    'body' => Str::limit(strip_tags((string) $notification->message), 500),
                ],
                'data' => $this->normalizeData($this->dataPayload($notification)),
                'android' => [
                    'priority' => in_array($notification->priority, ['high', 'urgent'], true) ? 'HIGH' : 'NORMAL',
                    'notification' => [
                        'channel_id' => 'alim_default',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->post($url, $payload);

        // Access token bisa kedaluwarsa lebih cepat dari cache. Refresh sekali.
        if ($response->status() === 401) {
            $this->forgetAccessToken($account);
            $response = Http::withToken($this->accessToken($account))
                ->acceptJson()
                ->timeout(20)
                ->post($url, $payload);
        }

        if ($response->successful()) {
            return true;
        }

        $body = $response->json();
        $errorStatus = (string) ($body['error']['status'] ?? '');
        $errorMessage = (string) ($body['error']['message'] ?? '');

        if ($response->status() === 404
            || $errorStatus === 'UNREGISTERED'
            || ($response->status() === 400 && str_contains(strtolower($errorMessage), 'registration token'))) {
            throw new InvalidPushTokenException('Token FCM tidak valid atau sudah dicabut.');
        }

        Log::warning('FCM push failed', [
            'device_id' => $device->id,
            'notification_id' => $notification->id,
            'status' => $response->status(),
            'error' => Str::limit((string) $response->body(), 300),
        ]);

        throw new RuntimeException("FCM push failed with HTTP {$response->status()}.");
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function accessToken(array $account): string
    {
        $clientEmail = trim((string) ($account['client_email'] ?? ''));
        $privateKey = str_replace('\\n', "\n", (string) ($account['private_key'] ?? ''));

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('Service account FCM tidak memiliki client_email/private_key.');
        }

        $tokenUri = trim((string) ($account['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
        $cacheKey = 'fcm_access_token:'.sha1($clientEmail.'|'.$tokenUri);

        return Cache::remember($cacheKey, 3300, function () use ($clientEmail, $privateKey, $tokenUri) {
            $now = time();
            $jwt = JWT::encode([
                'iss' => $clientEmail,
                'scope' => self::SCOPE,
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ], $privateKey, 'RS256');

            $response = Http::asForm()
                ->timeout(20)
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('FCM OAuth token request failed', [
                    'status' => $response->status(),
                    'error' => Str::limit((string) $response->body(), 300),
                ]);

                throw new RuntimeException('Gagal mengambil access token FCM.');
            }

            $token = (string) ($response->json('access_token') ?? '');

            if ($token === '') {
                throw new RuntimeException('Access token FCM kosong.');
            }

            return $token;
        });
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function forgetAccessToken(array $account): void
    {
        $clientEmail = trim((string) ($account['client_email'] ?? ''));
        $tokenUri = trim((string) ($account['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
        Cache::forget('fcm_access_token:'.sha1($clientEmail.'|'.$tokenUri));
    }

    /**
     * @return array<string, string>
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

    /**
     * FCM mensyaratkan seluruh nilai data berupa string.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[(string) $key] = is_bool($value)
                ? ($value ? '1' : '0')
                : (is_scalar($value) ? (string) $value : (string) json_encode($value));
        }

        return $normalized;
    }
}
