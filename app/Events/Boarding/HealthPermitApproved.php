<?php

namespace App\Events\Boarding;

use App\Models\Student;
use App\Models\StudentHealthPermit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a boarding health permit is approved (hospitalization begins).
 * Triggers attendance sync and potential clinic creation.
 */
class HealthPermitApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StudentHealthPermit $permit,
        public Student $student,
        public ?string $note = null,
    ) {}

    public function type(): string
    {
        return $this->permit->permit_type;
    }
}
