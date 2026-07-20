<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\CreateDormitoryVisitRequest;
use App\Models\DormitoryResident;
use App\Models\DormitoryVisitLog;
use App\Models\Student;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DormitoryVisitController extends Controller
{
    // ── POST /api/mobile/v1/dormitory/visit ────────────────────────────
    public function store(CreateDormitoryVisitRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $schoolId = $request->attributes->get('schoolContextId');

        // Verify wali links to this student
        $link = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $data['student_id'])
            ->active()
            ->first();

        if (! $link) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Santri tidak ditemukan atau Anda tidak memiliki akses.',
                ],
            ], 404);
        }

        // Find resident to get dormitory scope
        $resident = DormitoryResident::where('student_id', $data['student_id'])
            ->where('is_active', true)
            ->orderByDesc('check_in_date')
            ->first();

        if (! $resident) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NO_RESIDENT',
                    'message' => 'Santri tidak terdaftar sebagai penghuni aktif.',
                ],
            ], 422);
        }

        // Build visit data with access rights
        $visitData = array_merge($data, [
            'dormitory_id' => $resident->dormitory_id,
            'room_id' => $resident->room_id,
            'academic_year_id' => $resident->academic_year_id,
            'status' => 'pending',
            'visitor_access_rights' => [
                'restricted_areas' => ($data['visitor_relationship'] ?? '') === 'mahrom',
                'can_stay_overnight' => false,
                'guardian_supervision_required' => true,
                'max_visitor_count' => ($data['visitor_relationship'] ?? '') === 'mahrom' ? 5 : 3,
            ],
            'is_special_permission' => false,
            'created_by' => $user->id,
        ]);

        $visit = DormitoryVisitLog::create($visitData);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan kunjungan berhasil dikirim.',
            'data' => $this->formatVisit($visit),
        ], 201);
    }

    // ── GET /api/mobile/v1/dormitory/visits ────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => ['visits' => [], 'total' => 0],
            ]);
        }

        $query = DormitoryVisitLog::with(['student:id,name,nisn'])
            ->whereIn('student_id', $studentIds);

        if ($schoolId) {
            $query->whereHas('student', fn ($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $visits = $query->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($v) => $this->formatVisit($v, true));

        return response()->json([
            'success' => true,
            'data' => [
                'visits' => $visits,
                'total' => $visits->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/dormitory/visits/{id} ──────────────────────
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        $visit = DormitoryVisitLog::with([
            'student:id,name,nisn',
            'room:id,code,wing.name',
            'dormitory:id,name,code',
            'approvedBy:name',
        ])
            ->whereIn('student_id', $studentIds)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatVisit($visit, true, true),
        ]);
    }

    // ── PATCH /api/mobile/v1/dormitory/visits/{id}/check-in ───────────
    public function checkIn(Request $request, string $id): JsonResponse
    {
        $visit = DormitoryVisitLog::findOrFail($id);
        $visit->update([
            'status' => 'arrived',
            'check_in_at' => now(),
            'actual_arrival_datetime' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in kunjungan berhasil.',
            'data' => $this->formatVisit($visit, true),
        ]);
    }

    // ── PATCH /api/mobile/v1/dormitory/visits/{id}/check-out ──────────
    public function checkOut(Request $request, string $id): JsonResponse
    {
        $visit = DormitoryVisitLog::findOrFail($id);
        $visit->update([
            'status' => 'checked_out',
            'check_out_at' => now(),
            'departure_datetime' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-out kunjungan berhasil.',
            'data' => $this->formatVisit($visit, true),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────
    private function formatVisit(DormitoryVisitLog $v, bool $withStudent = false, bool $full = false): array
    {
        $payload = [
            'id' => $v->id,
            'dormitory_id' => $v->dormitory_id,
            'room_id' => $v->room_id,
            'student_id' => $v->student_id,
            'visitor_name' => $v->visitor_name,
            'visitor_id_number' => $v->visitor_id_number,
            'visitor_phone' => $v->visitor_phone,
            'visitor_relationship' => $v->visitor_relationship,
            'purpose' => $v->purpose,
            'expected_arrival_datetime' => $v->expected_arrival_datetime?->toIso8601String(),
            'actual_arrival_datetime' => $v->actual_arrival_datetime?->toIso8601String(),
            'check_in_at' => $v->check_in_at?->toIso8601String(),
            'check_out_at' => $v->check_out_at?->toIso8601String(),
            'status' => $v->status,
            'status_text' => $v->status_text,
            'created_at' => $v->created_at?->toIso8601String(),
        ];

        if ($full) {
            $payload['approval_note'] = $v->approval_note;
            $payload['approved_by_name'] = $v->approvedBy?->name;
            $payload['approved_at'] = $v->approved_at?->toIso8601String();
            $payload['notes'] = $v->notes;
            $payload['visitor_access_rights'] = $v->visitor_access_rights;
            $payload['is_special_permission'] = $v->is_special_permission;
        }

        if ($withStudent && $v->student) {
            $payload['student'] = [
                'id' => $v->student->id,
                'name' => $v->student->name,
                'nisn' => $v->student->nisn,
            ];
        }

        return $payload;
    }
}
