<?php

namespace App\Policies;

use App\Models\Kaldik;
use App\Models\User;

class KaldikPolicy
{
    /**
     * Semua role bisa LIHAT (read) data kaldik/agenda.
     * Write access yang dibatasi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Kaldik $kaldik): bool
    {
        return true;
    }

    /**
     * Siapa yang boleh BUAT / UPDATE / HAPUS Kaldik atau Agenda:
     *
     * Super Admin, Administrator & Pimpinan → boleh mengelola Kalender Pendidikan
     * Admin Tata Usaha → hanya untuk kategori 'agenda' yang work_unit_id-nya sendiri
     */
    public function create(User $user): bool
    {
        return $this->canManageKaldik($user) || canPermission('kaldik-create');
    }

    public function update(User $user, Kaldik $kaldik): bool
    {
        // Kalender Pendidikan (kaldik pondok) hanya dikelola Super Admin & Pimpinan.
        if ($kaldik->category === Kaldik::CATEGORY_KALDIK) {
            return $this->canManageKaldik($user) || canPermission('kaldik-update-all');
        }

        if (canPermission('kaldik-update-all')) {
            return true;
        }

        // Admin Tata Usaha → hanya bisa edit/hapus agenda miliknya sendiri
        if (canPermission('kaldik-update-self')) {
            if ($kaldik->category !== Kaldik::CATEGORY_AGENDA) {
                return false;
            }

            $userWorkUnitId = $this->getUserWorkUnitId($user);

            return $kaldik->work_unit_id === $userWorkUnitId;
        }

        return $this->canManageKaldik($user);
    }

    public function delete(User $user, Kaldik $kaldik): bool
    {
        return $this->update($user, $kaldik);
    }

    public function restore(User $user, Kaldik $kaldik): bool
    {
        return $this->update($user, $kaldik);
    }

    public function forceDelete(User $user, Kaldik $kaldik): bool
    {
        return $this->update($user, $kaldik);
    }

    /**
     * Super Admin / Administrator (system admin) & Pimpinan boleh mengelola
     * Kalender Pendidikan. Role lain hanya dapat melihat sesuai konteks
     * satuan pendidikannya.
     */
    private function canManageKaldik(User $user): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return in_array('pimpinan', $user->effectiveRoles(), true);
    }

    /**
     * Ambil work_unit_id primary user dari GtkWorkUnit.
     */
    private function getUserWorkUnitId(User $user): ?string
    {
        return $user->primaryWorkUnit?->work_unit_id;
    }
}
