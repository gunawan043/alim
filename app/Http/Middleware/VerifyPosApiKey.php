<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the POS / Kasir integration API (prefix: /api/pos/v1).
 *
 * Configuration is read from the database (system_settings, managed from
 * the Super Admin → Pengaturan Sistem → Integrasi POS page) and falls back
 * to config/services.php (env) when a setting has never been saved.
 *
 * Defence layers:
 *  1. Integration can be disabled from the admin page (503).
 *  2. Secret must be configured (DB or env) — otherwise 503.
 *  3. Optional IP allowlist for the POS server(s), exact IPs or CIDR (403).
 *  4. Key delivered via a non-generic custom header (default X-ALIM-POS-KEY)
 *     or `Authorization: Bearer`, compared in constant time (401).
 */
class VerifyPosApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled()) {
            return $this->error('Integrasi POS sedang dinonaktifkan.', 503);
        }

        $configuredKey = $this->resolveApiKey();

        if ($configuredKey === '') {
            return $this->error('Integrasi POS belum dikonfigurasi pada server.', 503);
        }

        $allowedIps = $this->resolveAllowedIps();

        if ($allowedIps !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowedIps)) {
            Log::warning('POS API: request ditolak karena IP tidak diizinkan.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->error('Alamat IP tidak diizinkan mengakses API ini.', 403);
        }

        $headerName = $this->resolveHeaderName();
        $providedKey = $request->header($headerName) ?? $request->bearerToken();

        if (! is_string($providedKey) || $providedKey === '' || ! hash_equals($configuredKey, trim($providedKey))) {
            Log::warning('POS API: request ditolak karena API key tidak valid.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->error('API key tidak valid atau tidak dikirim.', 401);
        }

        return $next($request);
    }

    private function isEnabled(): bool
    {
        $enabled = setting('pos_api_enabled');

        if ($enabled === null || $enabled === '') {
            return true;
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    private function resolveApiKey(): string
    {
        $fromSettings = trim((string) setting('pos_api_key'));

        if ($fromSettings !== '') {
            return $fromSettings;
        }

        return trim((string) config('services.pos.api_key'));
    }

    private function resolveHeaderName(): string
    {
        $fromSettings = trim((string) setting('pos_api_header'));

        if ($fromSettings !== '') {
            return $fromSettings;
        }

        $fromConfig = trim((string) config('services.pos.header'));

        return $fromConfig !== '' ? $fromConfig : 'X-ALIM-POS-KEY';
    }

    /**
     * @return list<string>
     */
    private function resolveAllowedIps(): array
    {
        $fromSettings = trim((string) setting('pos_api_allowed_ips'));

        if ($fromSettings !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $fromSettings))));
        }

        return array_values((array) config('services.pos.allowed_ips', []));
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
            'errors' => null,
        ], $status);
    }
}
