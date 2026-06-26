<?php

namespace App\Events;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudyGroup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentPromoted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly StudyGroup $fromStudyGroup,
        public readonly StudyGroup $toStudyGroup,
        public readonly AcademicYear $fromAcademicYear,
        public readonly AcademicYear $toAcademicYear,
        public readonly string $promotionDate,
        public readonly ?string $actorId = null,
        public readonly ?string $source = null,
    ) {}
}
