<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Domain event: a Subject has been assigned to a StudyGroup.
 *
 * Fired by StudyGroupSubjectObserver on create/update/delete of a
 * study_group_subjects row. This is the dedicated entry point for the
 * academic cascade — listeners are responsible for:
 *   - provisioning teacher_admin_books for grade/attendance entry
 *   - resolving KKTP context for the (school, grade_level, subject, academic_year)
 *   - registering subject into the raport pipeline
 *   - preparing subject-level attendance scaffolding
 *
 * The event carries plain data (UUIDs only) so it is safe to serialize
 * across queue boundaries.
 */
class SubjectAssignedToStudyGroup
{
    use Dispatchable, SerializesModels;

    public string $studyGroupSubjectId;

    public string $studyGroupId;

    public string $subjectId;

    public ?string $teacherId;

    public ?string $schoolId;

    public ?string $academicYearId;

    /**
     * The originating study group — required to resolve grade_level_id
     * which is needed to look up the matching KKTP context.
     */
    public ?string $gradeLevelId;

    /**
     * one of: created, updated, deleted.
     */
    public string $changeType;

    public function __construct(
        string $studyGroupSubjectId,
        string $studyGroupId,
        string $subjectId,
        ?string $teacherId = null,
        ?string $schoolId = null,
        ?string $academicYearId = null,
        ?string $gradeLevelId = null,
        string $changeType = 'created',
    ) {
        $this->studyGroupSubjectId = $studyGroupSubjectId;
        $this->studyGroupId = $studyGroupId;
        $this->subjectId = $subjectId;
        $this->teacherId = $teacherId;
        $this->schoolId = $schoolId;
        $this->academicYearId = $academicYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->changeType = $changeType;
    }
}
