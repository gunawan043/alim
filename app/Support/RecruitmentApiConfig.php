<?php

namespace App\Support;

/**
 * Konfigurasi API integrasi recruitment — pembungkus tipis di atas
 * IntegrationApiConfig (prefix: recruitment_*).
 */
class RecruitmentApiConfig
{
    public const CRYPT_PREFIX = IntegrationApiConfig::CRYPT_PREFIX;

    public const DEFAULT_HEADER = 'X-ALIM-RECRUITMENT-KEY';

    public static function enabled(): bool
    {
        return IntegrationApiConfig::enabled('recruitment');
    }

    public static function key(): string
    {
        return IntegrationApiConfig::key('recruitment');
    }

    public static function hasStoredKey(): bool
    {
        return IntegrationApiConfig::hasStoredKey('recruitment');
    }

    public static function header(): string
    {
        return IntegrationApiConfig::header('recruitment');
    }

    /**
     * @return list<string>
     */
    public static function allowedIps(): array
    {
        return IntegrationApiConfig::allowedIps('recruitment');
    }

    public static function storeKey(string $plainKey): void
    {
        IntegrationApiConfig::storeKey('recruitment', $plainKey);
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
