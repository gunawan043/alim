<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Minishlink\WebPush\VAPID;

/**
 * Konfigurasi Web Push VAPID untuk browser.
 *
 * Public key boleh diketahui browser. Private key disimpan terenkripsi dan
 * hanya dipakai backend untuk menandatangani request push.
 */
class WebPushConfig
{
    public static function vapidPublicKey(): string
    {
        $stored = trim((string) setting('web_push_vapid_public_key', ''));

        if ($stored !== '') {
            return $stored;
        }

        return trim((string) config('services.web_push.vapid_public_key', ''));
    }

    public static function vapidPrivateKey(): string
    {
        $stored = trim((string) setting('web_push_vapid_private_key', ''));

        if ($stored !== '') {
            return self::decrypt($stored);
        }

        return trim((string) config('services.web_push.vapid_private_key', ''));
    }

    public static function subject(): string
    {
        $stored = trim((string) setting('web_push_vapid_subject', ''));

        if ($stored !== '') {
            return $stored;
        }

        $fallback = trim((string) config('services.web_push.vapid_subject', ''));

        if ($fallback !== '') {
            return $fallback;
        }

        $email = trim((string) setting('app_email', config('mail.from.address', 'admin@alim.local')));

        return 'mailto:'.($email !== '' ? $email : 'admin@alim.local');
    }

    public static function isConfigured(): bool
    {
        return self::vapidPublicKey() !== ''
            && self::vapidPrivateKey() !== ''
            && self::subject() !== '';
    }

    public static function hasStoredPublicKey(): bool
    {
        return trim((string) setting('web_push_vapid_public_key', '')) !== '';
    }

    public static function hasStoredPrivateKey(): bool
    {
        return trim((string) setting('web_push_vapid_private_key', '')) !== '';
    }

    public static function storedPrivateKeyIsEncrypted(): bool
    {
        return IntegrationApiConfig::isEncrypted((string) setting('web_push_vapid_private_key', ''));
    }

    public static function publicKeyFromEnv(): bool
    {
        return ! self::hasStoredPublicKey()
            && trim((string) config('services.web_push.vapid_public_key', '')) !== '';
    }

    /**
     * Generate VAPID key pair baru. Memanggil ini akan mengganti key lama;
     * subscription browser lama perlu mendaftar ulang.
     *
     * @return array{publicKey:string,privateKey:string}
     */
    public static function generateKeys(): array
    {
        $keys = VAPID::createVapidKeys();

        SystemSetting::set('web_push_vapid_public_key', $keys['publicKey'], 'push');
        SystemSetting::set(
            'web_push_vapid_private_key',
            IntegrationApiConfig::CRYPT_PREFIX.Crypt::encryptString($keys['privateKey']),
            'push'
        );

        // Subscription lama memakai VAPID key lama dan tidak akan valid lagi.
        WebPushSubscription::query()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        Cache::forget('system_settings_map');

        return $keys;
    }

    public static function storeSubject(?string $subject): void
    {
        $subject = trim((string) $subject);

        SystemSetting::set('web_push_vapid_subject', $subject !== '' ? $subject : null, 'push');
        Cache::forget('system_settings_map');
    }

    public static function clearKeys(): void
    {
        SystemSetting::whereIn('key', ['web_push_vapid_public_key', 'web_push_vapid_private_key'])->delete();
        WebPushSubscription::query()->whereNull('revoked_at')->update(['revoked_at' => now()]);
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
