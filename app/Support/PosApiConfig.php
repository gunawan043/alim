<?php

namespace App\Support;

/**
 * Konfigurasi API POS — pembungkus tipis di atas IntegrationApiConfig
 * agar pemanggil lama (middleware, controller, view, test) tidak berubah.
 */
class PosApiConfig
{
    public const CRYPT_PREFIX = IntegrationApiConfig::CRYPT_PREFIX;

    public const DEFAULT_HEADER = 'X-ALIM-POS-KEY';

    public static function enabled(): bool
    {
        return IntegrationApiConfig::enabled('pos');
    }

    public static function key(): string
    {
        return IntegrationApiConfig::key('pos');
    }

    public static function hasStoredKey(): bool
    {
        return IntegrationApiConfig::hasStoredKey('pos');
    }

    public static function header(): string
    {
        return IntegrationApiConfig::header('pos');
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        return IntegrationApiConfig::allowedIps('pos');
    }

    public static function storeKey(string $plainKey): void
    {
        IntegrationApiConfig::storeKey('pos', $plainKey);
    }

    public static function generateKey(): string
    {
        return IntegrationApiConfig::generateKey();
    }

    public static function isEncrypted(?string $value): bool
    {
        return IntegrationApiConfig::isEncrypted($value);
    }
}
