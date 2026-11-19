<?php

namespace App\Services\Students;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menentukan cakupan santri untuk modul Data Mahrom / Import Mahrom.
 *
 *  - admin/struktural : seluruh santri pada konteks sekolah;
 *  - wali kelas       : hanya santri pada rombel yang diwalikan;
 *  - user asrama      : hanya santri yang mondok di asrama yang dapat diakses.
 */
class MahromAccessScope
{
    /**
     * @return array{school_id: ?string, student_ids: ?array, students: Builder}
     */
    public function resolve(User $user, ?string $schoolId): array
    {
        $students = Student::query()
            ->withoutGlobalScope('school_context')
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($user->isSystemAdmin()
            || $user->isSuperAdmin()
            || canPermission('student-all-access')
            || $user->hasAnyRole(['Pimpinan', 'Humas Personalia', 'Keuangan'])) {
            return ['school_id' => $schoolId, 'student_ids' => null, 'students' => $students];
        }

        if ($user->isHomeroomTeacher()) {
            $groupIds = $user->homeroomStudyGroupIds();
            $yearId = AcademicYear::where('is_active', true)->value('id');

            $ids = StudentClassHistory::query()
                ->whereIn('study_group_id', $groupIds)
                ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
                ->pluck('student_id')
                ->unique()
                ->values()
                ->all();

            return [
                'school_id' => $schoolId,
                'student_ids' => $ids,
                'students' => $students->whereIn('id', $ids),
            ];
        }

        if ($user->isDormitoryUser()) {
            $dormitoryIds = $user->accessibleDormitoryIds();

            $ids = Student::query()
                ->withoutGlobalScope('school_context')
                ->whereHas('activeDormitoryResident', fn ($q) => $q->whereIn('dormitory_id', $dormitoryIds))
                ->pluck('id')
                ->all();

            return [
                'school_id' => $schoolId,
                'student_ids' => $ids,
                'students' => $students->whereIn('id', $ids),
            ];
        }

        return ['school_id' => $schoolId, 'student_ids' => null, 'students' => $students];
    }
}
