<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RecordDormitoryReturnRequest;
use App\Models\DormitoryPermit;
use App\Models\WaliSantri;
use App\Services\Boarding\LeaveWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DormitoryReturnController extends Controller
{
    public function __construct(
        private readonly LeaveWorkflowService $leave,
    ) {}

    // ── POST /api/mobile/v1/dormitory/return ─────────────────────────────
    public function store(RecordDormitoryReturnRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify wali links to the permit's student
        $permit = DormitoryPermit::findOrFail($data['permit_id']);

        $link = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $permit->student_id)
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

        if (! in_array($permit->status, ['approved', 'overdue'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PERMIT_STATUS',
                    'message' => 'Santri belum berangkat, izin tidak aktif, atau sudah tercatat pulang.',
                ],
            ], 422);
        }

        $updated = $this->leave->recordReturn(
            permitId: $permit->id,
            dormitoryId: $permit->dormitory_id,
            actualReturnDatetime: $data['actual_return_datetime'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Kepulangan berhasil dicatat.',
            'data' => $this->formatPermit($updated),
        ]);
    }

    // ── GET /api/mobile/v1/dormitory/returns ─────────────────���───────────
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => ['permits' => [], 'total' => 0],
            ]);
        }

        $query = DormitoryPermit::with(['student:id,name,nisn', 'room:id,code'])
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['approved', 'overdue', 'returned']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $permits = $query->orderByDesc('departure_datetime')
            ->limit(100)
            ->get()
            ->map(fn ($p) => $this->formatPermit($p, true));

        return response()->json([
            'success' => true,
            'data' => [
                'permits' => $permits,
                'total' => $permits->count(),
            ],
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────���─────
    private function formatPermit(DormitoryPermit $p, bool $withStudent = false): array
    {
        $payload = [
            'id' => $p->id,
            'dormitory_id' => $p->dormitory_id,
            'room_id' => $p->room_id,
            'student_id' => $p->student_id,
            'permit_type' => $p->permit_type,
            'destination' => $p->destination,
            'purpose' => $p->purpose,
            'departure_datetime' => $p->departure_datetime?->toIso8601String(),
            'expected_return_datetime' => $p->expected_return_datetime?->toIso8601String(),
            'actual_return_datetime' => $p->actual_return_datetime?->toIso8601String(),
            'companion_name' => $p->companion_name,
            'companion_relation' => $p->companion_relation,
            'companion_phone' => $p->companion_phone,
            'status' => $p->status,
            'created_at' => $p->created_at?->toIso8601String(),
        ];

        if ($withStudent && $p->student) {
            $payload['student'] = [
                'id' => $p->student->id,
                'name' => $p->student->name,
                'nisn' => $p->student->nisn,
            ];
        }

        return $payload;
    }
}
