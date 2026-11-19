<?php

namespace App\Support;

use App\Models\User;

/**
 * Otorisasi pengelolaan link wali–santri.
 *
 * Tidak ada role baru: Super Admin/System Admin, atau role "Satuan Pendidikan"
 * yang jabatannya mengandung "Tata Usaha" (Kepala/Admin/Staf) — semua jabatan
 * TU dapat saling backup.
 */
class WaliLinkAccess
{
    public static function canManage(User $user): bool
    {
        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        try {
            if (! $user->hasRole('Satuan Pendidikan')) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        $jabatan = implode(' ', self::candidates($user));

        return $jabatan !== '' && str_contains($jabatan, 'tata usaha');
    }

    /**
     * Semua sumber jabatan/tugas, bukan hanya kepegawaian utama. Kepala TU
     * yang tercatat sebagai tugas tambahan tetap dapat mengelola link wali.
     *
     * @return list<string>
     */
    private static function candidates(User $user): array
    {
        return UserJabatan::candidates($user);
    }
}
