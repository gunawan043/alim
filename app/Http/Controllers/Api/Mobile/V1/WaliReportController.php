<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\DormitoryPermit;
use App\Models\DormitoryReward;
use App\Models\DormitoryVisitLog;
use App\Models\RaportRegistration;
use App\Models\StudentAchievement;
use App\Models\StudentAttendance;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\ViolationPoint;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Laporan ringkas untuk wali: kehadiran bulanan, perizinan, kunjungan,
 * tahfidz, pelanggaran, penghargaan, dan status rapor.
 *
 * GET /api/mobile/v1/wali/reports?student_id=&month=&year=
 */
class WaliReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $links = WaliSantri::with(['student:id,name,nisn,school_id', 'student.school:id,name'])
            ->where('user_id', $request->user()->id)
            ->active()
            ->when($request->filled('student_id'), fn ($query) => $query->where('student_id', $request->string('student_id')->toString()))
            ->get();

        if ($links->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Santri tidak ditemukan atau bukan anak Anda.',
                ],
            ], 404);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $reports = $links->map(fn (WaliSantri $link) => [
            'student' => [
                'id' => $link->student_id,
                'name' => $link->student?->name,
                'nisn' => $link->student?->nisn,
                'school' => $link->student?->school?->name,
            ],
            'period' => ['month' => $month, 'year' => $year],
            'attendance' => $this->attendanceSummary($link->student_id, $month, $year),
            'permits' => $this->permitSummary($link->student_id),
            'visits' => $this->visitSummary($link->student_id),
            'tahfidz' => $this->tahfidzSummary($link->student_id),
            'violations' => $this->violationSummary($link->student_id),
            'achievements' => $this->achievementSummary($link->student_id),
            'rewards' => $this->rewardSummary($link->student_id),
            'raport' => $this->raportSummary($link->student_id),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => ['reports' => $reports],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSummary(string $studentId, int $month, int $year): array
    {
        $rows = StudentAttendance::query()
            ->where('student_id', $studentId)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'hadir' => (int) ($rows['hadir'] ?? 0),
            'izin' => (int) ($rows['izin'] ?? 0),
            'sakit' => (int) ($rows['sakit'] ?? 0),
            'alpa' => (int) ($rows['alpa'] ?? 0),
        ];

        $total = array_sum($counts);

        return $counts + [
            'total' => $total,
            'percentage' => $total > 0 ? round(($counts['hadir'] / $total) * 100, 1) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permitSummary(string $studentId): array
    {
        $counts = DormitoryPermit::where('student_id', $studentId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $latest = DormitoryPermit::where('student_id', $studentId)->latest('created_at')->first();

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'latest' => $latest ? [
                'id' => $latest->id,
                'type' => $latest->permit_type,
                'status' => $latest->status,
                'departure_at' => $latest->departure_datetime?->toIso8601String(),
                'expected_return_at' => $latest->expected_return_datetime?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function visitSummary(string $studentId): array
    {
        $total = DormitoryVisitLog::where('student_id', $studentId)->count();
        $latest = DormitoryVisitLog::where('student_id', $studentId)->latest('created_at')->first();

        return [
            'total' => $total,
            'latest' => $latest ? [
                'id' => $latest->id,
                'visitor_name' => $latest->visitor_name,
                'status' => $latest->status,
                'expected_arrival_at' => $latest->expected_arrival_datetime?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tahfidzSummary(string $studentId): array
    {
        $progress = TahfidzJuzProgress::where('student_id', $studentId)->get();
        $lastSetoran = TahfidzSetoran::where('student_id', $studentId)->latest('setoran_date')->first();

        return [
            'juz_completed' => $progress->where('status', 'selesai')->count(),
            'juz_in_progress' => $progress->where('status', 'berjalan')->count(),
            'average_score' => $progress->avg('avg_nilai_setoran') !== null
                ? round((float) $progress->avg('avg_nilai_setoran'), 1)
                : null,
            'last_setoran' => $lastSetoran ? [
                'date' => $lastSetoran->setoran_date?->toDateString(),
                'juz' => $lastSetoran->juz,
                'type' => $lastSetoran->setoran_type,
                'score' => $lastSetoran->nilai_setoran,
                'status' => $lastSetoran->status,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function violationSummary(string $studentId): array
    {
        $rows = ViolationPoint::where('student_id', $studentId)->get();

        return [
            'total_points' => (int) $rows->sum('points'),
            'total_records' => $rows->count(),
            'recent' => $rows->sortByDesc('violation_date')->take(3)->map(fn ($row) => [
                'date' => $row->violation_date?->toDateString(),
                'type' => $row->violation_type,
                'points' => (int) $row->points,
                'action' => $row->action_taken,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function achievementSummary(string $studentId): array
    {
        $rows = StudentAchievement::where('student_id', $studentId)->get();

        return [
            'total' => $rows->count(),
            'recent' => $rows->sortByDesc('event_date')->take(3)->map(fn ($row) => [
                'name' => $row->event_name,
                'level' => $row->level,
                'position' => $row->position,
                'date' => $row->event_date?->toDateString(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rewardSummary(string $studentId): array
    {
        $rows = DormitoryReward::where('student_id', $studentId)->get();

        return [
            'total' => $rows->count(),
            'total_points' => (int) $rows->sum('points'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function raportSummary(string $studentId): ?array
    {
        $raport = RaportRegistration::where('student_id', $studentId)
            ->latest('created_at')
            ->first();

        if (! $raport) {
            return null;
        }

        return [
            'id' => $raport->id,
            'semester' => $raport->semester,
            'status' => $raport->status,
            'is_published' => (bool) $raport->is_published,
            'final_score' => $raport->final_score,
            'class_rank' => $raport->class_rank,
            'predicate' => $raport->predicate,
            'published_at' => $raport->published_at?->toIso8601String(),
        ];
    }
}
