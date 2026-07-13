<?php

namespace App\Services\Boarding;

use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Services\BoardingRulesEngine;
use App\Domain\Services\BoardingTimelineService;
use App\Domain\Types\DefaultBoardingContext;
use App\Events\Boarding\LeaveApproved;
use App\Events\Boarding\LeaveReturned;
use App\Models\BoardingTimelineEvent;
use App\Models\DormitoryPermit;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\StudentBoardingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Full leave-request lifecycle:
 *
 *   submit()              → Rules Engine → create permit
 *   approve()             → Rules Engine → change status ON_LEAVE
 *   reject()              → update status PERMIT_REJECTED, keep IN_DORM
 *   recordReturn()        → update status IN_DORM, mark quota consumed
 *
 * Every state change goes through StudentStatusService (single source of truth)
 * and emits a timeline event (full audit trail).
 */
class LeaveWorkflowService
{
    public function __construct(
        private readonly BoardingRulesEngine $engine,
        private readonly BoardingTimelineService $timeline,
        private readonly StudentStatusService $status,
    ) {}

    // ── Submit ──────────────────────────────────────────────────

    /**
     * Validate & submit a new leave request.
     *
     * @param  array<string, mixed>  $data  Validated from StorePermitRequest
     */
    public function submit(array $data, string $dormitoryId, string $activeYearId): DormitoryPermit
    {
        $student = Student::find($data['student_id']);

        // 1. Pre-submit policy evaluation (run through Rules Engine for context,
        //    but never block submission here — the controller layer may choose to).
        $policy = $this->resolvePolicy($student, $dormitoryId);
        $departure = CarbonImmutable::parse($data['departure_datetime']);
        $dorm = \App\Models\Dormitory::find($dormitoryId);
        $context = new DefaultBoardingContext(
            $student,
            $dorm,
            $policy,
            'leave_request',
            $departure,
            ['permit_type' => $data['permit_type']],
            [],
            false
        );

        $this->engine->evaluate($context);

        // Permit creation is NOT blocked by rules — we record the outcome even on denial.
        // The controller can still block at UI level if desired.

        // Mahrom resolution (UI already fills some fields, but we validate here)
        if (! empty($data['mahrom_id'])) {
            $mahrom = StudentMahrom::where('id', $data['mahrom_id'])
                ->where('student_id', $data['student_id'])
                ->where('is_active', true)
                ->first();
            if ($mahrom) {
                $data['companion_name'] = $mahrom->name;
                $data['companion_relation'] = $mahrom->relationship_text;
                $data['companion_phone'] = $mahrom->phone;
            }
        }
        $data['companion_is_mahrom'] = isset($data['companion_is_mahrom']);

        $data['dormitory_id'] = $dormitoryId;
        $data['academic_year_id'] = $activeYearId;
        $data['status'] = 'pending';
        $data['id'] = $data['id'] ?? (string) Str::uuid();

        return DB::transaction(function () use ($data) {
            $permit = DormitoryPermit::create($data);
            DB::afterCommit(function () use ($permit) {
                Event::dispatch(new BoardingPermitSubmitted($permit));
            });
            return $permit;
        });
    }

    // ── Approve ─────────────────────────────────────────────────

    /**
     * Approve a permit → student moves to ON_LEAVE status.
     *
     * This calls the Rules Engine a second time to double-check approval is
     * consistent with current policy (quota may have changed since submission).
     */
    public function approve(string $permitId, string $dormitoryId, ?string $note = null): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);

        return DB::transaction(function () use ($permit, $dormitoryId, $note) {
            // 1. Post-approval policy re-evaluation
            $student = $permit->student;
            $policy = $this->resolvePolicy($student, $dormitoryId);
            $context = new DefaultBoardingContext(
                $student,
                $student->dormitory ?? \App\Models\Dormitory::find($dormitoryId),
                $policy,
                'leave_approval',
                CarbonImmutable::now(),
                ['permit_type' => $permit->permit_type],
                [],
                false
            );
            $decision = $this->engine->evaluate($context);

            // 2. Update permit
            $permit->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            // 3. Transition student status to ON_LEAVE
            $expectedReturn = $permit->expected_return_datetime
                ? CarbonImmutable::instance($permit->expected_return_datetime)
                : null;

            $this->status->markOnLeave(
                studentId: $permit->student_id,
                permitId: $permit->id,
                expectedReturnAt: $expectedReturn,
            );

            // 4. Dispatch domain event (deferred to after commit so it never
            //    fires inside a rollback path).
            DB::afterCommit(function () use ($permit, $note) {
                Event::dispatch(new BoardingPermitDecided(
                    permit: $permit,
                    decision: BoardingPermitDecided::APPROVED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            // 5. Dispatch integration event after commit so listeners that
            //    write attendance rows / push notifications never run inside
            //    a transaction that could roll back.
            if ($student = $permit->student) {
                DB::afterCommit(function () use ($permit, $student, $note) {
                    Event::dispatch(new LeaveApproved(
                        permit: $permit,
                        student: $student,
                        approvalNote: $note,
                    ));
                });
            }

            return $permit;
        });
    }

    // ── Reject ──────────────────────────────────────────────────

    /**
     * Reject a permit → student stays IN_DORM (or whatever current status is).
     */
    public function reject(string $permitId, string $dormitoryId, ?string $note = null): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);

        return DB::transaction(function () use ($permit, $note) {
            // Update permit
            $permit->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            // Dispatch after commit to avoid noise if something else in
            // the same transaction later rolls back.
            DB::afterCommit(function () use ($permit, $note) {
                Event::dispatch(new BoardingPermitDecided(
                    permit: $permit,
                    decision: BoardingPermitDecided::REJECTED,
                    decidedBy: auth()->id(),
                    note: $note,
                ));
            });

            return $permit;
        });
    }

    // ── Return ──────────────────────────────────────────────────

    /**
     * Record student return from leave → status back to IN_DORM.
     */
    public function recordReturn(string $permitId, string $dormitoryId, string $actualReturnDatetime): DormitoryPermit
    {
        $permit = DormitoryPermit::where('dormitory_id', $dormitoryId)->findOrFail($permitId);
        $student = $permit->student;

        return DB::transaction(function () use ($permit, $student, $actualReturnDatetime) {
            $permit->update([
                'actual_return_datetime' => $actualReturnDatetime,
                'status' => 'returned',
            ]);

            // Transition status back to IN_DORM
            $this->status->markReturned(
                studentId: $permit->student_id,
                permitId: $permit->id,
            );

            // Dispatch integration event after commit so listeners that
            // touch attendance / notifications never run inside a rollback path.
            if ($student) {
                DB::afterCommit(function () use ($permit, $student) {
                    Event::dispatch(new LeaveReturned(
                        permit: $permit,
                        student: $student,
                        note: 'Student returned from leave.',
                    ));
                });
            }

            return $permit;
        });
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function resolvePolicy(?Student $student, string $dormitoryId): ?\App\Models\BoardingPolicy
    {
        if (! $student) {
            return null;
        }
        return \App\Models\BoardingPolicy::where('dormitory_id', $dormitoryId)
            ->where('student_id', $student->id)
            ->first();
    }
}