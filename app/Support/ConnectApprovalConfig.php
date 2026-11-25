<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Konfigurasi ALIM Connect — Approval API di sisi Core.
 *
 * Kelompok ini terpisah dari `ConnectSsoConfig` karena keduanya kredensial
 * yang berbeda dan tidak boleh saling menggantikan: key SSO hanya boleh
 * bertukar kode (`sso:exchange`), key Approval hanya boleh mengirim perintah
 * persetujuan (`approval:command`). Middleware masing-masing menolak key
 * dengan cakupan yang salah.
 *
 * Pola penyimpanan mengikuti `IntegrationApiConfig` (system_settings lebih
 * dulu, lalu `config/services.php`), sehingga operator dapat mengisi nilainya
 * dari Pengaturan Sistem tanpa menyunting `.env` di server.
 */
class ConnectApprovalConfig
{
    public const PREFIX = 'connect_approval';

    public const DEFAULT_HEADER = 'X-ALIM-CONNECT-APPROVAL-KEY';

    public const DEFAULT_SCOPES = ['approval:command'];

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

        Cache::forget('system_settings_map');
    }

    /**
     * Hapus key tersimpan sehingga `apiKey()` kembali membaca
     * `CONNECT_APPROVAL_API_KEY` dari `.env`.
     *
     * Baris dihapus, bukan diisi string kosong: `storeKey('')` menulis
     * `enc:v1:` + enkripsi('') yang tetap non-empty, sehingga fallback
     * `.env` tidak akan pernah dipakai walau UI menyatakan key dihapus.
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
            (array) config('services.connect_approval.scopes', self::DEFAULT_SCOPES)
        )));
    }

    public static function storeAllowedIps(?string $ips): void
    {
        SystemSetting::set(
            'connect_approval_api_allowed_ips',
            trim((string) $ips),
            'integration'
        );

        Cache::forget('system_settings_map');
    }
}
