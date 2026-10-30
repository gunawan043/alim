<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi generik untuk API integrasi antar-aplikasi (POS, recruitment, ...).
 *
 * Nilai disimpan di `system_settings` dengan prefix tertentu (mis. `pos_*`,
 * `recruitment_*`) dan fallback ke config/services.php. Token dienkripsi
 * dengan APP_KEY dan disimpan dengan prefix `enc:v1:`.
 */
class IntegrationApiConfig
{
    public const CRYPT_PREFIX = 'enc:v1:';

    public static function enabled(string $prefix): bool
    {
        $value = self::setting($prefix, 'enabled');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function key(string $prefix): string
    {
        $stored = trim((string) self::setting($prefix, 'api_key'));

        if ($stored !== '') {
            return self::decrypt($stored);
        }

        return trim((string) config("services.{$prefix}.api_key"));
    }

    public static function hasStoredKey(string $prefix): bool
    {
        return trim((string) self::setting($prefix, 'api_key')) !== '';
    }

    public static function header(string $prefix): string
    {
        $stored = trim((string) self::setting($prefix, 'header'));

        if ($stored !== '') {
            return $stored;
        }

        $fromConfig = trim((string) config("services.{$prefix}.header"));

        return $fromConfig !== '' ? $fromConfig : 'X-ALIM-'.strtoupper($prefix).'-KEY';
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(string $prefix): array
    {
        $stored = self::setting($prefix, 'allowed_ips');

        if ($stored !== null) {
            $stored = trim($stored);

            return $stored === ''
                ? []
                : array_values(array_filter(array_map('trim', explode(',', $stored))));
        }

        return array_values((array) config("services.{$prefix}.allowed_ips", []));
    }

    public static function storeKey(string $prefix, string $plainKey): void
    {
        SystemSetting::set(
            "{$prefix}_api_key",
            self::CRYPT_PREFIX.Crypt::encryptString($plainKey),
            'integration'
        );
    }

    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::CRYPT_PREFIX);
    }

    private static function decrypt(string $value): string
    {
        if (! self::isEncrypted($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, strlen(self::CRYPT_PREFIX)));
        } catch (\Throwable) {
            return '';
        }
    }

    private static function setting(string $prefix, string $field): ?string
    {
        $key = match ($field) {
            'api_key' => "{$prefix}_api_key",
            'header' => "{$prefix}_api_header",
            'allowed_ips' => "{$prefix}_api_allowed_ips",
            'enabled' => "{$prefix}_api_enabled",
            default => "{$prefix}_api_{$field}",
        };

        $value = setting($key);

        return $value === null ? null : (string) $value;
    }
}
