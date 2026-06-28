<?php

namespace App\Events;

use App\Models\TeachingAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeachingAssignmentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TeachingAssignment $teachingAssignment,
        public readonly string $changeType = 'updated',
    ) {}

    public function getSchoolId(): ?string
    {
        return $this->teachingAssignment->school_id;
    }

    public function getAcademicYearId(): ?string
    {
        return $this->teachingAssignment->academic_year_id;
    }

    public function getSubjectId(): ?string
    {
        return $this->teachingAssignment->subject_id;
    }

    public function getStudyGroupId(): ?string
    {
        return $this->teachingAssignment->study_group_id;
    }

    public function getTeacherId(): ?string
    {
        return $this->teachingAssignment->teacher_id;
    }
}
