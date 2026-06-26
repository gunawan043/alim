<?php

namespace App\Events;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudyGroup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentGraduated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly StudyGroup $fromStudyGroup,
        public readonly AcademicYear $fromAcademicYear,
        public readonly string $graduationDate,
        public readonly ?string $graduationYear = null,
        public readonly ?string $actorId = null,
        public readonly ?string $source = 'promotion',
    ) {}
}
