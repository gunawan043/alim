<?php

namespace App\Events;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentMutationIn;
use App\Models\StudyGroup;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentMutatedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly StudentMutationIn $mutation,
        public readonly ?StudyGroup $enrollInStudyGroup = null,
        public readonly ?AcademicYear $enrollInAcademicYear = null,
        public readonly ?string $joinDate = null,
        public readonly ?string $actorId = null,
    ) {}
}
