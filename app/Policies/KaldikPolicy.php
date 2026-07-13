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
     * Super Admin & Administrator → boleh untuk semua
     * Admin Tata Usaha → hanya untuk kategori 'agenda' yang work_unit_id-nya sendiri
     */
    public function create(User $user): bool
    {
        return canPermission('kaldik-create');
    }

    public function update(User $user, Kaldik $kaldik): bool
    {
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

        return false;
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
     * Ambil work_unit_id primary user dari GtkWorkUnit.
     */
    private function getUserWorkUnitId(User $user): ?string
    {
        return $user->primaryWorkUnit?->work_unit_id;
    }
}
