<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\User;

/**
 * Kewenangan modul Departemen Bahasa.
 *
 * Departemen Bahasa berperan sebagai pengawas/QC. Pelaksana teknis dapat
 * guru bahasa dan pengurus asrama; santri tidak diberi akun karena tidak
 * memakai perangkat sendiri.
 */
class BahasaAccess
{
    public function isSuperAdmin(User $user): bool
    {
        return (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin());
    }

    public function isDepartment(User $user): bool
    {
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
            : [];

        if (collect($roles)->contains(fn ($role) => str_contains($role, 'departemen bahasa'))) {
            return true;
        }

        return str_contains($this->jabatan($user), 'departemen bahasa');
    }

    public function isLanguageTeacher(User $user): bool
    {
        $jabatan = $this->jabatan($user);

        return str_contains($jabatan, 'guru bahasa')
            || str_contains($jabatan, 'bahasa arab')
            || str_contains($jabatan, 'bahasa inggris')
            || str_contains($jabatan, 'koordinator guru bahasa');
    }

    public function isDormitoryStaff(User $user): bool
    {
        $jabatan = $this->jabatan($user);
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all()
            : [];

        return str_contains($jabatan, 'asrama')
            || str_contains($jabatan, 'wali kamar')
            || collect($roles)->contains(fn ($role) => str_contains($role, 'asrama'));
    }

    public function canView(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isDepartment($user)
            || $this->isLanguageTeacher($user)
            || $this->isDormitoryStaff($user)
            || $this->isSchoolLeader($user);
    }

    public function canManage(User $user): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isDepartment($user)
            || $this->isLanguageTeacher($user);
    }

    /**
     * Pelaksana operasional: guru bahasa dan pengurus asrama. Mereka boleh
     * mencatat setoran/pelanggaran, tetapi tidak mengubah struktur program.
     */
    public function canRecord(User $user): bool
    {
        return $this->canManage($user) || $this->isDormitoryStaff($user);
    }

    public function canManageStructure(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isDepartment($user);
    }

    public function isSchoolLeader(User $user): bool
    {
        $jabatan = $this->jabatan($user);

        return str_contains($jabatan, 'kepala satuan pendidikan')
            || str_contains($jabatan, 'wakil kepala satuan pendidikan')
            || str_contains($jabatan, 'kepala sekolah')
            || str_contains($jabatan, 'kurikulum');
    }

    public function jabatan(User $user): string
    {
        $employment = $user->employment;

        return strtolower(trim((string) ($employment?->jabatan ?? '')));
    }

    public function schoolId(User $user): ?string
    {
        return GtkEmployment::query()->where('user_id', $user->id)->value('school_id');
    }
}
