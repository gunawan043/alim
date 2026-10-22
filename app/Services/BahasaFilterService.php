<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Filter lintas satuan pendidikan untuk modul Departemen Bahasa.
 * Departemen/Super Admin dapat memilih sekolah lain; guru tetap dibatasi
 * pada konteks sekolahnya.
 */
class BahasaFilterService
{
    public function __construct(private readonly BahasaAccess $access) {}

    public function schoolId(Request $request): ?string
    {
        $context = $request->attributes->get('schoolContextId');

        if ($request->has('school_id') && ($this->access->isDepartment($request->user()) || $this->access->isSuperAdmin($request->user()))) {
            $requested = trim((string) $request->input('school_id'));

            return $requested !== '' ? $requested : null;
        }

        return $context ? (string) $context : null;
    }

    public function showSchoolFilter(Request $request): bool
    {
        return $this->access->isDepartment($request->user()) || $this->access->isSuperAdmin($request->user());
    }

    public function gradeLevelId(Request $request): ?string
    {
        return $request->filled('grade_level_id') ? (string) $request->input('grade_level_id') : null;
    }

    public function studyGroupId(Request $request): ?string
    {
        return $request->filled('study_group_id') ? (string) $request->input('study_group_id') : null;
    }

    /**
     * @return array{schools: Collection, gradeLevels: Collection, studyGroups: Collection, showSchool: bool, schoolId: ?string, gradeLevelId: ?string, studyGroupId: ?string}
     */
    public function options(Request $request): array
    {
        $schoolId = $this->schoolId($request);
        $showSchool = $this->showSchoolFilter($request);
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $schools = $showSchool
            ? School::query()->orderBy('name')->get(['id', 'name'])
            : School::query()->when($schoolId, fn ($q) => $q->whereKey($schoolId))->get(['id', 'name']);

        $gradeLevels = GradeLevel::query()
            ->with('school:id,name')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'name', 'school_id']);

        $studyGroups = StudyGroup::query()
            ->with('school:id,name')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->when($this->gradeLevelId($request), fn ($q, $gradeId) => $q->where('grade_level_id', $gradeId))
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id', 'school_id']);

        return [
            'schools' => $schools,
            'gradeLevels' => $gradeLevels,
            'studyGroups' => $studyGroups,
            'showSchool' => $showSchool,
            'schoolId' => $schoolId,
            'gradeLevelId' => $this->gradeLevelId($request),
            'studyGroupId' => $this->studyGroupId($request),
        ];
    }

    public function applyStudentScope(Builder $query, Request $request, string $studentRelation = 'student'): Builder
    {
        $gradeId = $this->gradeLevelId($request);
        $groupId = $this->studyGroupId($request);

        if (! $gradeId && ! $groupId) {
            return $query;
        }

        return $query->whereHas($studentRelation === '' ? 'classHistories' : $studentRelation.'.classHistories', function (Builder $history) use ($gradeId, $groupId) {
            $history->where('is_active', true)
                ->when($gradeId, fn ($q) => $q->whereHas('studyGroup', fn ($group) => $group->where('grade_level_id', $gradeId)))
                ->when($groupId, fn ($q) => $q->where('study_group_id', $groupId));
        });
    }

    public static function labelWithSchool(User $user, string $label, ?string $schoolName): string
    {
        return $schoolName ? $label.' — '.$schoolName : $label;
    }
}
