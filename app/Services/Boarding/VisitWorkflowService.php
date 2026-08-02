<?php

namespace App\Services\Boarding;

use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Domain\Services\BoardingRulesEngine;
use App\Domain\Services\BoardingTimelineService;
use App\Domain\Types\DefaultBoardingContext;
use App\Models\BoardingPolicy;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryVisitLog;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Visit lifecycle:
 *
 *   submit()    → Rules Engine → create visit row (pending)
 *   approve()   → mark approved (timeline: VISIT_APPROVED)
 *   reject()    → mark rejected (timeline: VISIT_REJECTED)
 *   checkIn()   → status: arrived (timeline: VISIT_CHECKED_IN)
 *   checkOut()  → status: checked_out (timeline: VISIT_CHECKED_OUT)
 *
 * Unlike Leave, a visit does NOT change the student's boarding status.
 * The student remains IN_DORM the whole time. The timeline still records
 * who came and when for audit / wali-notification purposes.
 */
class VisitWorkflowService
{
    public function __construct(
        private readonly BoardingRulesEngine $engine,
        private readonly BoardingTimelineService $timeline,
    ) {}

    public function submit(array $data, string $dormitoryId): DormitoryVisitLog
    {
        $student = ! empty($data['student_id']) ? Student::find($data['student_id']) : null;

        if ($student) {
            $policy = BoardingPolicy::query()
                ->select('boarding_policies.*')
                ->join('dormitory_policy_assignments', 'dormitory_policy_assignments.boarding_policy_id', '=', 'boarding_policies.id')
                ->where('dormitory_policy_assignments.target_id', $dormitoryId)
                ->where('dormitory_policy_assignments.policy_assignment_type', 'dormitory')
                ->where('boarding_policies.is_active', true)
                ->orderByDesc('dormitory_policy_assignments.priority')
                ->orderByDesc('dormitory_policy_assignments.effective_from')
                ->first();

            $context = DefaultBoardingContext::visitRequest($student, Dormitory::find($dormitoryId), $policy);

            $decision = $this->engine->evaluate($context);

            // Blocking: if denied (e.g., quota exhausted) and cannot be bypassed, throw exception
            if ($decision->isDenied()) {
                // Can be bypassed? e.g., special permission exists but requires override.
                if (! $decision->canBeBypassed()) {
                    $denyReason = $decision->firstDenyReason();
                    throw new \App\Domain\Exceptions\QuotaExceededException(
                        $denyReason ?: 'Pengajuan tidak dapat diproses karena melebihi kuota.',
                        [
                            'policy_code' => $decision->toArray()['rule_results'][0]['policy_code'] ?? null,
                            'strategy' => $decision->toArray()['rule_results'][0]['metadata']['strategy'] ?? null,
                        ]
                    );
                }
                // If can be bypassed (special permission needed), we still allow creation
                // so admin can review later. The visit will be flagged for override.
            }
        }

        $data['dormitory_id'] = $dormitoryId;
        $data['created_by'] = auth()->id();

        return DB::transaction(fn () => DormitoryVisitLog::create($data));
    }

    public function approve(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        return DB::transaction(function () use ($visit, $note) {
            $visit->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            $this->recordTimeline($visit, BoardingTimelineEvent::TYPE_VISIT_APPROVED);

            DB::afterCommit(function () use ($visit, $note) {
                Event::dispatch(new BoardingVisitDecided(
                    visit: $visit,
                    decision: BoardingVisitDecided::APPROVED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            return $visit;
        });
    }

    public function reject(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        return DB::transaction(function () use ($visit, $note) {
            $visit->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            $this->recordTimeline($visit, BoardingTimelineEvent::TYPE_VISIT_REJECTED);

            DB::afterCommit(function () use ($visit, $note) {
                Event::dispatch(new BoardingVisitDecided(
                    visit: $visit,
                    decision: BoardingVisitDecided::REJECTED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            return $visit;
        });
    }

    public function checkIn(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        return DB::transaction(function () use ($visit, $note) {
            $visit->update([
                'status' => 'arrived',
                'actual_arrival_datetime' => now(),
                'check_in_at' => now(),
            ]);

            $this->recordTimeline($visit, BoardingTimelineEvent::TYPE_VISIT_CHECK_IN, $note);

            DB::afterCommit(fn () => Event::dispatch(new BoardingVisitCheckIn($visit)));

            return $visit;
        });
    }

    public function checkOut(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        return DB::transaction(function () use ($visit, $note) {
            $visit->update([
                'status' => 'checked_out',
                'departure_datetime' => now(),
                'check_out_at' => now(),
            ]);

            $this->recordTimeline($visit, BoardingTimelineEvent::TYPE_VISIT_CHECK_OUT, $note);

            return $visit;
        });
    }

    private function recordTimeline(DormitoryVisitLog $visit, string $eventType, ?string $note = null): void
    {
        $this->timeline->record(
            studentId: $visit->student_id,
            eventType: $eventType,
            dormitoryId: $visit->dormitory_id,
            roomId: $visit->room_id,
            boardingPolicyId: null,
            eventAt: CarbonImmutable::now(),
            subjectRefs: [
                'subject_type' => 'DormitoryVisitLog',
                'subject_id' => $visit->id,
            ],
            payload: [
                'visitor_name' => $visit->visitor_name,
                'visitor_relationship' => $visit->visitor_relationship,
                'purpose' => $visit->purpose,
                'note' => $note,
            ],
            recordedBy: auth()->id(),
        );
    }
}
