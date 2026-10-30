<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Koneksi ALIM → API pengadaan POS.
 * Nilai disimpan di system_settings dan token dienkripsi dengan APP_KEY.
 */
class PosProcurementConfig
{
    public static function enabled(): bool
    {
        $value = setting('pos_procurement_enabled');

        if ($value === null || $value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function baseUrl(): string
    {
        $stored = trim((string) setting('pos_procurement_base_url'));
        $url = $stored !== '' ? $stored : (string) config('services.pos_procurement.base_url', '');

        return self::normalizeBaseUrl($url);
    }

    public static function token(): ?string
    {
        $raw = trim((string) setting('pos_procurement_api_key'));

        if ($raw !== '') {
            $token = self::decrypt($raw);

            return $token !== '' ? $token : null;
        }

        $fallback = trim((string) config('services.pos_procurement.api_key', ''));

        return $fallback !== '' ? $fallback : null;
    }

    public static function hasStoredToken(): bool
    {
        return trim((string) setting('pos_procurement_api_key')) !== '';
    }

    public static function tokenIsEncrypted(): bool
    {
        return IntegrationApiConfig::isEncrypted((string) setting('pos_procurement_api_key'));
    }

    public static function header(): string
    {
        $stored = trim((string) setting('pos_procurement_header'));

        return $stored !== ''
            ? $stored
            : (string) config('services.pos_procurement.header', 'X-POS-PROCUREMENT-KEY');
    }

    public static function timeout(): int
    {
        return max(1, (int) config('services.pos_procurement.timeout', 15));
    }

    public static function verifySsl(): bool
    {
        return (bool) config('services.pos_procurement.verify_ssl', true);
    }

    public static function isConfigured(): bool
    {
        return self::baseUrl() !== '' && self::token() !== null;
    }

    public static function storeEnabled(bool $enabled): void
    {
        SystemSetting::set('pos_procurement_enabled', $enabled ? '1' : '0', 'pos_procurement');
        self::forgetCache();
    }

    public static function storeBaseUrl(?string $url): void
    {
        $url = trim((string) $url);
        SystemSetting::set('pos_procurement_base_url', $url !== '' ? $url : null, 'pos_procurement');
        self::forgetCache();
    }

    public static function storeHeader(?string $header): void
    {
        $header = trim((string) $header);
        SystemSetting::set('pos_procurement_header', $header !== '' ? $header : null, 'pos_procurement');
        self::forgetCache();
    }

    public static function storeToken(string $token): void
    {
        SystemSetting::set(
            'pos_procurement_api_key',
            IntegrationApiConfig::CRYPT_PREFIX.Crypt::encryptString($token),
            'pos_procurement'
        );
        self::forgetCache();
    }

    public static function clearToken(): void
    {
        SystemSetting::where('key', 'pos_procurement_api_key')->delete();
        self::forgetCache();
    }

    private static function forgetCache(): void
    {
        Cache::forget('system_settings');
        Cache::forget('system_settings_map');
    }

    public static function endpoint(string $path): string
    {
        $base = self::baseUrl();
        $suffix = '/api/procurement/v1';

        if ($base === '') {
            return '';
        }

        if (str_ends_with(strtolower($base), $suffix)) {
            $base = substr($base, 0, -strlen($suffix));
        }

        return rtrim($base, '/').'/api/procurement/v1/'.ltrim($path, '/');
    }

    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        return preg_replace('#/api/procurement/v1$#i', '', $url) ?? $url;
    }

    private static function decrypt(string $value): string
    {
        if (! IntegrationApiConfig::isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(IntegrationApiConfig::CRYPT_PREFIX)));
        } catch (\Throwable) {
            return '';
        }
    }
}
