<?php

namespace App\Support;

use App\Models\Security\SecurityTeamMember;
use App\Models\User;

/**
 * Aturan akses modul Satuan Keamanan.
 *
 * Dipakai bersama oleh middleware, controller, dan sidebar supaya
 * satu sumber kebenaran (tidak ada lagi cek jabatan tersebar).
 */
class SecurityAccess
{
    public const ROLE = 'Satuan Keamanan';

    public const JABATAN_KEPALA = 'Kepala Satuan Keamanan';

    public const JABATAN_KOORDINATOR = 'Koordinator Satuan Keamanan';

    public const JABATAN_ANGGOTA = 'Anggota Satuan Keamanan';

    public static function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->getRoleNames()->contains('Super Admin');
    }

    public static function jabatan(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $jabatan = null;

        if ($user->employment) {
            $jabatan = $user->employment->jabatan;
        }

        if (! $jabatan && method_exists($user, 'jabatan') && $user->jabatan) {
            $jabatan = is_object($user->jabatan)
                ? ($user->jabatan->nama ?? $user->jabatan->name ?? null)
                : $user->jabatan;
        }

        return self::normalize((string) ($jabatan ?? ''));
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\([^)]*\)/', ' ', $value);
        $value = str_replace('&', ' dan ', $value);
        $value = str_replace('staff', 'staf', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    public static function hasJabatan(?User $user, string $target): bool
    {
        $jabatan = self::jabatan($user);
        $target = self::normalize($target);

        if ($jabatan === '' || $target === '') {
            return false;
        }

        return $jabatan === $target || str_starts_with($jabatan, $target.' ');
    }

    public static function isKepala(?User $user): bool
    {
        return self::hasJabatan($user, self::JABATAN_KEPALA);
    }

    public static function isKoordinator(?User $user): bool
    {
        return self::hasJabatan($user, self::JABATAN_KOORDINATOR);
    }

    public static function isAnggota(?User $user): bool
    {
        return self::hasJabatan($user, self::JABATAN_ANGGOTA);
    }

    public static function isStruktural(?User $user): bool
    {
        return self::isKepala($user) || self::isKoordinator($user);
    }

    public static function isTeamMember(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return SecurityTeamMember::query()->active()->where('user_id', $user->id)->exists();
    }

    /**
     * Boleh membuka modul sama sekali.
     */
    public static function hasAccess(?User $user): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->getRoleNames()->contains(self::ROLE)) {
            return true;
        }

        if (str_contains(self::jabatan($user), 'keamanan')) {
            return true;
        }

        return self::isTeamMember($user);
    }

    /**
     * Boleh mengelola konfigurasi, regu, dan menutup insiden.
     */
    public static function canManage(?User $user): bool
    {
        return self::isAdmin($user) || self::isStruktural($user);
    }

    /**
     * Pos jaga tempat petugas bertugas (dari regu aktif), atau null.
     */
    public static function postIdFor(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $team = SecurityTeamMember::query()
            ->active()
            ->with('team:id,post_id')
            ->where('user_id', $user->id)
            ->first();

        return $team?->team?->post_id;
    }
}
