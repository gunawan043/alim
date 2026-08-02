<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\LeaveReturned;
use App\Services\TimelineWriter;

class RecordLeaveReturnedOnTimeline
{
    public function __construct(
        private readonly TimelineWriter $writer,
    ) {}

    public function record(LeaveReturned $event): void
    {
        $this->writer->write(
            studentId: $event->student->id,
            eventType: 'leave.returned',
            subjectRefs: ['permit_id' => $event->permit->id],
            dormitoryId: $event->permit->dormitory_id ?? null,
            payload: [
                'actual_return_date' => $event->permit->actual_return_datetime ?? null,
                'note' => $event->note ?? null,
            ],
            module: 'boarding',
            category: 'leave',
            eventAt: \Carbon\CarbonImmutable::now(),
            sourceActorId: null,
        );
    }
}
