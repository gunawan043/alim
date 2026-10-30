<?php

namespace App\Http\Middleware;

use App\Support\IntegrationApiConfig;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard umum untuk API integrasi antar-aplikasi (POS, recruitment, ...).
 *
 * Subclass menentukan prefix konfigurasi (`pos`, `recruitment`) yang dipakai
 * untuk membaca token, header, IP allowlist, dan status aktif.
 *
 * Lapisan keamanan: HTTPS di produksi, disable switch, IP allowlist opsional,
 * token di header khusus/Bearer dengan perbandingan konstan-waktu, rate limit
 * percobaan gagal, audit log tanpa menulis token, dan respons no-store.
 */
abstract class VerifyIntegrationApiKey
{
    private const AUTH_FAIL_DECAY_SECONDS = 60;

    /**
     * Prefix config/services.php dan system_settings (mis. "pos").
     */
    abstract protected function configPrefix(): string;

    /**
     * Nama integrasi untuk pesan/log (mis. "POS").
     */
    abstract protected function integrationName(): string;

    public function handle(Request $request, Closure $next): Response
    {
        $name = $this->integrationName();
        $prefix = $this->configPrefix();

        if ($this->shouldForceHttps($prefix) && ! $request->isSecure()) {
            return $this->error("API {$name} hanya dapat diakses melalui HTTPS.", 403);
        }

        $throttleKey = "{$prefix}-api-auth:".$request->ip();
        $maxAttempts = (int) config("services.{$prefix}.auth_fail_limit", 10);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $retryAfter = max(1, RateLimiter::availableIn($throttleKey));

            Log::warning("{$name} API: request ditolak karena terlalu banyak percobaan autentikasi.", [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->error(
                "Terlalu banyak percobaan autentikasi. Coba lagi dalam {$retryAfter} detik.",
                429,
                ['Retry-After' => (string) $retryAfter]
            );
        }

        if (! IntegrationApiConfig::enabled($prefix)) {
            return $this->error("Integrasi {$name} sedang dinonaktifkan.", 503);
        }

        $configuredKey = IntegrationApiConfig::key($prefix);

        if ($configuredKey === '') {
            return $this->error("Integrasi {$name} belum dikonfigurasi pada server.", 503);
        }

        $allowedIps = IntegrationApiConfig::allowedIps($prefix);

        if ($allowedIps !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowedIps)) {
            RateLimiter::hit($throttleKey, self::AUTH_FAIL_DECAY_SECONDS);

            Log::warning("{$name} API: request ditolak karena IP tidak diizinkan.", [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->error('Alamat IP tidak diizinkan mengakses API ini.', 403);
        }

        $providedKey = $request->header(IntegrationApiConfig::header($prefix)) ?? $request->bearerToken();

        if (! is_string($providedKey) || $providedKey === '' || ! hash_equals($configuredKey, trim($providedKey))) {
            RateLimiter::hit($throttleKey, self::AUTH_FAIL_DECAY_SECONDS);

            Log::warning("{$name} API: request ditolak karena API key tidak valid.", [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->error('API key tidak valid atau tidak dikirim.', 401);
        }

        RateLimiter::clear($throttleKey);

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    private function shouldForceHttps(string $prefix): bool
    {
        return app()->environment('production')
            && (bool) config("services.{$prefix}.require_https", true);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function error(string $message, int $status, array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
            'errors' => null,
        ], $status, $headers);
    }
}
