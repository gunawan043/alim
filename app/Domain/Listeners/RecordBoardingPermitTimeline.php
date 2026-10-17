<?php

namespace App\Domain\Listeners;

use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Services\BoardingTimelineService;
use App\Models\BoardingTimelineEvent;

class RecordBoardingPermitTimeline
{
    public function __construct(private BoardingTimelineService $timeline) {}

    public function onSubmitted(BoardingPermitSubmitted $event): void
    {
        $permit = $event->permit;
        $this->timeline->record(
            studentId: $permit->student_id,
            eventType: BoardingTimelineEvent::TYPE_PERMIT_SUBMITTED,
            dormitoryId: $permit->dormitory_id,
            roomId: $permit->room_id ?? null,
            subjectRefs: ['permit_id' => $permit->id],
            payload: ['permit_type' => $permit->permit_type, 'status' => 'pending'],
            sourceActorId: $permit->created_by,
        );
    }

    public function onDecided(BoardingPermitDecided $event): void
    {
        $permit = $event->permit;
        $eventType = $event->decision === BoardingPermitDecided::APPROVED
            ? BoardingTimelineEvent::TYPE_LEAVE_APPROVED
            : BoardingTimelineEvent::TYPE_PERMIT_REJECTED;

        $this->timeline->record(
            studentId: $permit->student_id,
            eventType: $eventType,
            dormitoryId: $permit->dormitory_id,
            roomId: $permit->room_id ?? null,
            subjectRefs: ['permit_id' => $permit->id],
            payload: array_filter([
                'permit_type' => $permit->permit_type,
                'note' => $event->note,
                'decided_by' => $event->decidedBy,
                'decision' => $event->decision,
            ], fn ($v) => $v !== null),
            sourceActorId: $event->decidedBy,
        );
    }
}
