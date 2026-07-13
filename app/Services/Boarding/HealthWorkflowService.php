<?php

namespace App\Services\Boarding;

use App\Domain\Services\BoardingRulesEngine;
use App\Domain\Services\BoardingTimelineService;
use App\Domain\Types\DefaultBoardingContext;
use App\Models\BoardingTimelineEvent;
use App\Models\BoardingPolicy;
use App\Models\Dormitory;
use App\Models\Student;
use App\Models\StudentBoardingStatus;
use App\Models\StudentHealthPermit;
use App\Models\WaliSantri;
use App\Models\NotificationUniversal;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Health permit lifecycle:
 *
 *   submit()     → Rules Engine → create (pending)
 *   approve()    → status: approved (timeline: HOSPITALIZED)
 *                  + StudentStatusService.transition(AT_HOSPITAL)
 *   reject()     → status: rejected (timeline: PERMIT_REJECTED)
 *   discharge()  → mark end_date as today, timeline: RECOVERED
 *                  + StudentStatusService.transition(IN_DORM)
 *   notifyParent()  → optional: mark parent_notified=true (timeline: not auto-recorded)
 *
 * Unlike Leave, this permit doesn't always mean the student left the dormitory.
 * It transitions the student's current location to AT_HOSPITAL so attendance &
 * meal planning know they're unavailable until discharge.
 */
class HealthWorkflowService
{
    public function __construct(
        private readonly BoardingRulesEngine $engine,
        private readonly BoardingTimelineService $timeline,
        private readonly StudentStatusService $status,
    ) {}

    public function submit(array $data): StudentHealthPermit
    {
        $student = Student::find($data['student_id']);
        if ($student && ! empty($data['dormitory_id'])) {
            $policy = BoardingPolicy::where('dormitory_id', $data['dormitory_id'])
                ->where('student_id', $student->id)
                ->first();
            $context = DefaultBoardingContext::hospitalized(
                $student,
                Dormitory::find($data['dormitory_id']),
                $policy
            );
            // Engine evaluation is recorded for audit, but doesn't gate submission.
            $this->engine->evaluate($context);
        }

        $data['school_id'] = $student->school_id;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'pending';

        return DB::transaction(fn () => StudentHealthPermit::create($data));
    }

    public function update(string $permitId, array $data): StudentHealthPermit
    {
        return DB::transaction(function () use ($permitId, $data) {
            $permit = StudentHealthPermit::findOrFail($permitId);
            $permit->update($data);

            return $permit;
        });
    }

    public function approve(string $permitId, ?string $note = null): StudentHealthPermit
    {
        return DB::transaction(function () use ($permitId, $note) {
            $permit = StudentHealthPermit::findOrFail($permitId);

            $permit->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            $this->timeline->record(
                studentId: $permit->student_id,
                eventType: BoardingTimelineEvent::TYPE_HOSPITALIZED,
                dormitoryId: $permit->dormitory_id,
                eventAt: CarbonImmutable::now(),
                subjectRefs: [
                    'subject_type' => 'StudentHealthPermit',
                    'subject_id' => $permit->id,
                ],
                payload: [
                    'permit_type' => $permit->permit_type,
                    'description' => $permit->description,
                    'start_date' => $permit->start_date?->toDateString(),
                    'rest_days' => $permit->rest_days,
                    'approval_note' => $note,
                ],
                recordedBy: Auth::id(),
            );

            // If student is currently IN_DORM, transition them to AT_HOSPITAL
            if ($permit->dormitory_id && $permit->student_id) {
                $current = StudentBoardingStatus::where('student_id', $permit->student_id)->first();
                if ($current && $current->status === StudentBoardingStatus::IN_DORM) {
                    $this->status->transition(
                        studentId: $permit->student_id,
                        to: StudentBoardingStatus::AT_HOSPITAL,
                        reason: 'health_permit_approved:'.$permit->id,
                        dormitoryId: $permit->dormitory_id,
                    );
                }
            }

            $this->notifyWali($permit, 'approved', $note);

            return $permit;
        });
    }

    public function reject(string $permitId, ?string $note = null): StudentHealthPermit
    {
        return DB::transaction(function () use ($permitId, $note) {
            $permit = StudentHealthPermit::findOrFail($permitId);

            $permit->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_note' => $note,
            ]);

            $this->timeline->record(
                studentId: $permit->student_id,
                eventType: BoardingTimelineEvent::TYPE_PERMIT_REJECTED,
                dormitoryId: $permit->dormitory_id,
                eventAt: CarbonImmutable::now(),
                subjectRefs: [
                    'subject_type' => 'StudentHealthPermit',
                    'subject_id' => $permit->id,
                ],
                payload: [
                    'permit_type' => $permit->permit_type,
                    'approval_note' => $note,
                ],
                recordedBy: Auth::id(),
            );

            $this->notifyWali($permit, 'rejected', $note);

            return $permit;
        });
    }

    public function discharge(string $permitId, ?CarbonImmutable $dischargedAt = null): StudentHealthPermit
    {
        return DB::transaction(function () use ($permitId, $dischargedAt) {
            $permit = StudentHealthPermit::findOrFail($permitId);
            $dischargedAt = $dischargedAt ?? CarbonImmutable::now();

            // Use the discharge date as the new end_date if it's sooner than the planned end
            if (! $permit->end_date || $permit->end_date->isAfter($dischargedAt)) {
                $permit->end_date = $dischargedAt->toDate();
                $permit->save();
            }

            // Record recovery on the timeline
            $this->timeline->record(
                studentId: $permit->student_id,
                eventType: BoardingTimelineEvent::TYPE_RECOVERED,
                dormitoryId: $permit->dormitory_id,
                eventAt: $dischargedAt,
                subjectRefs: [
                    'subject_type' => 'StudentHealthPermit',
                    'subject_id' => $permit->id,
                ],
                payload: [
                    'discharged_at' => $dischargedAt->toIso8601String(),
                    'permit_type' => $permit->permit_type,
                ],
                recordedBy: Auth::id(),
            );

            // Flip student status back to IN_DORM
            $current = StudentBoardingStatus::where('student_id', $permit->student_id)->first();
            if ($current && $current->status === StudentBoardingStatus::AT_HOSPITAL) {
                $this->status->transition(
                    studentId: $permit->student_id,
                    to: StudentBoardingStatus::IN_DORM,
                    reason: 'health_permit_discharged:'.$permit->id,
                    dormitoryId: $permit->dormitory_id,
                );
            }

            return $permit;
        });
    }

    public function notifyParent(string $permitId): StudentHealthPermit
    {
        return DB::transaction(function () use ($permitId) {
            $permit = StudentHealthPermit::findOrFail($permitId);
            $permit->update([
                'parent_notified' => true,
                'parent_notified_at' => now(),
                'parent_notified_by' => Auth::id(),
            ]);

            return $permit;
        });
    }

    /**
     * Kirim notifikasi ke wali saat izin disetujui/ditolak.
     */
    protected function notifyWali(StudentHealthPermit $permit, string $decision, ?string $note): void
    {
        $student = $permit->student;
        if (! $student) {
            return;
        }

        $wals = WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->whereNotNull('user_id')
            ->get();

        if ($wals->isEmpty()) {
            return;
        }

        $isApproved = $decision === 'approved';

        foreach ($wals as $wali) {
            NotificationUniversal::create([
                'user_id'         => $wali->user_id,
                'module'          => 'boarding',
                'type'            => 'health_decision',
                'action'          => $decision,
                'title'           => $isApproved ? 'Izin Sakit Diterima' : 'Izin Sakit Ditolak',
                'message'         => $isApproved
                    ? "Izin sakit '{$permit->permit_type}' untuk {$student->name} telah disetujui."
                    : "Izin sakit '{$permit->permit_type}' untuk {$student->name} ditolak." . ($note ? " Alasan: {$note}" : ''),
                'reference_type'  => StudentHealthPermit::class,
                'reference_id'    => $permit->id,
                'action_url'      => route('user.asrama.approval-center', [
                    'userId'   => $wali->user_id,
                    'asramaUuid' => $permit->dormitory_id,
                ]),
                'action_text'     => 'Lihat Detail',
                'is_read'         => false,
                'priority'        => $isApproved ? 'medium' : 'high',
            ]);
        }
    }
}