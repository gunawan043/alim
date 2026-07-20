<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\WaliSantriService;
use App\Models\StudentAttendance;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private WaliSantriService $waliService;

    public function __construct(WaliSantriService $waliService)
    {
        $this->waliService = $waliService;
    }

    // ── GET /api/mobile/v1/dashboard ────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->waliService->getDashboard($user);

        // ── Add Dormitory Stats (Sprint 4 - Gap: dashboard stats) ──
        $dormitoryStats = $this->getDormitoryStats($user);
        $data['dormitory'] = $dormitoryStats;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ── GET /api/mobile/v1/dashboard/attendance ─────────────────────────────

    public function attendance(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->query('date', now()->format('Y-m-d'));

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->where('school_id', $request->attributes->get('schoolContextId'))
            ->active()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => ['attendances' => [], 'date' => $date],
            ]);
        }

        $attendances = StudentAttendance::with('student:id,name,nisn')
            ->whereIn('student_id', $studentIds)
            ->where('attendance_date', $date)
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'student' => [
                    'id' => $att->student->id,
                    'name' => $att->student->name,
                    'nisn' => $att->student->nisn,
                ],
                'status' => $att->status,
                'arrival_time' => $att->arrival_time?->format('H:i'),
                'notes' => $att->notes,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'attendances' => $attendances,
                'total_students' => $studentIds->count(),
                'total_present' => $attendances->where('status', 'hadir')->count(),
                'total_permit' => $attendances->where('status', 'izin')->count(),
                'total_sick' => $attendances->where('status', 'sakit')->count(),
                'total_absent' => $attendances->where('status', 'alpa')->count(),
            ],
        ]);
    }

    /**
     * Gather dormitory-related summary stats for the dashboard.
     */
    private function getDormitoryStats(object $user): array
    {
        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id')
            ->toArray();

        if (empty($studentIds)) {
            return [
                'dormitory_active' => false,
                'pending_permits' => 0,
                'pending_returns' => 0,
                'pending_visits' => 0,
                'recent_violations' => [],
                'recent_rewards' => [],
            ];
        }

        // Pending permits (leave + dormitory permit)
        $pendingPermits = 0;

        // Use DormitoryPermit if available
        $dpCount = \DB::table('dormitory_permits')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'pending')
            ->count();
        $pendingPermits += $dpCount;

        // Pending returns (DormitoryReturnLog)
        $pendingReturns = \DB::table('dormitory_return_logs')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'registered')
            ->count();

        // Pending visits (DormitoryVisitLog)
        $pendingVisits = \DB::table('dormitory_visit_logs')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'pending')
            ->count();

        // Recent violations (last 3)
        $recentViolations = \DB::table('dormitory_violations')
            ->select('violation_type', 'points', 'violation_date', 'action_taken', 'student_id')
            ->whereIn('student_id', $studentIds)
            ->orderBy('violation_date', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($v) => [
                'type' => $v->violation_type ?? '-',
                'points' => $v->points ?? 0,
                'date' => $v->violation_date ? \Carbon\Carbon::parse($v->violation_date)->format('d M Y') : '-',
                'action' => $v->action_taken ?? '-',
            ])
            ->toArray();

        // Recent rewards (last 3)
        $recentRewards = \DB::table('dormitory_rewards')
            ->select('title', 'category', 'awarded_date', 'student_id')
            ->whereIn('student_id', $studentIds)
            ->orderBy('awarded_date', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($r) => [
                'title' => $r->title ?? '-',
                'category' => $r->category ?? '-',
                'date' => $r->awarded_date ? \Carbon\Carbon::parse($r->awarded_date)->format('d M Y') : '-',
            ])
            ->toArray();

        return [
            'dormitory_active' => true,
            'pending_permits' => $pendingPermits,
            'pending_returns' => $pendingReturns,
            'pending_visits' => $pendingVisits,
            'recent_violations' => $recentViolations,
            'recent_rewards' => $recentRewards,
        ];
    }
}
