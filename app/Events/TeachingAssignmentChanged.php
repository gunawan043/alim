<?php

namespace App\Events;

use App\Models\TeachingAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeachingAssignmentChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?TeachingAssignment $assignment = null,
        public string $changeType = 'updated',
        public ?string $schoolId = null,
        public ?string $academicYearId = null,
        public ?string $teacherId = null,
        public ?string $subjectId = null,
        public ?string $studyGroupId = null,
    ) {}
}
