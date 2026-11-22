<?php

declare(strict_types=1);

namespace App\Support\Qr;

use App\Models\SystemSetting;
use Throwable;

/**
 * Sumber tunggal pengaturan logo QR.
 *
 * Nilai tersimpan di tabel system_settings (tab QR) dan menimpa default
 * di config/qr.php. Hasilnya di-cache per request supaya renderer tidak
 * menembak database untuk setiap QR yang digambar.
 */
final class QrBranding
{
    private static ?array $settings = null;

    public static function enabled(): bool
    {
        $value = self::setting('qr_logo_enabled');

        if ($value === null || $value === '') {
            return (bool) config('qr.logo.enabled', true);
        }

        return (bool) (int) $value;
    }

    /**
     * Path absolut logo yang akan digambar, atau null bila tidak ada.
     */
    public static function logoPath(): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $custom = trim((string) self::setting('qr_logo_path', ''));

        if ($custom !== '') {
            $path = self::storageDir().'/'.basename($custom);

            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        $fallback = (string) config('qr.logo.path', '');

        if ($fallback !== '' && is_file($fallback) && is_readable($fallback)) {
            return $fallback;
        }

        return null;
    }

    /**
     * URL logo untuk pratinjau di halaman pengaturan.
     */
    public static function logoUrl(): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        $custom = trim((string) self::setting('qr_logo_path', ''));
        $customPath = $custom !== '' ? self::storageDir().'/'.basename($custom) : null;

        if ($customPath && is_file($customPath)) {
            return route('qr.logo', ['v' => filemtime($customPath)]);
        }

        $fallback = (string) config('qr.logo.path', '');
        $publicRoot = rtrim(public_path(), '/');

        if ($fallback !== '' && is_file($fallback) && str_starts_with($fallback, $publicRoot)) {
            $relative = ltrim(str_replace('\\', '/', substr($fallback, strlen($publicRoot))), '/');

            return asset($relative);
        }

        return null;
    }

    public static function ratio(): float
    {
        $value = self::setting('qr_logo_ratio');
        $ratio = is_numeric($value) ? (float) $value : (float) config('qr.logo.ratio', 0.22);

        return max(
            (float) config('qr.logo.min_ratio', 0.12),
            min((float) config('qr.logo.max_ratio', 0.28), $ratio)
        );
    }

    public static function storageDir(): string
    {
        return storage_path('app/qr-logo');
    }

    public static function forget(): void
    {
        self::$settings = null;
    }

    private static function setting(string $key, ?string $default = null): ?string
    {
        if (self::$settings === null) {
            try {
                self::$settings = SystemSetting::query()->pluck('value', 'key')->all();
            } catch (Throwable) {
                self::$settings = [];
            }
        }

        $value = self::$settings[$key] ?? $default;

        return $value === null ? null : (string) $value;
    }
}
