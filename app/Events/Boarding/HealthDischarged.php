<?php

namespace App\Events\Boarding;

use App\Models\StudentHealthPermit;
use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a student is discharged from hospitalization.
 * Triggers attendance resume and clinic discharge event.
 */
class HealthDischarged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StudentHealthPermit $permit,
        public Student $student,
        public ?string $note = null,
    ) {}
}