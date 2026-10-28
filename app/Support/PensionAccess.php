<?php

namespace App\Support;

use App\Models\User;

/**
 * Hak akses modul Pensiun.
 *
 * Data pensiun adalah bagian kepegawaian yang sensitif, sehingga hanya
 * Personalia dan Super Admin yang boleh mengubahnya. Pegawai lain tetap
 * dapat melihat ringkasan pensiun di halaman profil (read-only).
 */
class PensionAccess
{
    /** Role yang boleh mengelola data pensiun GTK. */
    private const MANAGER_ROLES = [
        'Humas Personalia',
        'Personalia',
    ];

    public static function canManage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyRole(self::MANAGER_ROLES);
    }
}
