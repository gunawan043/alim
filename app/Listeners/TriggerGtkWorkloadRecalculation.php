<?php

namespace App\Listeners;

use App\Events\GtkProfileUpdated;
use App\Events\StudyGroupSubjectChanged;
use App\Events\TeachingAssignmentChanged;
use App\Jobs\RecalculateWorkloadJob;
use App\Models\GtkEmployment;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerGtkWorkloadRecalculation implements ShouldQueue
{
    public function handleGtkProfileUpdated(GtkProfileUpdated $event): void
    {
        $schoolId = $event->schoolId
            ?? GtkEmployment::where('user_id', $event->gtkProfile->user_id)->value('school_id');

        RecalculateWorkloadJob::dispatch([
            'school_id' => $schoolId,
            'academic_year_id' => $event->academicYearId,
            'scope' => 'school',
            'trigger_source' => 'GtkProfileUpdated',
            'trigger_ref_id' => $event->gtkProfile->id,
            'context' => ['change_type' => $event->changeType],
        ]);
    }

    public function handleTeachingAssignmentChanged(TeachingAssignmentChanged $event): void
    {
        $schoolId = $event->schoolId
            ?? $event->assignment?->school_id;

        RecalculateWorkloadJob::dispatch([
            'school_id' => $schoolId,
            'academic_year_id' => $event->academicYearId
                ?? $event->assignment?->academic_year_id,
            'scope' => 'school',
            'trigger_source' => 'TeachingAssignmentChanged',
            'trigger_ref_id' => $event->assignment?->id,
            'context' => [
                'change_type' => $event->changeType,
                'teacher_id' => $event->teacherId,
                'subject_id' => $event->subjectId,
                'study_group_id' => $event->studyGroupId,
            ],
        ]);
    }

    public function handleStudyGroupSubjectChanged(StudyGroupSubjectChanged $event): void
    {
        RecalculateWorkloadJob::dispatch([
            'school_id' => $event->schoolId,
            'academic_year_id' => $event->academicYearId,
            'scope' => 'school',
            'trigger_source' => 'StudyGroupSubjectChanged',
            'trigger_ref_id' => $event->studyGroupSubjectId,
            'context' => [
                'change_type' => $event->changeType,
                'study_group_id' => $event->studyGroupId,
                'subject_id' => $event->subjectId,
                'teacher_id' => $event->teacherId,
            ],
        ]);
    }
}
