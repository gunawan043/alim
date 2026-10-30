<?php

namespace App\Services\Dormitory;

use App\Models\DormitoryPointLedger;
use App\Models\DormitoryPointRule;
use App\Models\DormitoryReward;
use App\Models\DormitoryViolation;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DormitoryPointService
{
    public function __construct(private readonly NotificationUniversalService $notifications) {}

    /**
     * Catat pelanggaran ke ledger (poin negatif), lalu terapkan aturan ambang.
     */
    public function recordViolation(DormitoryViolation $violation): void
    {
        $catalog = $violation->pointCatalog;
        $points = abs((int) ($catalog?->points_min ?? $violation->points ?? 0));

        if ($points <= 0) {
            return;
        }

        $semester = $this->currentSemester();
        $total = 0;
        $rule = null;

        DB::transaction(function () use ($violation, $catalog, $points, $semester, &$total, &$rule) {
            DormitoryPointLedger::updateOrCreate(
                [
                    'reference_type' => 'dormitory_violation',
                    'reference_id' => $violation->id,
                ],
                [
                    'student_id' => $violation->student_id,
                    'dormitory_id' => $violation->dormitory_id,
                    'academic_year_id' => $violation->academic_year_id,
                    'semester' => $semester,
                    'kind' => 'violation',
                    'points' => -$points,
                    'is_permanent' => (bool) ($catalog?->is_permanent ?? false),
                    'note' => $catalog?->name ?: $violation->violation_type,
                    'created_by' => $violation->recorded_by ?: auth()->id(),
                ]
            );

            $total = $this->totalsForStudent(
                $violation->student_id,
                $violation->dormitory_id,
                $violation->academic_year_id
            );

            // Default: reward menambah poin positif dan TIDAK mengurangi poin
            // pelanggaran. Offset hanya jika kebijakan mengaktifkannya.
            if (config('asrama_points.reward_can_offset_violation', false)) {
                $accumulated = max(0, -$total);
            } else {
                $accumulated = abs((int) DormitoryPointLedger::query()
                    ->where('student_id', $violation->student_id)
                    ->where('dormitory_id', $violation->dormitory_id)
                    ->where('kind', 'violation')
                    ->when($violation->academic_year_id, fn ($q) => $q->where(function ($q2) use ($violation) {
                        $q2->where('academic_year_id', $violation->academic_year_id)->orWhere('is_permanent', true);
                    }))
                    ->sum('points'));
            }
            $rule = $this->matchRuleForPoints($accumulated);

            $violation->forceFill([
                'point_rule_id' => $rule?->id,
                'recommended_action' => $rule?->action,
                'action_status' => $rule
                    ? ($rule->requires_approval ? 'pending' : 'auto')
                    : 'none',
            ])->save();
        });

        $violation->refresh();
        $this->notifyStaff($violation, $total, $rule);
    }

    /**
     * Catat reward ke ledger (poin positif) dan simpan snapshot poin.
     */
    public function recordReward(DormitoryReward $reward): void
    {
        $catalog = $reward->pointCatalog;
        $points = max(0, (int) ($catalog?->points_min ?? $reward->points ?? 0));
        $semester = $reward->semester ?: $this->currentSemester();
        $recordedAt = now();

        // Prestasi sekolah menggunakan poin penuh dari katalog. Batas reward
        // perilaku per semester hanya berlaku untuk penghargaan non-prestasi.
        $maxPerSemester = $reward->source_achievement_id
            ? null
            : config('asrama_points.max_behavior_reward_per_semester');

        if ($maxPerSemester !== null && is_numeric($maxPerSemester)) {
            $earned = (int) DormitoryPointLedger::query()
                ->where('student_id', $reward->student_id)
                ->where('dormitory_id', $reward->dormitory_id)
                ->where('academic_year_id', $reward->academic_year_id)
                ->where('semester', $semester)
                ->where('kind', 'reward')
                ->where(function ($query) use ($reward) {
                    $query->where('reference_type', '!=', 'dormitory_reward')
                        ->orWhere('reference_id', '!=', $reward->id);
                })
                ->sum('points');

            $points = max(0, min($points, (int) $maxPerSemester - $earned));
        }

        DB::transaction(function () use ($reward, $catalog, $points, $semester, $recordedAt) {
            DormitoryPointLedger::updateOrCreate(
                [
                    'reference_type' => 'dormitory_reward',
                    'reference_id' => $reward->id,
                ],
                [
                    'student_id' => $reward->student_id,
                    'dormitory_id' => $reward->dormitory_id,
                    'academic_year_id' => $reward->academic_year_id,
                    'semester' => $semester,
                    'kind' => 'reward',
                    'points' => $points,
                    'is_permanent' => false,
                    'note' => $catalog?->name ?: $reward->title,
                    'created_by' => $reward->given_by ?: auth()->id(),
                ]
            );

            $reward->forceFill([
                'points' => $points,
                'semester' => $semester,
                'ledger_recorded_at' => $recordedAt,
            ])->save();
        });
    }

    /**
     * Total poin berjalan (reward positif, pelanggaran negatif) untuk satu santri.
     * Poin permanent tetap dihitung walau berasal dari tahun ajaran lain.
     */
    public function totalsForStudent(
        string $studentId,
        ?string $dormitoryId = null,
        ?string $academicYearId = null,
        ?string $semester = null
    ): int {
        $query = DormitoryPointLedger::query()->where('student_id', $studentId);

        if ($dormitoryId) {
            $query->where('dormitory_id', $dormitoryId);
        }

        if ($academicYearId) {
            $query->where(function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId)
                    ->orWhere('is_permanent', true);
            });
        }

        if ($semester) {
            $query->where(function ($q) use ($semester) {
                $q->where('semester', $semester)
                    ->orWhere('is_permanent', true);
            });
        }

        return (int) $query->sum('points');
    }

    public function matchRuleForPoints(int $points): ?DormitoryPointRule
    {
        return DormitoryPointRule::query()
            ->where('is_active', true)
            ->where('min_points', '<=', $points)
            ->where(function ($query) use ($points) {
                $query->whereNull('max_points')
                    ->orWhere('max_points', '>=', $points);
            })
            ->orderByDesc('min_points')
            ->first();
    }

    /**
     * Semester ganjil: Juli–Desember, genap: Januari–Juni.
     */
    public function currentSemester(?Carbon $date = null): string
    {
        return ((int) ($date ?? now())->month) >= 7 ? 'ganjil' : 'genap';
    }

    private function notifyStaff(DormitoryViolation $violation, int $total, ?DormitoryPointRule $rule): void
    {
        if (! $violation->dormitory_id) {
            return;
        }

        $studentName = $violation->student?->name ?? 'Santri';
        $points = abs((int) ($violation->pointCatalog?->points_min ?? $violation->points ?? 0));
        $accumulated = max(0, -$total);
        $action = $rule?->action ?: 'Belum ada tindakan otomatis';

        $url = null;
        if ($violation->recorded_by) {
            try {
                $url = route('user.asrama.violations.show', [
                    'userId' => $violation->recorded_by,
                    'asramaUuid' => $violation->dormitory_id,
                    'violationUuid' => $violation->id,
                ]);
            } catch (\Throwable) {
                $url = null;
            }
        }

        $this->notifications->notifyDormitoryStaff($violation->dormitory_id, [
            'module' => 'dormitory',
            'reference_type' => 'dormitory_violation',
            'reference_id' => $violation->id,
            'type' => $rule?->requires_approval ? 'warning' : 'info',
            'action' => 'dormitory_point_violation',
            'title' => "Poin Pelanggaran — {$studentName}",
            'message' => "{$violation->violation_type} ({$points} poin). "
                ."Akumulasi poin pelanggaran: {$accumulated}. "
                ."Tindakan: {$action}.",
            'action_url' => $url,
            'action_text' => 'Lihat Pelanggaran',
            'priority' => $rule?->requires_approval ? 'high' : 'medium',
            'send_push' => true,
            'data' => [
                'student_id' => $violation->student_id,
                'point_catalog_id' => $violation->point_catalog_id,
                'accumulated_points' => $accumulated,
                'action_status' => $violation->action_status,
            ],
        ]);
    }
}
