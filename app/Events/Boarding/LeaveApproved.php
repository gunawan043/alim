<?php

namespace App\Events\Boarding;

use App\Models\DormitoryPermit;
use App\Models\Student;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a boarding leave is approved and the student status changes
 * to ON_LEAVE. Attended by academic attendance sync listener.
 */
class LeaveApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DormitoryPermit $permit,
        public Student $student,
        public ?string $approvalNote = null,
    ) {}

    public function dormitoryId(): string
    {
        return $this->permit->dormitory_id;
    }
}
