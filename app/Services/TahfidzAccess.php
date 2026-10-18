<?php

namespace App\Services;

use App\Models\GtkEmployment;
use App\Models\TahfidzCoordinator;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupTeacher;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Schema;

/**
 * Kewenangan modul Tahfidz — dua lapis:
 * - Sekolah: koordinator tahfidz mengelola halaqah dan operasional harian.
 * - Departemen Tahfidz: kurikulum, QC, ujian, sertifikasi, lintas sekolah.
 *
 * Home base kepegawaian TIDAK sama dengan penugasan halaqah. Guru
 * departemen hanya boleh input setoran jika punya penugasan halaqah.
 */
class TahfidzAccess
{
    public const DEPARTMENT_CODE = 'DEP-TAH';

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

        foreach ($roles as $role) {
            if (str_contains($role, 'departemen tahfidz')) {
                return true;
            }
        }

        if (str_contains($this->jabatan($user), 'departemen tahfidz')) {
            return true;
        }

        return false;
    }

    public function isSchoolCoordinator(User $user, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        if (! Schema::hasTable('tahfidz_coordinators')) {
            return false;
        }

        $registered = TahfidzCoordinator::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->exists();

        if ($registered) {
            return true;
        }

        // Kompatibilitas data lama: tugas tambahan Koordinator Guru Tahfidz
        // tetap diakui sebagai koordinator sekolah.
        $tasks = method_exists($user, 'tugasTambahan')
            ? $user->tugasTambahan->pluck('nama')->map(fn ($name) => strtolower(trim((string) $name)))->all()
            : [];

        if (! in_array('koordinator guru tahfidz', $tasks, true)) {
            return false;
        }

        if (! $schoolId) {
            return true;
        }

        return GtkEmployment::query()->where('user_id', $user->id)->where('school_id', $schoolId)->exists();
    }

    public function isSchoolLeader(User $user): bool
    {
        $jabatan = $this->jabatan($user);

        return str_contains($jabatan, 'kepala satuan pendidikan')
            || str_contains($jabatan, 'wakil kepala satuan pendidikan')
            || str_contains($jabatan, 'kepala sekolah')
            || str_contains($jabatan, 'wakil kepala sekolah')
            || str_contains($jabatan, 'kurikulum');
    }

    public function canViewAllSchools(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isDepartment($user);
    }

    public function canManageCurriculum(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isDepartment($user);
    }

    public function canManageGroup(User $user, TahfidzGroup $group): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isDepartment($user) && $this->isDepartmentGroup($group)) {
            return true;
        }

        return $this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id);
    }

    public function canViewGroup(User $user, TahfidzGroup $group): bool
    {
        if ($this->canViewAllSchools($user)) {
            return true;
        }

        if ($this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id)) {
            return true;
        }

        if ($this->isSchoolLeader($user) && $this->hasSchoolAssignment($user, $group->school_id)) {
            return true;
        }

        if ((string) $group->teacher_id === (string) $user->id
            || (string) $group->coordinator_id === (string) $user->id) {
            return true;
        }

        return $this->isAssignedTeacher($user, $group);
    }

    /**
     * Input setoran hanya untuk guru halaqah, koordinator sekolah, atau
     * departemen yang memang diberi penugasan mengajar.
     */
    public function canInputSetoran(User $user, TahfidzGroup $group): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id)) {
            return true;
        }

        if ((string) $group->teacher_id === (string) $user->id
            || (string) $group->coordinator_id === (string) $user->id) {
            return true;
        }

        return TahfidzGroupTeacher::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('can_input_setoran', true)
            ->exists();
    }

    public function canVerifySetoran(User $user, TahfidzGroup $group): bool
    {
        return $this->isSuperAdmin($user)
            || $this->isDepartment($user)
            || $this->isSchoolCoordinator($user, $group->school_id, $group->academic_year_id);
    }

    public function departmentWorkUnit(): ?WorkUnit
    {
        return WorkUnit::query()->where('code', self::DEPARTMENT_CODE)->first();
    }

    private function isDepartmentGroup(TahfidzGroup $group): bool
    {
        $department = $this->departmentWorkUnit();

        return $department && (string) $group->work_unit_id === (string) $department->id;
    }

    private function isAssignedTeacher(User $user, TahfidzGroup $group): bool
    {
        return TahfidzGroupTeacher::query()
            ->where('tahfidz_group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    private function hasSchoolAssignment(User $user, ?string $schoolId): bool
    {
        if (! $schoolId) {
            return false;
        }

        return GtkEmployment::query()
            ->where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->exists();
    }

    private function jabatan(User $user): string
    {
        return strtolower(trim((string) ($user->employment?->jabatan ?? '')));
    }
}
