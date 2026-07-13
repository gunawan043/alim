<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\LeaveApproved;
use App\Services\TimelineWriter;
use Illuminate\Support\CarbonImmutable;

/**
 * Records a "leave approved" event to the unified student timeline.
 */
class RecordLeaveApprovedOnTimeline
{
    public function __construct(
        private readonly TimelineWriter $writer,
    ) {}

    public function record(LeaveApproved $event): void
    {
        $this->writer->write(
            studentId: $event->student->id,
            eventType: 'leave.approved',
            subjectRefs: ['permit_id' => $event->permit->id],
            dormitoryId: $event->permit->dormitory_id ?? null,
            payload: [
                'start_date' => $event->permit->departure_datetime ?? null,
                'end_date' => $event->permit->expected_return_datetime ?? null,
                'reason' => $event->permit->purpose ?? null,
                'leave_type' => $event->permit->permit_type ?? null,
                'note' => $event->approvalNote ?? null,
            ],
            module: 'boarding',
            category: 'leave',
            eventAt: \Illuminate\Support\CarbonImmutable::now(),
            sourceActorId: null,
        );
    }
}