<?php

namespace App\Domain\Services;

use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Central service for recording boarding timeline events.
 *
 * Replaces scattered per-action logging (activity logs, permit history, etc.)
 * with a single queryable event stream per student.
 *
 * Also coordinates room-capacity enforcement and quota-bypass flagging.
 */
final class BoardingTimelineService
{
    public function __construct(private readonly BoardingRulesEngine $engine) {}

    /**
     * Record a timeline event and auto-trigger attendance sync if enabled.
     *
     * @param  array<string, mixed>|null  $subjectRefs  Source record references.
     * @param  array<string, mixed>|null  $payload  Domain-specific data.
     * @param  string  $sourceSystem  Who initiated this.
     * @param  string|null  $sourceActorId  Optional actor UUID.
     */
    public function record(
        string $studentId,
        string $eventType,
        ?string $dormitoryId = null,
        ?string $roomId = null,
        ?string $boardingPolicyId = null,
        ?CarbonImmutable $eventAt = null,
        ?array $subjectRefs = null,
        ?array $payload = null,
        ?string $recordedBy = null,
        string $sourceSystem = 'dormitory',
        ?string $sourceActorId = null,
        bool $isSpecialPermission = false,
        bool $skipCheckInRoomCapacity = false
    ): BoardingTimelineEvent {
        $event = new BoardingTimelineEvent;
        $event->student_id = $studentId;
        $event->event_type = $eventType;
        $event->dormitory_id = $dormitoryId;
        $event->room_id = $roomId;
        $event->boarding_policy_id = $boardingPolicyId;
        $event->event_at = $eventAt ?? CarbonImmutable::now();
        $event->subject_refs = $subjectRefs;
        $event->payload = $payload;
        $event->is_special_permission = $isSpecialPermission;
        $event->recorded_by = $recordedBy ?? auth()->id();
        $event->source_actor_id = $sourceActorId;
        $event->source_system = $sourceSystem;
        $event->save();

        // Invalidate the rules-engine and quota-usage caches so that the
        // next evaluate()/canLeave()/canVisit() call sees this event.
        $this->invalidateQuotaCaches($studentId, $eventType);

        // Auto-trigger attendance sync if the policy allows
        if (! $skipCheckInRoomCapacity) {
            $this->maybeSyncAttendance($studentId, $eventType, $dormitoryId, $payload ?? [], $isSpecialPermission);
        }

        return $event;
    }

    /**
     * Invalidate cached quota usage and rules-engine decisions for this
     * student whenever a permit-related timeline event is recorded.
     *
     * Without this, the next decision request would re-use a stale cache
     * hit from before the permit was approved/checked-in/checked-out.
     */
    private function invalidateQuotaCaches(string $studentId, string $eventType): void
    {
        $quotaEvents = [
            BoardingTimelineEvent::TYPE_LEAVE_APPROVED,
            BoardingTimelineEvent::TYPE_LEAVE_STARTED,
            BoardingTimelineEvent::TYPE_LEAVE_RETURNED,
            BoardingTimelineEvent::TYPE_VISIT_APPROVED,
            BoardingTimelineEvent::TYPE_VISIT_CHECK_IN,
        ];

        if (! in_array($eventType, $quotaEvents, true)) {
            return;
        }

        $eventKind = str_starts_with($eventType, 'leave') ? 'leave' : 'visit';

        foreach ([\App\Domain\Types\QuotaPeriod::WEEKLY, \App\Domain\Types\QuotaPeriod::MONTHLY, \App\Domain\Types\QuotaPeriod::SEMESTER, \App\Domain\Types\QuotaPeriod::YEARLY, \App\Domain\Types\QuotaPeriod::DAILY] as $period) {
            // Match the cache key format used by BoardingRulesEngine::countUsageForCurrentPeriod().
            $key = sprintf('usage_%s_%s_%%_%s_%%', $studentId, $eventKind, $period);
            Cache::forget($key);
        }

        // Bust rules-engine decisions tagged with this student's ID — the
        // pattern matches the format used in BoardingRulesEngine::evaluate().
        Cache::forget(sprintf('rules_engine_%s_%%', $studentId));
    }

    /**
     * Query all events for a student within a date range.
     */
    public function getStudentHistory(
        string $studentId,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null
    ): \Illuminate\Support\Collection {
        $query = BoardingTimelineEvent::where('student_id', $studentId)
            ->with(['student:id,name', 'room:id,name', 'dormitory:id,name'])
            ->orderByDesc('event_at');

        if ($from) {
            $query->where('event_at', '>=', $from);
        }

        if ($to) {
            $query->where('event_at', '<=', $to);
        }

        return $query->get();
    }

    /**
     * Query events by type range.
     */
    public function getDormitoryEvents(
        string $dormitoryId,
        ?string $eventType = null,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null
    ): \Illuminate\Support\Collection {
        $query = BoardingTimelineEvent::where('dormitory_id', $dormitoryId)
            ->orderByDesc('event_at');

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        if ($from) {
            $query->where('event_at', '>=', $from);
        }

        if ($to) {
            $query->where('event_at', '<=', $to);
        }

        return $query->get();
    }

    /**
     * Record a leave event through the Rules Engine.
     * Returns the decision outcome AND the timeline event.
     */
    public function recordLeave(
        string $studentId,
        string $permitId,
        string $dormitoryId,
        ?string $roomId = null,
        string $permitType = 'pulang',
        ?string $destination = null,
        ?string $companion = null,
        bool $isSpecial = false
    ): array {
        $student = Student::find($studentId);

        // Allow even if denied — we record the outcome in timeline
        $event = $this->record(
            studentId: $studentId,
            eventType: BoardingTimelineEvent::TYPE_LEAVE_APPROVED,
            dormitoryId: $dormitoryId,
            roomId: $roomId,
            boardingPolicyId: $this->engine->getApplicablePolicy($studentId, $dormitoryId)?->id,
            subjectRefs: ['permit_id' => $permitId],
            payload: [
                'permit_type' => $permitType,
                'destination' => $destination,
                'companion' => $companion,
            ],
            sourceActorId: auth()->id(),
            isSpecialPermission: $isSpecial,
        );

        return ['event' => $event];
    }

    /**
     * Batch import legacy permit events into timeline.
     */
    public function importLegacyPermits(int $perBatch = 1000): int
    {
        $imported = 0;
        $batch = \App\Models\DormitoryPermit::whereDoesntHave('timeline', function ($q) {
            $q->whereRaw('1=0'); // placeholder — no back-ref yet
        })
            ->with('student', 'room')
            ->limit($perBatch)
            ->get();

        foreach ($batch as $permit) {
            if ($permit->status === 'approved' && empty($permit->actual_return_datetime)) {
                $this->record(
                    studentId: $permit->student_id,
                    eventType: BoardingTimelineEvent::TYPE_LEAVE_APPROVED,
                    dormitoryId: $permit->dormitory_id,
                    roomId: $permit->room_id,
                    subjectRefs: ['permit_id' => $permit->id],
                    payload: ['permit_type' => $permit->permit_type],
                    sourceSystem: 'migration',
                );
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Check room capacity when assigning a new dormitory room to a student.
     *
     * @return array{allowed: bool, reason: string}
     */
    public function checkRoomCapacity(string $roomId): array
    {
        $room = DormitoryRoom::with('dormitory')->find($roomId);

        if (! $room) {
            return ['allowed' => false, 'reason' => 'Room not found.'];
        }

        if ($room->capacity === 0) {
            return ['allowed' => false, 'reason' => 'Room capacity is 0.'];
        }

        // If the room has no assigned residents for this slot, it's open
        $currentOccupancy = \App\Models\DormitoryResident::where('room_id', $roomId)
            ->where('is_active', true)
            ->count();

        if ($currentOccupancy >= $room->capacity) {
            return ['allowed' => false, 'reason' => "Room full ({$currentOccupancy}/{$room->capacity})."];
        }

        $available = $room->capacity - $currentOccupancy;

        return ['allowed' => true, 'reason' => "Available spots: {$available}."];
    }

    /**
     * Check if an attendance-sync event should trigger sync.
     *
     * @param  array<string, mixed>  $payload  Additional payload data.
     */
    private function maybeSyncAttendance(
        string $studentId,
        string $eventType,
        ?string $dormitoryId,
        array $payload = [],
        bool $isSpecial = false,
    ): void {
        $student = Student::find($studentId);
        if (! $student) {
            return;
        }

        $dormitory = Dormitory::find($dormitoryId);
        if (! $dormitory) {
            return;
        }

        $policy = $this->engine->getApplicablePolicy($studentId, $dormitoryId);
        $context = new \App\Domain\Types\DefaultBoardingContext(
            $student,
            $dormitory,
            $policy,
            $eventType,
            CarbonImmutable::now(),
            $payload,
            [],
            $isSpecial
        );

        $decision = $this->engine->evaluate($context);
        $syncResult = collect($decision->getRuleResults())->firstWhere('policy_code', 'attendance_sync');

        if ($syncResult && $syncResult->isAllow()) {
            $this->triggerAttendanceSync($studentId, $eventType, $dormitoryId, $payload);
        }
    }

    private function triggerAttendanceSync(
        string $studentId,
        string $eventType,
        ?string $dormitoryId,
        array $payload
    ): void {
        $dormitoryService = app(\App\Services\DormitoryService::class);
        $dormitoryService->syncBoardingAttendance(
            studentId: $studentId,
            eventType: $eventType,
            dormitoryId: $dormitoryId,
            payload: $payload
        );
    }
}
