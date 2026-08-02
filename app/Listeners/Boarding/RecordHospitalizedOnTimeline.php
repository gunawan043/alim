<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\HealthPermitApproved;
use App\Services\TimelineWriter;

class RecordHospitalizedOnTimeline
{
    public function __construct(
        private readonly TimelineWriter $writer,
    ) {}

    public function record(HealthPermitApproved $event): void
    {
        $this->writer->write(
            studentId: $event->student->id,
            eventType: 'health.hospitalized',
            subjectRefs: ['permit_id' => $event->permit->id],
            dormitoryId: $event->permit->dormitory_id ?? null,
            payload: [
                'permit_type' => $event->permit->permit_type,
                'description' => $event->permit->description ?? null,
                'start_date' => $event->permit->start_date,
                'end_date' => $event->permit->end_date,
                'note' => $event->note ?? null,
            ],
            module: 'boarding',
            category: 'health',
            eventAt: \Carbon\CarbonImmutable::now(),
            sourceActorId: null,
        );
    }
}
