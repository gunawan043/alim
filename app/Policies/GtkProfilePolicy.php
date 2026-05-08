<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GtkProfile;

class GtkProfilePolicy
{
    /**
     * Helper: ambil level role terendah (semakin kecil = semakin tinggi)
     */
    protected function roleLevel(User $user): int
    {
        return (int) $user->roles()->min('level');
    }

    /**
     * VIEW DATA GTK
     */
    public function view(User $user, GtkProfile $profile): bool
    {
        $level = $this->roleLevel($user);

        // Super Admin, Mudir, Wadir, Personalia
        if ($level <= 5) {
            return true;
        }

        // Kepala sekolah & wakil → lihat TANPA sensitif
        if ($level <= 7) {
            return true;
        }

        // Guru → hanya data sendiri
        return $user->id === $profile->user_id;
    }

    /**
     * UPDATE DATA GTK
     */
    public function update(User $user, GtkProfile $profile): bool
    {
        $level = $this->roleLevel($user);

        // Personalia ke atas
        if ($level <= 5) {
            return true;
        }

        // User hanya boleh update data dirinya
        return $user->id === $profile->user_id;
    }

    /**
     * AKSES DATA SENSITIF (NIK, KK, NPWP)
     */
    public function viewSensitive(User $user): bool
    {
        $level = $this->roleLevel($user);

        // HANYA role tertentu
        return $level <= 5;
    }

    /**
     * DELETE DATA
     */
    public function delete(User $user, GtkProfile $profile): bool
    {
        return $this->roleLevel($user) <= 2; // Super Admin & Mudir
    }
}
