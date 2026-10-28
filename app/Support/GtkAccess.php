<?php

namespace App\Support;

use App\Models\User;

/**
 * Aturan akses data GTK (pegawai) yang dipakai lintas middleware/controller.
 *
 * - Self-service: setiap pengguna boleh melihat/mengubah data GTK miliknya.
 * - Manager: System/Super Admin, pemegang izin `gtk-edit-all`, atau role
 *   pengelola kepegawaian yang memang dipakai di modul GTK.
 * - View-only: pemegang izin `gtk-view-all` / `gtk-view-school`.
 */
class GtkAccess
{
    /** Role yang boleh mengelola data GTK pegawai lain. */
    private const MANAGER_ROLES = [
        'Humas Personalia',
        'Personalia',
        'Satuan Pendidikan',
        'Unit Rumah Tangga',
    ];

    public static function isManager(User $user): bool
    {
        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if (canPermission('gtk-edit-all')) {
            return true;
        }

        return $user->hasAnyRole(self::MANAGER_ROLES);
    }

    /**
     * Update massal GTK (halaman /gtk/massal + aksi mass-update).
     *
     * Sengaja lebih ketat daripada isManager(): hanya Personalia dan Super
     * Admin. Kepala satuan kerja / unit rumah tangga TIDAK termasuk.
     */
    public static function canMassUpdate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyRole(['Humas Personalia', 'Personalia']);
    }

    public static function canView(User $actor, User $target): bool
    {
        if ((string) $actor->id === (string) $target->id) {
            return true;
        }

        if (self::isManager($actor)) {
            return true;
        }

        return canPermission('gtk-view-all') || canPermission('gtk-view-school');
    }
}
