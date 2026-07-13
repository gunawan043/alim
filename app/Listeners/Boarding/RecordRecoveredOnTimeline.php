<?php

namespace App\Listeners\Boarding;

use App\Events\Boarding\HealthDischarged;
use App\Services\TimelineWriter;
use Illuminate\Support\CarbonImmutable;

class RecordRecoveredOnTimeline
{
    public function __construct(
        private readonly TimelineWriter $writer,
    ) {}

    public function record(HealthDischarged $event): void
    {
        $this->writer->write(
            studentId: $event->student->id,
            eventType: 'health.discharged',
            subjectRefs: ['permit_id' => $event->permit->id],
            dormitoryId: $event->permit->dormitory_id ?? null,
            payload: [
                'start_date' => $event->permit->start_date,
                'end_date' => $event->permit->end_date,
                'note' => $event->note ?? null,
            ],
            module: 'boarding',
            category: 'health',
            eventAt: \Illuminate\Support\CarbonImmutable::now(),
            sourceActorId: null,
        );
    }
}