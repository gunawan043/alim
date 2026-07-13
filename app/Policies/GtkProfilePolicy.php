<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GtkProfile;

class GtkProfilePolicy
{
    /**
     * VIEW DATA GTK
     */
    public function view(User $user, GtkProfile $profile): bool
    {
        // Personalia / Wadir / Mudir / Super Admin
        if (canPermission('gtk-view-all')) {
            return true;
        }

        // Kepala sekolah & wakil → lihat TANPA sensitif
        if (canPermission('gtk-view-school')) {
            return true;
        }

        // Guru → hanya data sendiri
        return canPermission('gtk-view-self') && $user->id === $profile->user_id;
    }

    /**
     * UPDATE DATA GTK
     */
    public function update(User $user, GtkProfile $profile): bool
    {
        // Personalia ke atas
        if (canPermission('gtk-edit-all')) {
            return true;
        }

        // User hanya boleh update data dirinya
        return canPermission('gtk-edit-self') && $user->id === $profile->user_id;
    }

    /**
     * AKSES DATA SENSITIF (NIK, KK, NPWP)
     */
    public function viewSensitive(User $user): bool
    {
        return canPermission('gtk-view-sensitive');
    }

    /**
     * DELETE DATA
     */
    public function delete(User $user, GtkProfile $profile): bool
    {
        return canPermission('super-admin-only');
    }
}