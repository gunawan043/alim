<?php

namespace App\Listeners;

use App\Events\SubjectAssignedToStudyGroup;
use App\Jobs\ProvisionStudyGroupSubjectAcademicStructureJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for SubjectAssignedToStudyGroup.
 *
 * Thin dispatcher: delegates the heavy cascade to a queued Job so the
 * controller / observer dispatch is non-blocking.
 */
class ProvisionStudyGroupSubjectAcademicStructure
{
    public function handle(SubjectAssignedToStudyGroup $event): void
    {
        ProvisionStudyGroupSubjectAcademicStructureJob::dispatch(
            $event->studyGroupSubjectId,
            $event->studyGroupId,
            $event->subjectId,
            $event->teacherId,
            $event->schoolId,
            $event->academicYearId,
            $event->gradeLevelId,
            $event->changeType,
        );
    }
}
