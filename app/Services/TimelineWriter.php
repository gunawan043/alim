<?php

namespace App\Services;

use App\Models\BoardingTimelineEvent;
use Illuminate\Support\CarbonImmutable;

/**
 * Unified Student Timeline Writer.
 *
 * All modules write into the timeline through this service.
 * Centralizes event categorization and metadata normalization
 * so different modules produce comparable events.
 *
 * Persists to the boarding_timeline_events table (shared across modules).
 */
class TimelineWriter
{
    /**
     * @param  array<string, mixed>  $subjectRefs
     * @param  array<string, mixed>  $payload
     */
    public function write(
        string $studentId,
        string $eventType,
        array $subjectRefs,
        ?string $dormitoryId,
        array $payload,
        string $module,
        string $category,
        CarbonImmutable $eventAt,
        ?string $sourceActorId = null,
    ): BoardingTimelineEvent {
        return BoardingTimelineEvent::create([
            'event_type' => $eventType,
            'student_id' => $studentId,
            'subject_refs' => json_encode($subjectRefs),
            'dormitory_id' => $dormitoryId,
            'payload' => json_encode($payload),
            'is_special_permission' => false,
            'recorded_by' => null,
            'source_actor_id' => $sourceActorId,
            'source_system' => $module,
            'event_at' => $eventAt,
        ]);
    }
}