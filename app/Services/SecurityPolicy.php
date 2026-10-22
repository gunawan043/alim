<?php

namespace App\Services;

/**
 * Central accessor untuk kebijakan keamanan yang diatur Super Admin.
 *
 * Nilai default sengaja mempertahankan perilaku lama bila setting belum ada,
 * sehingga perubahan skema/settings tidak tiba-tiba mengunci pengguna.
 */
class SecurityPolicy
{
    public static function otpExpiryMinutes(): int
    {
        return self::integerSetting('otp_expiry_minutes', 10, 1, 60);
    }

    public static function passwordMinLength(): int
    {
        return self::integerSetting('password_min_length', 8, 8, 64);
    }

    public static function twoFactorScope(): string
    {
        $scope = (string) setting('security_2fa_scope', 'all');

        return in_array($scope, ['all', 'super_admin'], true) ? $scope : 'all';
    }

    public static function mobileTwoFactorEnabled(): bool
    {
        return (string) setting('security_2fa_mobile_enabled', '0') === '1';
    }

    public static function maxLoginAttempts(): int
    {
        return self::integerSetting('max_login_attempts', 9, 1, 20);
    }

    public static function lockoutMinutes(): int
    {
        return self::integerSetting('lockout_duration_minutes', 1440, 1, 1440);
    }

    public static function sessionLifetimeMinutes(): int
    {
        return self::integerSetting(
            'session_lifetime_minutes',
            (int) config('session.lifetime', 120),
            1,
            10080,
        );
    }

    /**
     * Peringatan "akun mungkin diambil alih" dikirim sebelum akun terkunci.
     * Untuk batas percobaan kecil, jangan sampai ambangnya melewati batas lock.
     */
    public static function compromisedThreshold(): int
    {
        return max(1, min(6, self::maxLoginAttempts()));
    }

    private static function integerSetting(string $key, int $default, int $min, int $max): int
    {
        $value = setting($key, $default);
        $value = is_numeric($value) ? (int) $value : $default;

        return self::clamp($value > 0 ? $value : $default, $min, $max);
    }

    private static function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
