<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Konfigurasi WhatsApp Connect SSO di sisi Core.
 *
 * Dua kelompok nilai, dan keduanya boleh berasal dari dua tempat yang
 * berbeda tanpa saling menimpa:
 *
 *  1. **Kredensial & keamanan** (key, header, allowlist IP) mengikuti pola
 *     Integrasi POS/Recruitment lewat `IntegrationApiConfig` dengan prefix
 *     `connect_sso` (`system_settings` lebih dulu, lalu `config/services.php`).
 *     Ini yang dipakai middleware `connect.sso-key`.
 *
 *  2. **Alamat** (URL handoff Connect, origin iframe) dibaca dari
 *     `system_settings` bila ada, lalu jatuh ke `config/services.php`.
 *     Keduanya wajib berupa https di production.
 *
 * Alasan keberadaan kelas ini: tanpa jalur `system_settings`, satu-satunya
 * cara mengaktifkan SSO di production adalah menyunting `.env` di server,
 * yang berarti membuka/menulis berkas rahasia lewat shell — tidak perlu.
 * Dengan jalur ini, operator mengisi nilai di Pengaturan Sistem dan key
 * disimpan terenkripsi dengan APP_KEY.
 */
class ConnectSsoConfig
{
    public const PREFIX = 'connect_sso';

    public const DEFAULT_HEADER = 'X-ALIM-CONNECT-SSO-KEY';

    public const DEFAULT_SCOPES = ['sso:exchange'];

    public const DEFAULT_TTL = 60;

    public static function apiKey(): string
    {
        return IntegrationApiConfig::key(self::PREFIX);
    }

    public static function hasStoredKey(): bool
    {
        return IntegrationApiConfig::hasStoredKey(self::PREFIX);
    }

    public static function storeKey(string $plainKey): void
    {
        IntegrationApiConfig::storeKey(self::PREFIX, $plainKey);

        // `setting()` menyimpan seluruh tabel di cache 5 menit. Tanpa
        // pembersihan, middleware masih memakai key lama sampai cache
        // kedaluwarsa dan operator mengira key baru tidak berlaku.
        Cache::forget('system_settings_map');
    }

    /**
     * Hapus key tersimpan sehingga `apiKey()` kembali membaca
     * `CONNECT_SSO_API_KEY` dari `.env`.
     *
     * Baris dihapus, bukan diisi string kosong: `storeKey('')` menyimpan
     * `enc:v1:` + enkripsi(''), yang tetap non-empty sehingga `key()`
     * menganggapnya ada dan tidak pernah jatuh ke config. Akibatnya
     * fallback `.env` diam-diam mati walau UI menyatakan key dihapus.
     */
    public static function forgetKey(): void
    {
        SystemSetting::query()->where('key', self::PREFIX.'_api_key')->delete();

        Cache::forget('system_settings_map');
    }

    public static function isEncrypted(?string $value): bool
    {
        return IntegrationApiConfig::isEncrypted($value);
    }

    public static function header(): string
    {
        return IntegrationApiConfig::header(self::PREFIX);
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        return IntegrationApiConfig::allowedIps(self::PREFIX);
    }

    /**
     * @return list<string>
     */
    public static function scopes(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            (array) config('services.connect_sso.scopes', self::DEFAULT_SCOPES)
        )));
    }

    /**
     * URL halaman handoff Connect (halaman yang menerima form POST kode).
     */
    public static function handoffUrl(): string
    {
        return trim(self::setting('handoff_url')
            ?? (string) config('services.connect_sso.handoff_url', ''));
    }

    /**
     * Origin Connect yang boleh di-embed Core. Hanya scheme + host + port;
     * jalur dibuang saat disimpan.
     */
    public static function frameOrigin(): string
    {
        return trim(self::setting('frame_origin')
            ?? (string) config('services.connect_sso.frame_origin', ''));
    }

    public static function handoffTtl(): int
    {
        return max(1, (int) config('services.connect_sso.handoff_ttl', self::DEFAULT_TTL));
    }

    public static function storeHandoffUrl(?string $url): void
    {
        SystemSetting::set(
            'connect_sso_handoff_url',
            trim((string) $url),
            'integration'
        );
    }

    public static function storeFrameOrigin(?string $url): void
    {
        SystemSetting::set(
            'connect_sso_frame_origin',
            self::originOf((string) $url) ?? '',
            'integration'
        );
    }

    public static function storeAllowedIps(?string $ips): void
    {
        SystemSetting::set(
            'connect_sso_api_allowed_ips',
            trim((string) $ips),
            'integration'
        );
    }

    /**
     * Kembalikan origin (scheme://host[:port]) dari URL, atau null bila tidak
     * bisa dipakai sebagai origin. Path, query, dan fragment dibuang karena
     * pembanding `Origin` browser tidak pernah memuatnya.
     */
    public static function originOf(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (! in_array($scheme, ['https', 'http'], true)) {
            return null;
        }

        if (app()->environment('production') && $scheme !== 'https') {
            return null;
        }

        return $scheme.'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * Tiga keadaan: null = belum pernah disimpan (pakai fallback config),
     * string kosong = sengaja dikosongkan lewat Pengaturan Sistem.
     */
    private static function setting(string $field): ?string
    {
        $row = SystemSetting::query()
            ->where('key', 'connect_sso_'.$field)
            ->first();

        return $row === null ? null : (string) $row->value;
    }
}
