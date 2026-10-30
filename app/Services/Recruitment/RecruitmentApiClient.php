<?php

namespace App\Services\Recruitment;

use App\Support\RecruitmentOutboundConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client API aplikasi recruitment (arah keluar ALIM → recruitment).
 *
 * Semua request memakai Bearer token dari RecruitmentOutboundConfig.
 * Token tidak pernah ditulis ke log. Kegagalan mengembalikan null/array kosong
 * agar pemanggil (ATS, dokumen, notifikasi) dapat memakai fallback.
 *
 * Kontrak endpoint yang dipakai/diharapkan:
 *  - GET  /api/health | /api/ping              (tes koneksi)
 *  - GET  /api/applications                     (daftar lamaran)
 *  - GET  /api/applications/{id}                (detail lamaran)
 *  - PUT  /api/applications/{id}/status         (ubah status)
 *  - GET  /api/applications/{id}/nilai          (nilai seleksi)
 *  - PUT  /api/applications/{id}/nilai          (simpan nilai)
 *  - GET  /api/profiles                         (daftar pelamar)
 *  - GET  /api/profiles/{id}                    (detail pelamar)
 *  - GET  /api/profiles/{id}/documents          (dokumen)
 *  - GET  /api/profiles/{id}/documents/{jenis}  (URL dokumen)
 *  - GET  /api/profiles/{id}/photo              (URL foto)
 *  - POST /api/profiles/{id}/applications       (buat lamaran)
 *  - GET  /api/jobs                             (lowongan)
 *  - POST /api/notifications/send               (notifikasi)
 *  - POST /api/notifications/send-bulk          (notifikasi massal)
 */
class RecruitmentApiClient
{
    private const TIMEOUT_SECONDS = 15;

    public function isConfigured(): bool
    {
        return RecruitmentOutboundConfig::isConfigured();
    }

    /**
     * Tes koneksi: coba /api/health lalu /api/ping.
     *
     * @return array{ok: bool, status: int, message: string, data: array<string, mixed>|null}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'status' => 0,
                'message' => 'URL atau token API recruitment belum dikonfigurasi.',
                'data' => null,
            ];
        }

        foreach (['/api/health', '/api/ping'] as $path) {
            $response = $this->http()?->get($path);

            if ($response && ($response->successful() || $response->status() === 404)) {
                if ($response->successful()) {
                    return [
                        'ok' => true,
                        'status' => $response->status(),
                        'message' => 'Koneksi ke aplikasi recruitment berhasil.',
                        'data' => is_array($response->json()) ? $response->json() : null,
                    ];
                }

                // 404 pada /api/health wajar bila endpoint itu tidak ada — coba /api/ping.
                continue;
            }

            if ($response) {
                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'message' => 'Aplikasi recruitment menolak koneksi (HTTP '.$response->status().').',
                    'data' => null,
                ];
            }
        }

        return [
            'ok' => false,
            'status' => 0,
            'message' => 'Tidak dapat terhubung ke aplikasi recruitment. Periksa URL/jaringan.',
            'data' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function applications(array $query = []): array
    {
        return $this->dataList($this->request('get', '/api/applications', [], $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function application(string|int $id): ?array
    {
        return $this->dataItem($this->request('get', "/api/applications/{$id}"));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function profiles(array $query = []): array
    {
        return $this->dataList($this->request('get', '/api/profiles', [], $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(string|int $id): ?array
    {
        return $this->dataItem($this->request('get', "/api/profiles/{$id}"));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jobs(array $query = []): array
    {
        return $this->dataList($this->request('get', '/api/jobs', [], $query));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function documents(string|int $profileId): array
    {
        return $this->dataList($this->request('get', "/api/profiles/{$profileId}/documents"));
    }

    public function photoUrl(string|int $profileId): ?string
    {
        $response = $this->request('get', "/api/profiles/{$profileId}/photo");

        return is_array($response) && ! empty($response['url']) ? (string) $response['url'] : null;
    }

    public function documentUrl(string|int $profileId, string $jenis): ?string
    {
        $response = $this->request('get', "/api/profiles/{$profileId}/documents/{$jenis}");

        return is_array($response) && ! empty($response['url']) ? (string) $response['url'] : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationNilai(string|int $applicationId): array
    {
        return $this->dataItem($this->request('get', "/api/applications/{$applicationId}/nilai")) ?? [];
    }

    public function pushApplicationNilai(string|int $applicationId, array $nilai): bool
    {
        return $this->request('put', "/api/applications/{$applicationId}/nilai", $nilai) !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function syncApplication(string|int $profileId, array $data): ?array
    {
        return $this->dataItem($this->request('post', "/api/profiles/{$profileId}/applications", $data));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function updateApplicationStatus(string|int $applicationId, string $status, array $extra = []): ?array
    {
        return $this->dataItem($this->request('put', "/api/applications/{$applicationId}/status", array_merge([
            'status' => $status,
        ], $extra)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sendNotification(string $type, string|int $userId, array $payload = []): ?array
    {
        return $this->request('post', '/api/notifications/send', [
            'type' => $type,
            'user_id' => $userId,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<int, string|int>  $userIds
     * @return array<string, mixed>|null
     */
    public function sendBulkNotifications(string $type, array $userIds, array $payload = []): ?array
    {
        return $this->request('post', '/api/notifications/send-bulk', [
            'type' => $type,
            'user_ids' => $userIds,
            'payload' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, array $data = [], array $query = []): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $request = $this->http();

            if (! $request) {
                return null;
            }

            $response = match (strtolower($method)) {
                'get' => $request->get($path, $query),
                'post' => $request->post($path, $data),
                'put' => $request->put($path, $data),
                'patch' => $request->patch($path, $data),
                'delete' => $request->delete($path, $data),
                default => null,
            };

            if (! $response) {
                return null;
            }

            if ($response->successful()) {
                $json = $response->json();

                return is_array($json) ? $json : [];
            }

            Log::warning('RecruitmentApiClient: request gagal', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('RecruitmentApiClient: exception', [
                'method' => strtoupper($method),
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function http(): ?PendingRequest
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Http::baseUrl(RecruitmentOutboundConfig::baseUrl())
            ->withToken((string) RecruitmentOutboundConfig::token())
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(2, 200, throw: false);
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array<int, array<string, mixed>>
     */
    private function dataList(?array $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (isset($response['data']) && is_array($response['data'])) {
            return array_values(array_filter($response['data'], 'is_array'));
        }

        return array_values(array_filter($response, 'is_array'));
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array<string, mixed>|null
     */
    private function dataItem(?array $response): ?array
    {
        if (! is_array($response)) {
            return null;
        }

        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        return $response;
    }
}
