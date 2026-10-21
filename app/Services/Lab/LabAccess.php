<?php

namespace App\Services\Lab;

use App\Models\AcademicYear;
use App\Models\Lab\LabProfile;
use App\Models\OtherTeacherTask;
use App\Models\User;

/**
 * Otorisasi laboratorium IPA.
 *
 * Sumber penugasan utama adalah other_teacher_tasks (school/year), bukan role
 * baru. Koordinator Laboratorium = guru dengan task_name "Koordinator
 * Laboratorium" aktif pada sekolah/tahun ajaran tersebut.
 */
class LabAccess
{
    private function hasPermission(?User $user, string $permission): bool
    {
        return $user !== null
            && function_exists('canUserPermission')
            && app()->bound(\App\Authorization\ValueObjects\OrganizationContext::class)
            && canUserPermission($user, $permission);
    }

    public function isSystemAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null
            && ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
                || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()));
    }

    public function isCoordinator(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if ($this->isSystemAdmin($user) || $this->hasPermission($user, 'lab_manage') || $this->hasPermission($user, 'lab_master_manage')) {
            return true;
        }

        $schoolId ??= request()->attributes->get('schoolContextId');
        $academicYearId ??= AcademicYear::where('is_active', true)->value('id');

        return OtherTeacherTask::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(task_name)) = ?', ['koordinator laboratorium'])
                    ->orWhere('task_code', 'koordinator-laboratorium');
            })
            ->exists();
    }

    public function isPrincipal(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $jabatan = strtoupper(trim((string) ($user->employment?->jabatan ?? '')));

        return str_contains($jabatan, 'KEPALA') || str_contains($jabatan, 'WAKIL KEPALA');
    }

    public function canView(?User $user, LabProfile $lab): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if ($this->isSystemAdmin($user) || $this->isPrincipal($user) || $this->hasPermission($user, 'lab_view')) {
            return true;
        }

        if ((string) $lab->coordinator_user_id === (string) $user->id) {
            return true;
        }

        return $this->isCoordinator($user, $lab->school_id);
    }

    public function canManage(?User $user, LabProfile $lab): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if ($this->isSystemAdmin($user) || $this->hasPermission($user, 'lab_manage') || $this->hasPermission($user, 'lab_master_manage')) {
            return true;
        }

        if ((string) $lab->coordinator_user_id === (string) $user->id) {
            return true;
        }

        return $this->isCoordinator($user, $lab->school_id);
    }

    public function canApprove(?User $user, LabProfile $lab): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $this->isSystemAdmin($user)
            || $this->hasPermission($user, 'lab_approve')
            || ((string) $lab->head_user_id === (string) $user->id)
            || $this->isPrincipal($user);
    }
}
