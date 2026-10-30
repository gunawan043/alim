<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Konfigurasi Firebase Cloud Messaging (HTTP v1).
 *
 * Service account JSON disimpan terenkripsi di system_settings dan dapat
 * diatur dari Super Admin. Fallback ke config/services.php untuk deployment
 * berbasis environment.
 */
class FcmConfig
{
    public static function projectId(): string
    {
        $stored = trim((string) setting('fcm_project_id', ''));

        if ($stored !== '') {
            return $stored;
        }

        $json = self::serviceAccount();
        $fromJson = trim((string) ($json['project_id'] ?? ''));

        if ($fromJson !== '') {
            return $fromJson;
        }

        return trim((string) config('services.fcm.project_id', ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function serviceAccount(): ?array
    {
        $raw = trim((string) setting('fcm_service_account_json', ''));

        if ($raw === '') {
            $raw = trim((string) config('services.fcm.service_account_json', ''));
        }

        if ($raw === '') {
            $path = trim((string) config('services.fcm.service_account_path', ''));

            if ($path !== '' && is_readable($path)) {
                $raw = (string) file_get_contents($path);
            }
        }

        if ($raw === '') {
            return null;
        }

        $raw = self::decrypt($raw);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function isConfigured(): bool
    {
        $account = self::serviceAccount();

        return self::projectId() !== ''
            && is_array($account)
            && ! empty($account['client_email'])
            && ! empty($account['private_key']);
    }

    public static function hasStoredServiceAccount(): bool
    {
        return trim((string) setting('fcm_service_account_json', '')) !== '';
    }

    public static function storedServiceAccountIsEncrypted(): bool
    {
        return IntegrationApiConfig::isEncrypted((string) setting('fcm_service_account_json', ''));
    }

    public static function hasStoredProjectId(): bool
    {
        return trim((string) setting('fcm_project_id', '')) !== '';
    }

    public static function storeProjectId(?string $projectId): void
    {
        $projectId = trim((string) $projectId);

        SystemSetting::set('fcm_project_id', $projectId !== '' ? $projectId : null, 'push');
        Cache::forget('system_settings_map');
    }

    public static function storeServiceAccountJson(string $json): void
    {
        SystemSetting::set(
            'fcm_service_account_json',
            IntegrationApiConfig::CRYPT_PREFIX.Crypt::encryptString(trim($json)),
            'push'
        );

        Cache::forget('system_settings_map');
    }

    public static function clearServiceAccount(): void
    {
        SystemSetting::where('key', 'fcm_service_account_json')->delete();
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
