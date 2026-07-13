<?php

namespace App\Services\Boarding;

use App\Domain\Services\BoardingTimelineService;
use App\Models\BoardingTimelineEvent;
use App\Models\Student;
use App\Models\StudentBoardingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Single writer of the StudentBoardingStatus table.
 *
 * Every transition is wrapped in a transaction so the row update and the
 * timeline event either both succeed or both fail.
 *
 * Invariants enforced:
 *   - Only states listed in StudentBoardingStatus::allowedTransitions() may follow another.
 *   - At most one row per student (DB unique index + upsert here).
 *   - Every state change emits a BoardingTimelineEvent so the audit stream
 *     matches the live state.
 */
class StudentStatusService
{
    public function __construct(private readonly BoardingTimelineService $timeline) {}

    /**
     * Return the current status for a student. If no row exists, the student
     * is treated as CHECKED_OUT (not yet registered, or archived).
     */
    public function current(string $studentId): StudentBoardingStatus
    {
        $row = StudentBoardingStatus::where('student_id', $studentId)->first();

        if (! $row) {
            $stub = new StudentBoardingStatus;
            $stub->student_id = $studentId;
            $stub->status = StudentBoardingStatus::CHECKED_OUT;
            $stub->effective_from = null;
            $stub->exists = false;
            return $stub;
        }

        return $row;
    }

    /**
     * Return the active status code (no DB roundtrip when status is loaded).
     */
    public function currentStatus(string $studentId): string
    {
        return StudentBoardingStatus::where('student_id', $studentId)->value('status')
            ?? StudentBoardingStatus::CHECKED_OUT;
    }

    /**
     * Atomic transition + timeline event emission.
     *
     * @param  string|null  $contextSubjectType  e.g. 'DormitoryPermit'
     * @param  string|null  $contextSubjectId
     * @param  CarbonImmutable|null  $expectedReturnAt  for ON_LEAVE / AT_HOSPITAL
     */
    public function transition(
        string $studentId,
        string $toStatus,
        ?string $dormitoryId = null,
        ?string $roomId = null,
        ?string $contextSubjectType = null,
        ?string $contextSubjectId = null,
        ?CarbonImmutable $expectedReturnAt = null,
        ?string $note = null,
        ?string $changedByUserId = null,
        ?CarbonImmutable $at = null
    ): StudentBoardingStatus {
        return DB::transaction(function () use (
            $studentId, $toStatus, $dormitoryId, $roomId, $contextSubjectType,
            $contextSubjectId, $expectedReturnAt, $note, $changedByUserId, $at
        ) {
            $student = Student::find($studentId);
            if (! $student) {
                throw new InvalidArgumentException("Student not found: {$studentId}");
            }

            $current = $this->current($studentId);
            $fromStatus = $current->exists ? $current->status : StudentBoardingStatus::CHECKED_OUT;

            if ($fromStatus === $toStatus) {
                return $current;
            }

            $allowed = StudentBoardingStatus::allowedTransitions()[$fromStatus] ?? [];
            if (! in_array($toStatus, $allowed, true)) {
                throw new InvalidArgumentException(
                    "Illegal status transition: {$fromStatus} -> {$toStatus} for student {$studentId}"
                );
            }

            $effectiveAt = $at ?? CarbonImmutable::now();

            // Resolve dorm/room fallback: when going IN_DORM we copy from the
            // resident assignment; when leaving we let the caller pass them.
            if ($toStatus === StudentBoardingStatus::IN_DORM && (! $dormitoryId || ! $roomId)) {
                $resident = \App\Models\DormitoryResident::where('student_id', $studentId)
                    ->where('is_active', true)
                    ->first();
                if ($resident) {
                    $dormitoryId ??= $resident->dormitory_id;
                    $roomId ??= $resident->room_id;
                }
            }

            $row = StudentBoardingStatus::updateOrCreate(
                ['student_id' => $studentId],
                [
                    'status' => $toStatus,
                    'dormitory_id' => $dormitoryId,
                    'room_id' => $roomId,
                    'effective_from' => $effectiveAt,
                    'expected_return_at' => $expectedReturnAt,
                    'context_subject_type' => $contextSubjectType,
                    'context_subject_id' => $contextSubjectId,
                    'note' => $note,
                    'changed_by_user_id' => $changedByUserId ?? auth()->id(),
                    'last_event_at' => $effectiveAt,
                ]
            );

            // Emit the corresponding timeline event.
            $eventType = $this->timelineEventFor($toStatus);
            if ($eventType) {
                $this->timeline->record(
                    studentId: $studentId,
                    eventType: $eventType,
                    dormitoryId: $dormitoryId,
                    roomId: $roomId,
                    boardingPolicyId: null,
                    eventAt: $effectiveAt,
                    subjectRefs: $contextSubjectType ? [
                        'subject_type' => $contextSubjectType,
                        'subject_id' => $contextSubjectId,
                    ] : null,
                    payload: [
                        'from_status' => $fromStatus,
                        'to_status' => $toStatus,
                        'note' => $note,
                    ],
                    recordedBy: $changedByUserId,
                );
            }

            return $row;
        });
    }

    private function timelineEventFor(string $status): ?string
    {
        return match ($status) {
            StudentBoardingStatus::IN_DORM => BoardingTimelineEvent::TYPE_CHECK_IN,
            StudentBoardingStatus::ON_LEAVE => BoardingTimelineEvent::TYPE_LEAVE_APPROVED,
            StudentBoardingStatus::AT_HOSPITAL => BoardingTimelineEvent::TYPE_HOSPITALIZED,
            StudentBoardingStatus::OFFICIAL_ACTIVITY => BoardingTimelineEvent::TYPE_TRANSFER,
            StudentBoardingStatus::CHECKED_OUT => null,
            default => null,
        };
    }

    /**
     * Convenience helpers used by the workflows in Phase 2-4.
     */
    public function markOnLeave(string $studentId, string $permitId, ?CarbonImmutable $expectedReturnAt = null): StudentBoardingStatus
    {
        return $this->transition(
            studentId: $studentId,
            toStatus: StudentBoardingStatus::ON_LEAVE,
            contextSubjectType: 'DormitoryPermit',
            contextSubjectId: $permitId,
            expectedReturnAt: $expectedReturnAt,
            note: 'Permit approved, student checked out for home leave.',
        );
    }

    public function markReturned(string $studentId, string $permitId): StudentBoardingStatus
    {
        return $this->transition(
            studentId: $studentId,
            toStatus: StudentBoardingStatus::IN_DORM,
            contextSubjectType: 'DormitoryPermit',
            contextSubjectId: $permitId,
            note: 'Student returned from leave.',
        );
    }

    public function markHospitalized(string $studentId, string $referralId, ?CarbonImmutable $expectedReturnAt = null): StudentBoardingStatus
    {
        return $this->transition(
            studentId: $studentId,
            toStatus: StudentBoardingStatus::AT_HOSPITAL,
            contextSubjectType: 'BoardingHospitalization',
            contextSubjectId: $referralId,
            expectedReturnAt: $expectedReturnAt,
            note: 'Student referred to hospital.',
        );
    }

    public function markRecovered(string $studentId, string $referralId): StudentBoardingStatus
    {
        return $this->transition(
            studentId: $studentId,
            toStatus: StudentBoardingStatus::IN_DORM,
            contextSubjectType: 'BoardingHospitalization',
            contextSubjectId: $referralId,
            note: 'Student recovered, returned to dormitory.',
        );
    }
}