<?php

namespace App\Services\Boarding;

use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Domain\Exceptions\QuotaExceededException;
use App\Domain\Services\BoardingRulesEngine;
use App\Domain\Services\BoardingTimelineService;
use App\Domain\Types\DefaultBoardingContext;
use App\Domain\Types\QuotaPeriod;
use App\Models\BoardingPolicy;
use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\DormitoryVisitLog;
use App\Models\DormitoryVisitorRestriction;
use App\Models\Student;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use App\Services\NotificationUniversalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $this->assertVisitorNotRestricted($data, $dormitoryId);

        $visitorCount = max(1, (int) ($data['visitor_count'] ?? 1));

        if ($student) {
            $policy = $this->resolvePolicyForDormitory($dormitoryId);

            $context = DefaultBoardingContext::visitRequest(
                $student,
                Dormitory::find($dormitoryId),
                $policy,
                $visitorCount
            );

            $decision = $this->engine->evaluate($context);

            // Blocking: quota exhausted (require override) dan banned/kapasitas
            // ditolak harus berhenti di sini. Tanpa ini, requireOverride
            // dianggap "allowed" dan pengajuan tetap tersimpan melewati kuota.
            if ($decision->isDenied() || $decision->requiresOverride()) {
                $denyReason = $decision->firstDenyReason()
                    ?: 'Kuota kunjungan sudah habis. Selesaikan periode atau ajukan izin khusus.';

                throw new QuotaExceededException(
                    $denyReason,
                    [
                        'policy_code' => $decision->toArray()['rule_results'][0]['policy_code'] ?? null,
                        'strategy' => $decision->toArray()['rule_results'][0]['metadata']['strategy'] ?? null,
                    ]
                );
            }
        }

        $data['dormitory_id'] = $dormitoryId;
        $data['created_by'] = auth()->id();
        $data['visitor_count'] = max(1, (int) ($data['visitor_count'] ?? 1));

        return DB::transaction(fn () => DormitoryVisitLog::create($data));
    }

    private function assertVisitorNotRestricted(array $data, string $dormitoryId): void
    {
        $name = trim((string) ($data['visitor_name'] ?? ''));
        $idNumber = trim((string) ($data['visitor_id_number'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($data['visitor_phone'] ?? ''));

        $restriction = DormitoryVisitorRestriction::query()
            ->where('dormitory_id', $dormitoryId)
            ->where('is_active', true)
            ->where(function ($query) use ($name, $idNumber, $phone) {
                if ($idNumber !== '') {
                    $query->orWhere('id_number', $idNumber);
                }
                if ($phone !== '') {
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ?", [$phone]);
                }
                if ($name !== '') {
                    $query->orWhereRaw('LOWER(visitor_name) = ?', [mb_strtolower($name)]);
                }
            })
            ->first();

        if ($restriction) {
            throw new \InvalidArgumentException(
                'Tamu ini masuk blacklist asrama: '.$restriction->reason
            );
        }
    }

    /**
     * Kebijakan boarding aktif untuk sebuah asrama (prioritas assignment).
     */
    public function resolvePolicyForDormitory(string $dormitoryId): ?BoardingPolicy
    {
        return BoardingPolicy::query()
            ->select('boarding_policies.*')
            ->join('dormitory_policy_assignments', 'dormitory_policy_assignments.boarding_policy_id', '=', 'boarding_policies.id')
            ->where('dormitory_policy_assignments.target_id', $dormitoryId)
            ->where('dormitory_policy_assignments.policy_assignment_type', 'dormitory')
            ->where('boarding_policies.is_active', true)
            ->orderByDesc('dormitory_policy_assignments.priority')
            ->orderByDesc('dormitory_policy_assignments.effective_from')
            ->first();
    }

    /**
     * Simpan kebijakan KUNJUNGAN khusus asrama (per asrama, bukan global).
     * Membuat/memperbarui BoardingPolicy milik asrama + assignment-nya.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDormitoryPolicy(string $dormitoryId, array $data): BoardingPolicy
    {
        $policy = $this->resolvePolicyForDormitory($dormitoryId);

        if (! $policy) {
            $policy = BoardingPolicy::create([
                'id' => (string) Str::uuid(),
                'code' => 'VISIT-'.strtoupper(substr($dormitoryId, 0, 8)),
                'name' => 'Kebijakan Kunjungan Asrama',
                'is_active' => true,
            ]);

            DB::table('dormitory_policy_assignments')->insert([
                'id' => (string) Str::uuid(),
                'boarding_policy_id' => $policy->id,
                'target_id' => $dormitoryId,
                'policy_assignment_type' => 'dormitory',
                'priority' => 1,
                'effective_from' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $policy->forceFill([
            'visit_strategy' => $data['visit_strategy'],
            'visit_quota' => $data['visit_quota'] ?? null,
            'visit_quota_period' => $data['visit_quota_period'] ?? 'monthly',
            'max_visitors_per_visit' => $data['max_visitors_per_visit'] ?? null,
            'is_active' => true,
        ])->save();

        return $policy->fresh();
    }

    /**
     * Informasi kuota kunjungan keluarga per santri (per periode kebijakan) —
     * dipakai panel jatah & modal limit di UI.
     *
     * @return array<string, mixed>
     */
    public function inspectQuotaForVisit(string $studentId, string $dormitoryId): array
    {
        $policy = $this->resolvePolicyForDormitory($dormitoryId);
        $strategy = $policy?->visit_strategy ?? 'unrestricted';
        $period = $policy?->visit_quota_period ?? 'monthly';
        $quota = $policy?->visit_quota;

        $used = 0;
        if ($policy && $strategy === 'quota') {
            $used = $this->countVisitsForCurrentPeriod($studentId, $dormitoryId, $period);
        }

        return [
            'strategy' => $strategy,
            'quota' => $quota !== null ? (int) $quota : null,
            'used' => (int) $used,
            'period' => $period,
            'remaining' => $quota !== null ? max(0, (int) $quota - (int) $used) : null,
            'is_unrestricted' => $strategy === 'unrestricted',
            'is_banned' => $strategy === 'banned',
            'max_visitors' => $policy?->max_visitors_per_visit,
            'period_label_id' => match ($period) {
                'weekly' => 'minggu ini',
                'monthly' => 'bulan ini',
                'semester' => 'semester ini',
                'yearly' => 'tahun ini',
                default => 'periode ini',
            },
        ];
    }

    /**
     * Hitung satu kunjungan satu kali, bukan per event timeline.
     * Event approve + check-in untuk kunjungan yang sama tidak boleh
     * dihitung sebagai dua pemakaian kuota.
     */
    public function countVisitsForCurrentPeriod(string $studentId, string $dormitoryId, string $period): int
    {
        $rangeStart = QuotaPeriod::rangeBound($period);
        $rangeEnd = match ($period) {
            QuotaPeriod::WEEKLY => $rangeStart->copy()->addWeek(),
            QuotaPeriod::MONTHLY => $rangeStart->copy()->addMonth(),
            QuotaPeriod::SEMESTER => $rangeStart->copy()->addMonths(6),
            QuotaPeriod::YEARLY => $rangeStart->copy()->addYear(),
            QuotaPeriod::DAILY => $rangeStart->copy()->addDay(),
            default => $rangeStart->copy()->addMonth(),
        };

        return DormitoryVisitLog::query()
            ->where('student_id', $studentId)
            ->where('dormitory_id', $dormitoryId)
            ->whereIn('status', ['approved', 'arrived', 'checked_out'])
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereBetween('approved_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($fallback) use ($rangeStart, $rangeEnd) {
                        $fallback->whereNull('approved_at')
                            ->whereBetween('created_at', [$rangeStart, $rangeEnd]);
                    });
            })
            ->count();
    }

    public function approve(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        if ($visit->status === 'pending') {
            $quota = $this->inspectQuotaForVisit($visit->student_id, $dormitoryId);

            if (! $visit->is_special_permission
                && ($quota['is_banned'] || ($quota['remaining'] !== null && $quota['remaining'] <= 0))) {
                throw new QuotaExceededException(
                    $quota['is_banned']
                        ? 'Kunjungan sedang dinonaktifkan untuk asrama ini.'
                        : 'Kuota kunjungan sudah habis. Permintaan tidak dapat disetujui.'
                );
            }
        }

        $visit = DB::transaction(function () use ($visit, $note) {
            $visit->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_note' => $note,
                'schedule_exception_approved_by' => $visit->is_special_permission ? auth()->id() : $visit->schedule_exception_approved_by,
                'schedule_exception_approved_at' => $visit->is_special_permission ? now() : $visit->schedule_exception_approved_at,
            ]);

            // Timeline VISIT_APPROVED dicatat oleh listener BoardingVisitDecided
            // agar satu event domain hanya menghasilkan satu baris timeline.

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

        $this->notifyVisitDecision($visit->fresh(['student']), 'approved');

        return $visit;
    }

    public function reject(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        $visit = DB::transaction(function () use ($visit, $note) {
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

        $this->notifyVisitDecision($visit->fresh(['student']), 'rejected');

        return $visit;
    }

    /**
     * Notifikasi WA wali + in-app petugas asrama setelah keputusan kunjungan.
     * Dipusatkan di service agar jalur approve manual, auto-approve, dan
     * approval center sama-sama mengirim notifikasi.
     */
    private function notifyVisitDecision(DormitoryVisitLog $visit, string $stage): void
    {
        try {
            app(GuardianWhatsAppNotifier::class)
                ->notifyVisit($visit, $stage);
        } catch (\Throwable $e) {
            Log::warning('WA notif kunjungan gagal: '.$e->getMessage());
        }

        try {
            $approved = $stage === 'approved';

            app(NotificationUniversalService::class)->notifyDormitoryStaff($visit->dormitory_id, [
                'module' => 'dormitory',
                'reference_type' => 'dormitory_visit',
                'reference_id' => $visit->id,
                'type' => $approved ? 'info' : 'warning',
                'action' => $approved ? 'visit_approved' : 'visit_rejected',
                'title' => ($approved ? 'Kunjungan disetujui: ' : 'Kunjungan ditolak: ').($visit->student?->name ?? '-'),
                'message' => 'Pengunjung '.($visit->visitor_name ?? '-').' dijadwalkan '
                    .($visit->expected_arrival_datetime?->format('d/m/Y H:i') ?? '-').'.',
                'priority' => 'low',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notif in-app kunjungan gagal: '.$e->getMessage());
        }
    }

    public function checkIn(string $visitId, string $dormitoryId, ?string $note = null): DormitoryVisitLog
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $dormitoryId)->findOrFail($visitId);

        return DB::transaction(function () use ($visit) {
            $visit->update([
                'status' => 'arrived',
                'actual_arrival_datetime' => now(),
                'check_in_at' => now(),
                'identity_verified_at' => $visit->identity_verified_at ?: now(),
                'identity_verified_by' => $visit->identity_verified_by ?: auth()->id(),
                'card_verified_at' => $visit->card_verified_at ?: now(),
                'card_verified_by' => $visit->card_verified_by ?: auth()->id(),
                'rules_acknowledged_at' => $visit->rules_acknowledged_at ?: now(),
            ]);

            // Timeline VISIT_CHECK_IN dicatat oleh listener BoardingVisitCheckIn.

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
                'visit_id' => $visit->id,
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
