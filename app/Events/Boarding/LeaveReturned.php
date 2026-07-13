<?php

namespace App\Events\Boarding;

use App\Models\DormitoryPermit;
use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a student returns from leave to the dormitory.
 * Resumes academic attendance synchronization.
 */
class LeaveReturned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DormitoryPermit $permit,
        public Student $student,
        public ?string $note = null,
    ) {}
}