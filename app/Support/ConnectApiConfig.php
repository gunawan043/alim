<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Konfigurasi keluar (Core → ALIM Connect) untuk pengiriman pesan WhatsApp.
 *
 * Berbeda arah dengan `ConnectSsoConfig`: kelas itu mengatur key yang
 * **dipakai Connect untuk memanggil Core**, sedangkan kelas ini mengatur
 * alamat dan key yang **dipakai Core untuk memanggil Connect**.
 *
 * Sumber nilai, berurutan: `system_settings` (`connect_*`), lalu
 * `config/services.php` `connect`. Key disimpan terenkripsi dengan APP_KEY
 * lewat `IntegrationApiConfig` (prefix `connect_api`, header `X-API-Key`).
 */
class ConnectApiConfig
{
    /** Prefix kredensial untuk `IntegrationApiConfig`. */
    public const CREDENTIAL_PREFIX = 'connect';

    /** Header yang diminta Connect pada setiap request API. */
    public const KEY_HEADER = 'X-API-Key';

    public static function baseUrl(): string
    {
        $stored = self::setting('base_url');

        return rtrim(trim($stored ?? (string) config('services.connect.base_url', '')), '/');
    }

    public static function sessionId(): string
    {
        return trim(self::setting('session_id')
            ?? (string) config('services.connect.session_id', ''));
    }

    public static function apiKey(): string
    {
        return IntegrationApiConfig::key(self::CREDENTIAL_PREFIX);
    }

    public static function hasStoredKey(): bool
    {
        return IntegrationApiConfig::hasStoredKey(self::CREDENTIAL_PREFIX);
    }

    public static function storeKey(string $plainKey): void
    {
        IntegrationApiConfig::storeKey(self::CREDENTIAL_PREFIX, $plainKey);
        Cache::forget('system_settings_map');
    }

    /**
     * Hapus key tersimpan sehingga `apiKey()` kembali membaca fallback
     * `config/services.php`. Baris dihapus, bukan diisi string kosong —
     * alasan yang sama dengan `ConnectSsoConfig::forgetKey()`.
     */
    public static function forgetKey(): void
    {
        SystemSetting::query()->where('key', 'connect_api_key')->delete();
        Cache::forget('system_settings_map');
    }

    public static function storeBaseUrl(?string $url): void
    {
        self::put('base_url', rtrim(trim((string) $url), '/'));
    }

    public static function storeSessionId(?string $id): void
    {
        self::put('session_id', trim((string) $id));
    }

    /**
     * Kembalikan origin (scheme://host[:port]) dari base URL, atau null bila
     * tidak bisa dipakai. Aturan sama dengan `ConnectSsoConfig::originOf()`:
     * hanya http/https, dan wajib https di production.
     */
    public static function originOf(string $url): ?string
    {
        return ConnectSsoConfig::originOf($url);
    }

    /**
     * Hal yang wajib ada sebelum uji kirim bisa dijalankan. Dikembalikan
     * sebagai daftar supaya halaman bisa menyebut **semua** yang kurang
     * sekaligus, bukan satu per satu di setiap percobaan.
     *
     * @return list<string>
     */
    public static function missing(): array
    {
        $missing = [];

        if (self::baseUrl() === '') {
            $missing[] = 'Base URL Connect belum diisi.';
        } elseif (self::originOf(self::baseUrl()) === null) {
            $missing[] = 'Base URL Connect bukan URL https yang valid.';
        }

        if (self::sessionId() === '') {
            $missing[] = 'Session ID belum diisi.';
        }

        if (self::apiKey() === '') {
            $missing[] = 'API key Connect (role OPERATOR) belum diisi.';
        }

        return $missing;
    }

    /**
     * Siapkan nomor Indonesia menjadi JID Connect (`628xxx@c.us`).
     * Nomor yang sudah berbentuk JID dikembalikan apa adanya.
     */
    public static function jid(string $phone): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        if (str_contains($phone, '@')) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits.'@c.us';
    }

    private static function put(string $field, string $value): void
    {
        SystemSetting::set('connect_'.$field, $value, 'integration');
        Cache::forget('system_settings_map');
    }

    /**
     * Tiga keadaan seperti `ConnectSsoConfig`: null = belum pernah disimpan
     * (pakai fallback config), '' = sengaja dikosongkan operator.
     */
    private static function setting(string $field): ?string
    {
        $row = SystemSetting::query()
            ->where('key', 'connect_'.$field)
            ->first();

        return $row === null ? null : (string) $row->value;
    }
}
