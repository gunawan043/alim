<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\CreateDormitoryPermitRequest;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DormitoryPermitController extends Controller
{
    // ── POST /api/mobile/v1/dormitory-permit ───────────────────────────────

    public function store(CreateDormitoryPermitRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify wali links to this student (tenant-scoped)
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

        // Tenant scope: validate student belongs to school context
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $student = Student::where('id', $data['student_id'])
                ->where('school_id', $schoolId)
                ->first();
            if (! $student) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'STUDENT_NOT_FOUND',
                        'message' => 'Santri tidak ditemukan.',
                    ],
                ], 404);
            }
        }

        // Look up current dormitory/room assignment for the student
        $resident = DormitoryResident::where('student_id', $data['student_id'])
            ->where('is_active', true)
            ->orderByDesc('check_in_date')
            ->first();

        // Verify referenced mahrom belongs to this student AND is active
        if (! empty($data['mahrom_id'])) {
            $mahrom = StudentMahrom::where('id', $data['mahrom_id'])
                ->where('student_id', $data['student_id'])
                ->first();

            if (! $mahrom) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'MAHROM_NOT_FOUND',
                        'message' => 'Mahrom tidak ditemukan untuk santri ini.',
                    ],
                ], 404);
            }

            if (! $mahrom->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'MAHROM_INACTIVE',
                        'message' => 'Mahrom nonaktif tidak dapat digunakan untuk kunjungan.',
                    ],
                ], 422);
            }
        }

        $permit = DormitoryPermit::create([
            'student_id' => $data['student_id'],
            'dormitory_id' => $resident?->dormitory_id,
            'room_id' => $resident?->room_id,
            'academic_year_id' => $resident?->academic_year_id,
            'permit_type' => $data['permit_type'],
            'destination' => $data['destination'],
            'purpose' => $data['purpose'],
            'departure_datetime' => $data['departure_datetime'],
            'expected_return_datetime' => $data['expected_return_datetime'],
            'companion_name' => $data['companion_name'] ?? null,
            'companion_relation' => $data['companion_relation'] ?? null,
            'companion_phone' => $data['companion_phone'] ?? null,
            'companion_is_mahrom' => $data['companion_is_mahrom'] ?? false,
            'mahrom_id' => $data['mahrom_id'] ?? null,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan surat izin berhasil dikirim dan menunggu persetujuan.',
            'data' => $this->formatPermit($permit),
        ], 201);
    }

    // ── GET /api/mobile/v1/dormitory-permits ───────────────────────────────

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

        $schoolId = $request->attributes->get('schoolContextId');
        $query = DormitoryPermit::with(['student:id,name,nisn'])
            ->whereIn('student_id', $studentIds);

        if ($schoolId) {
            $query->whereHas('student', fn ($q) => $q->where('school_id', $schoolId));
        }

        $permits = $query->orderByDesc('created_at')
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

    private function formatPermit(DormitoryPermit $p, bool $withStudent = false): array
    {
        $payload = [
            'id' => $p->id,
            'permit_type' => $p->permit_type,
            'permit_type_text' => $p->permit_type_text,
            'destination' => $p->destination,
            'purpose' => $p->purpose,
            'departure_datetime' => $p->departure_datetime?->toIso8601String(),
            'expected_return_datetime' => $p->expected_return_datetime?->toIso8601String(),
            'actual_return_datetime' => $p->actual_return_datetime?->toIso8601String(),
            'status' => $p->status,
            'status_text' => $p->status_text,
            'is_overdue' => $p->is_overdue,
            'companion_name' => $p->companion_name,
            'companion_relation' => $p->companion_relation,
            'companion_phone' => $p->companion_phone,
            'approval_note' => $p->approval_note,
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
