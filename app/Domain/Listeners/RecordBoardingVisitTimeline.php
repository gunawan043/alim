<?php

namespace App\Domain\Listeners;

use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Domain\Services\BoardingTimelineService;
use App\Models\BoardingTimelineEvent;

class RecordBoardingVisitTimeline
{
    public function __construct(private BoardingTimelineService $timeline) {}

    public function onDecided(BoardingVisitDecided $event): void
    {
        if ($event->decision !== BoardingVisitDecided::APPROVED) {
            return;
        }

        $visit = $event->visit;
        $this->timeline->record(
            studentId: $visit->student_id,
            eventType: BoardingTimelineEvent::TYPE_VISIT_APPROVED,
            dormitoryId: $visit->dormitory_id,
            roomId: $visit->room_id ?? null,
            subjectRefs: ['visit_id' => $visit->id],
            payload: [
                'visitor_count' => $visit->visitor_count ?? 1,
                'decided_by' => $event->decidedBy,
            ],
            sourceActorId: $event->decidedBy,
        );
    }

    public function onCheckIn(BoardingVisitCheckIn $event): void
    {
        $this->timeline->record(
            studentId: $event->visit->student_id,
            eventType: BoardingTimelineEvent::TYPE_VISIT_CHECK_IN,
            dormitoryId: $event->visit->dormitory_id,
            roomId: $event->visit->room_id ?? null,
            sourceActorId: $event->visit->created_by,
        );
    }
}