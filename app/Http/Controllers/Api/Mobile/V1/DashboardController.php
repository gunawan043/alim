<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\WaliSantriService;
use App\Models\WaliSantri;
use App\Models\StudentAttendance;
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
        $user = auth()->user();
        $data = $this->waliService->getDashboard($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ── GET /api/mobile/v1/dashboard/attendance ─────────────────────────────

    public function attendance(Request $request): JsonResponse
    {
        $user = auth()->user();
        $date = $request->query('date', now()->format('Y-m-d'));

        $studentIds = WaliSantri::where('user_id', $user->id)
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
            ->map(fn($att) => [
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
}