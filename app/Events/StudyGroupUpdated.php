<?php

namespace App\Events;

use App\Models\StudyGroup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyGroupUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StudyGroup $studyGroup,
        public readonly string $changeType = 'updated',
    ) {}

    public function getSchoolId(): ?string
    {
        return $this->studyGroup->school_id;
    }

    public function getAcademicYearId(): ?string
    {
        return $this->studyGroup->academic_year_id;
    }

    public function getStudyGroupId(): string
    {
        return $this->studyGroup->id;
    }
}
