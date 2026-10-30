<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi arah keluar ALIM → aplikasi recruitment.
 *
 * URL dan token dapat diatur dari halaman Integrasi Recruitment (disimpan di
 * system_settings) dan fallback ke config/services.php (`recruitment_api`).
 * Token disimpan terenkripsi dengan prefix `enc:v1:`.
 */
class RecruitmentOutboundConfig
{
    public static function baseUrl(): string
    {
        $stored = trim((string) setting('recruitment_outbound_url'));

        $url = $stored !== ''
            ? $stored
            : (string) config('services.recruitment_api.url', '');

        return self::normalizeBaseUrl($url);
    }

    public static function token(): ?string
    {
        $raw = trim((string) setting('recruitment_outbound_token'));

        if ($raw !== '') {
            $decrypted = self::decrypt($raw);

            return $decrypted !== '' ? $decrypted : null;
        }

        $fallback = trim((string) config('services.recruitment_api.token', ''));

        return $fallback !== '' ? $fallback : null;
    }

    public static function hasStoredToken(): bool
    {
        return trim((string) setting('recruitment_outbound_token')) !== '';
    }

    public static function storedTokenIsEncrypted(): bool
    {
        return IntegrationApiConfig::isEncrypted((string) setting('recruitment_outbound_token'));
    }

    public static function isConfigured(): bool
    {
        return self::baseUrl() !== '' && self::token() !== null;
    }

    public static function storeUrl(?string $url): void
    {
        $url = trim((string) $url);

        SystemSetting::set('recruitment_outbound_url', $url !== '' ? $url : null, 'recruitment');
    }

    public static function storeToken(string $plainToken): void
    {
        SystemSetting::set(
            'recruitment_outbound_token',
            IntegrationApiConfig::CRYPT_PREFIX.Crypt::encryptString($plainToken),
            'recruitment'
        );
    }

    public static function clearToken(): void
    {
        SystemSetting::where('key', 'recruitment_outbound_token')->delete();
    }

    /**
     * Normalisasi base URL: buang slash akhir dan akhiran "/api" agar
     * pemanggil bisa konsisten menambahkan "/api/...".
     */
    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        if ($url === '') {
            return '';
        }

        return preg_replace('#/api$#i', '', $url) ?? $url;
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
