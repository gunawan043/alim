<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi Apple Push Notification service (token-based auth).
 *
 * Private key .p8 disimpan terenkripsi. Key ID, Team ID, bundle ID, dan
 * environment dapat diatur dari Super Admin.
 */
class ApnsConfig
{
    public static function keyId(): string
    {
        return trim((string) (setting('apns_key_id', '') ?: config('services.apns.key_id', '')));
    }

    public static function teamId(): string
    {
        return trim((string) (setting('apns_team_id', '') ?: config('services.apns.team_id', '')));
    }

    public static function bundleId(): string
    {
        return trim((string) (setting('apns_bundle_id', '') ?: config('services.apns.bundle_id', '')));
    }

    public static function environment(): string
    {
        $environment = strtolower(trim((string) (setting('apns_environment', '') ?: config('services.apns.environment', 'production'))));

        return $environment === 'sandbox' ? 'sandbox' : 'production';
    }

    public static function privateKey(): string
    {
        $raw = trim((string) setting('apns_private_key', ''));

        if ($raw === '') {
            $raw = trim((string) config('services.apns.private_key', ''));
        }

        if ($raw === '') {
            $path = trim((string) config('services.apns.private_key_path', ''));

            if ($path !== '' && is_readable($path)) {
                $raw = (string) file_get_contents($path);
            }
        }

        if ($raw === '') {
            return '';
        }

        $key = self::decrypt($raw);

        // Dukung key yang ditempel sebagai satu baris dengan \n literal.
        return str_replace('\\n', "\n", trim($key));
    }

    public static function isConfigured(): bool
    {
        return self::keyId() !== ''
            && self::teamId() !== ''
            && self::bundleId() !== ''
            && self::privateKey() !== '';
    }

    public static function baseUrl(): string
    {
        return self::environment() === 'sandbox'
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';
    }

    public static function hasStoredPrivateKey(): bool
    {
        return trim((string) setting('apns_private_key', '')) !== '';
    }

    public static function storedPrivateKeyIsEncrypted(): bool
    {
        return IntegrationApiConfig::isEncrypted((string) setting('apns_private_key', ''));
    }

    public static function storeKeyId(?string $keyId): void
    {
        self::storeSetting('apns_key_id', $keyId);
    }

    public static function storeTeamId(?string $teamId): void
    {
        self::storeSetting('apns_team_id', $teamId);
    }

    public static function storeBundleId(?string $bundleId): void
    {
        self::storeSetting('apns_bundle_id', $bundleId);
    }

    public static function storeEnvironment(?string $environment): void
    {
        self::storeSetting('apns_environment', $environment === 'sandbox' ? 'sandbox' : 'production');
    }

    public static function storePrivateKey(string $privateKey): void
    {
        SystemSetting::set(
            'apns_private_key',
            IntegrationApiConfig::CRYPT_PREFIX.Crypt::encryptString(trim($privateKey)),
            'push'
        );

        Cache::forget('system_settings_map');
    }

    public static function clearPrivateKey(): void
    {
        SystemSetting::where('key', 'apns_private_key')->delete();
        Cache::forget('system_settings_map');
    }

    private static function storeSetting(string $key, ?string $value): void
    {
        $value = trim((string) $value);

        SystemSetting::set($key, $value !== '' ? $value : null, 'push');
        Cache::forget('system_settings_map');
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
