<?php

namespace App\Observers;

use App\Events\StudyGroupSubjectChanged;
use App\Events\SubjectAssignedToStudyGroup;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;

class StudyGroupSubjectObserver
{
    protected static bool $disabled = false;

    public static function disable(): void
    {
        static::$disabled = true;
    }

    public static function enable(): void
    {
        static::$disabled = false;
    }

    public function created(StudyGroupSubject $row): void
    {
        if (static::$disabled) {
            return;
        }

        $ctx = $this->resolveContext($row);

        StudyGroupSubjectChanged::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            'created',
            $ctx['schoolId'],
            $ctx['academicYearId'],
        );

        SubjectAssignedToStudyGroup::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            $ctx['schoolId'],
            $ctx['academicYearId'],
            $ctx['gradeLevelId'],
            'created',
        );
    }

    public function updated(StudyGroupSubject $row): void
    {
        if (static::$disabled) {
            return;
        }

        $ctx = $this->resolveContext($row);

        StudyGroupSubjectChanged::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            'updated',
            $ctx['schoolId'],
            $ctx['academicYearId'],
        );

        SubjectAssignedToStudyGroup::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            $ctx['schoolId'],
            $ctx['academicYearId'],
            $ctx['gradeLevelId'],
            'updated',
        );
    }

    public function deleted(StudyGroupSubject $row): void
    {
        if (static::$disabled) {
            return;
        }

        $ctx = $this->resolveContext($row);

        StudyGroupSubjectChanged::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            'deleted',
            $ctx['schoolId'],
            $ctx['academicYearId'],
        );

        SubjectAssignedToStudyGroup::dispatch(
            $row->id,
            $row->study_group_id,
            $row->subject_id,
            $row->teacher_id,
            $ctx['schoolId'],
            $ctx['academicYearId'],
            $ctx['gradeLevelId'],
            'deleted',
        );
    }

    /**
     * Resolve school_id, academic_year_id, grade_level_id for the event payload.
     */
    private function resolveContext(StudyGroupSubject $row): array
    {
        $schoolId = $row->school_id;
        $academicYearId = $row->academic_year_id;
        $gradeLevelId = null;

        $sg = $row->study_group_id
            ? StudyGroup::find($row->study_group_id)
            : null;

        if ($sg) {
            $schoolId = $schoolId ?? $sg->school_id;
            $academicYearId = $academicYearId ?? $sg->academic_year_id;
            $gradeLevelId = $sg->grade_level_id;
        }

        return [
            'schoolId' => $schoolId,
            'academicYearId' => $academicYearId,
            'gradeLevelId' => $gradeLevelId,
        ];
    }
}
