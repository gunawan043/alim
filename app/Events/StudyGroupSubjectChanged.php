<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyGroupSubjectChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?string $studyGroupSubjectId = null,
        public ?string $studyGroupId = null,
        public ?string $subjectId = null,
        public ?string $teacherId = null,
        public string $changeType = 'updated',
        public ?string $schoolId = null,
        public ?string $academicYearId = null,
    ) {}
}
