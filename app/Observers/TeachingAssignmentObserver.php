<?php

namespace App\Observers;

use App\Events\SubjectAssignedToStudyGroup;
use App\Events\TeachingAssignmentChanged;
use App\Models\StudyGroup;
use App\Models\TeachingAssignment;

class TeachingAssignmentObserver
{
    public function created(TeachingAssignment $assignment): void
    {
        TeachingAssignmentChanged::dispatch(
            $assignment,
            'created',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        $this->dispatchSubjectAssigned($assignment, 'created');
    }

    public function updated(TeachingAssignment $assignment): void
    {
        TeachingAssignmentChanged::dispatch(
            $assignment,
            'updated',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        $this->dispatchSubjectAssigned($assignment, 'updated');
    }

    public function deleted(TeachingAssignment $assignment): void
    {
        TeachingAssignmentChanged::dispatch(
            $assignment,
            'deleted',
            $assignment->school_id,
            $assignment->academic_year_id,
            $assignment->teacher_id,
            $assignment->subject_id,
            $assignment->study_group_id,
        );

        $this->dispatchSubjectAssigned($assignment, 'deleted');
    }

    /**
     * Mirror the StudyGroupSubjectObserver cascade: dispatch
     * SubjectAssignedToStudyGroup so the nilai provisioning job runs.
     */
    private function dispatchSubjectAssigned(TeachingAssignment $assignment, string $changeType): void
    {
        SubjectAssignedToStudyGroup::dispatch(
            $assignment->id,
            $assignment->study_group_id,
            $assignment->subject_id,
            $assignment->teacher_id,
            $assignment->school_id,
            $assignment->academic_year_id,
            $this->resolveGradeLevelId($assignment),
            $changeType,
        );
    }

    private function resolveGradeLevelId(TeachingAssignment $assignment): ?string
    {
        if (! $assignment->study_group_id) {
            return null;
        }

        $sg = StudyGroup::find($assignment->study_group_id);

        return $sg?->grade_level_id;
    }
}
