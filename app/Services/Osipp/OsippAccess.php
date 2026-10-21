<?php

namespace App\Services\Osipp;

use App\Models\AcademicYear;
use App\Models\CoordinatorAssignment;
use App\Models\GtkAdditionalTask;
use App\Models\Osipp\OsippPeriod;
use App\Models\OtherTeacherTask;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Otorisasi ruang kerja Guru Koordinator OSIPP.
 *
 * Sumber utama kewenangan adalah penugasan resmi pada `other_teacher_tasks`
 * dengan cakupan satuan pendidikan dan tahun ajaran. Penugasan lama pada
 * `coordinator_assignments` dan SK `gtk_additional_tasks` tetap dibaca sebagai
 * fallback, bukan sebagai pengganti penugasan operasional.
 */
class OsippAccess
{
    public const TASK_NAMES = [
        'guru koordinator osipp',
        'koordinator osipp',
        'pembina osipp',
    ];

    public const TASK_CODES = [
        'guru-koordinator-osipp',
        'koordinator-osipp',
        'osipp',
    ];

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

    public function schoolId(?User $user = null): ?string
    {
        $user ??= auth()->user();

        $contextSchoolId = request()->attributes->get('schoolContextId');
        if ($contextSchoolId) {
            return (string) $contextSchoolId;
        }

        $employmentSchoolId = $user?->employment?->school_id;
        if ($employmentSchoolId) {
            return (string) $employmentSchoolId;
        }

        if (! $user) {
            return null;
        }

        $taskSchoolId = OtherTeacherTask::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                foreach (self::TASK_NAMES as $name) {
                    $query->orWhereRaw('LOWER(TRIM(task_name)) = ?', [$name]);
                }
                $query->orWhereIn('task_code', self::TASK_CODES);
            })
            ->value('school_id');

        if ($taskSchoolId) {
            return (string) $taskSchoolId;
        }

        $coordinatorSchoolId = CoordinatorAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', today())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()))
            ->where(fn ($q) => $q->whereRaw('LOWER(coordination_area) LIKE ?', ['%osipp%'])
                ->orWhereRaw('LOWER(coordination_type) LIKE ?', ['%osipp%'])
                ->orWhereRaw('LOWER(description) LIKE ?', ['%osipp%']))
            ->value('school_id');

        return $coordinatorSchoolId ? (string) $coordinatorSchoolId : null;
    }

    public function academicYearId(?string $schoolId = null): ?string
    {
        return AcademicYear::query()->where('is_active', true)->value('id');
    }

    public function isCoordinator(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        $schoolId ??= $this->schoolId($user);
        $academicYearId ??= $this->academicYearId($schoolId);

        if (! $schoolId) {
            return false;
        }

        $taskExists = OtherTeacherTask::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where(function ($query) {
                foreach (self::TASK_NAMES as $name) {
                    $query->orWhereRaw('LOWER(TRIM(task_name)) = ?', [$name]);
                }
                $query->orWhereIn('task_code', self::TASK_CODES);
            })
            ->exists();

        if ($taskExists) {
            return true;
        }

        $coordinatorExists = CoordinatorAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('status', 'active')
            ->where('start_date', '<=', today())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()))
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(coordination_area)) LIKE ?', ['%osipp%'])
                    ->orWhereRaw('LOWER(TRIM(coordination_type)) LIKE ?', ['%osipp%'])
                    ->orWhereRaw('LOWER(TRIM(description)) LIKE ?', ['%osipp%']);
            })
            ->exists();

        if ($coordinatorExists) {
            return true;
        }

        // Fallback SK lama: harus aktif dan tidak boleh melewati cakupan sekolah.
        $legacyTasks = GtkAdditionalTask::query()
            ->with('decree')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('tmt')->orWhere('tmt', '<=', today());
            })
            ->where(function ($q) {
                $q->whereNull('tst')->orWhere('tst', '>=', today());
            })
            ->where(function ($query) {
                foreach (self::TASK_NAMES as $name) {
                    $query->orWhereRaw('LOWER(TRIM(nama_tugas)) = ?', [$name]);
                }
            })
            ->get();

        foreach ($legacyTasks as $task) {
            if ($task->decree && (string) $task->decree->school_id !== (string) $schoolId) {
                continue;
            }

            $employment = $user->employment;
            if ($employment && (string) $employment->school_id !== (string) $schoolId) {
                continue;
            }

            if ($employment && $academicYearId && $employment->academic_year_id
                && (string) $employment->academic_year_id !== (string) $academicYearId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function canView(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $this->isSystemAdmin($user)
            || $this->hasPermission($user, 'osipp_view')
            || $this->hasPermission($user, 'osipp_manage')
            || $this->hasPermission($user, 'osipp_verify')
            || $this->hasPermission($user, 'osipp_evaluate')
            || $this->isCoordinator($user, $schoolId, $academicYearId);
    }

    public function canManage(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $this->isSystemAdmin($user)
            || $this->hasPermission($user, 'osipp_manage')
            || $this->isCoordinator($user, $schoolId, $academicYearId);
    }

    public function canVerify(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $this->isSystemAdmin($user)
            || $this->hasPermission($user, 'osipp_verify')
            || $this->isCoordinator($user, $schoolId, $academicYearId);
    }

    public function canEvaluate(?User $user = null, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return $this->isSystemAdmin($user)
            || $this->hasPermission($user, 'osipp_evaluate')
            || $this->isCoordinator($user, $schoolId, $academicYearId);
    }

    public function canAccessPeriod(User $user, OsippPeriod $period): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        $sameSchool = (string) $period->school_id === (string) $this->schoolId($user);

        if (! $sameSchool) {
            return false;
        }

        foreach (['osipp_view', 'osipp_manage', 'osipp_verify', 'osipp_evaluate'] as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return $this->isCoordinator($user, $period->school_id, $period->academic_year_id);
    }

    /**
     * Penerima notifikasi verifikasi OSIPP pada sekolah/tahun yang sama.
     *
     * @return array<int, string>
     */
    public function coordinatorIds(string $schoolId, ?string $academicYearId = null): array
    {
        $ids = OtherTeacherTask::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where(function ($query) {
                foreach (self::TASK_NAMES as $name) {
                    $query->orWhereRaw('LOWER(TRIM(task_name)) = ?', [$name]);
                }
                $query->orWhereIn('task_code', self::TASK_CODES);
            })
            ->pluck('teacher_id');

        $ids = $ids->merge(
            CoordinatorAssignment::query()
                ->where('school_id', $schoolId)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->where('status', 'active')
                ->where('start_date', '<=', today())
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()))
                ->where(fn ($q) => $q->whereRaw('LOWER(coordination_area) LIKE ?', ['%osipp%'])
                    ->orWhereRaw('LOWER(coordination_type) LIKE ?', ['%osipp%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%osipp%']))
                ->pluck('teacher_id')
        );

        $ids = $ids->merge(
            GtkAdditionalTask::query()
                ->where(function ($q) {
                    $q->whereNull('tst')->orWhere('tst', '>=', today());
                })
                ->where(function ($query) {
                    foreach (self::TASK_NAMES as $name) {
                        $query->orWhereRaw('LOWER(TRIM(nama_tugas)) = ?', [$name]);
                    }
                })
                ->whereHas('user.employment', fn ($q) => $q->where('school_id', $schoolId))
                ->pluck('user_id')
        );

        return $ids->filter()->unique()->map(fn ($id) => (string) $id)->values()->all();
    }

    public function isWithinPeriod(OsippPeriod $period, Carbon|string|null $date = null): bool
    {
        $date = $date ? Carbon::parse($date) : today();

        if ($period->start_date && $date->lt($period->start_date)) {
            return false;
        }

        if ($period->end_date && $date->gt($period->end_date)) {
            return false;
        }

        return true;
    }
}
