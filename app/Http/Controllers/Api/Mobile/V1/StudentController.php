<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RegisterStudentRequest;
use App\Http\Services\WaliSantriService;
use App\Models\Student;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private WaliSantriService $waliService;

    public function __construct(WaliSantriService $waliService)
    {
        $this->waliService = $waliService;
    }

    // ── GET /api/mobile/v1/santri ────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $links = WaliSantri::with(['student.school'])
            ->where('user_id', $user->id)
            ->active()
            ->get();

        $students = $links->map(fn($link) => $this->formatStudent($link));

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $students,
                'total' => $students->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/santri/:id ────────────────────────────────────────

    public function show(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();

        $link = WaliSantri::with(['student.school', 'student.user'])
            ->where('user_id', $user->id)
            ->where('student_id', $id)
            ->active()
            ->first();

        if (!$link) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Santri tidak ditemukan atau Anda tidak memiliki akses.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatStudent($link, true),
        ]);
    }

    // ── POST /api/mobile/v1/santri ───────────────────────────────────────────

    public function store(RegisterStudentRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        try {
            $result = $this->waliService->registerStudentAndLink($data, $user);

            $statusCode = $result['already_linked'] ? 200 : 201;
            $message = $result['already_linked']
                ? 'Santri sudah terhubung dengan akun Anda.'
                : 'Santri berhasil terdaftar dan terhubung dengan akun wali.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'student' => $this->formatStudentDetail($result['student']),
                    'wali_santri' => [
                        'id' => $result['wali_santri']->id,
                        'role' => $result['wali_santri']->role,
                        'is_primary' => $result['wali_santri']->is_primary,
                        'status' => $result['wali_santri']->status,
                    ],
                    'already_linked' => $result['already_linked'],
                ],
            ], $statusCode);

        } catch (\Exception $e) {
            return $this->handleServiceException($e);
        }
    }

    // ── POST /api/mobile/v1/santri/verify-nik ────────────────────────��──────

    public function verifyNik(Request $request): JsonResponse
    {
        $request->validate([
            'nik' => 'required|string|size:16|regex:/^\d{16}$/',
        ]);

        $nik = $request->nik;

        if (!$this->waliService::validateNikFormat($nik)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_NIK_FORMAT',
                    'message' => 'Format NIK tidak valid. NIK harus 16 digit angka.',
                ],
            ], 422);
        }

        $student = Student::where('nik', $nik)->first();

        if (!$student) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nik' => $nik,
                    'status' => 'available',
                    'message' => 'NIK ini belum terdaftar. Bisa digunakan untuk registrasi Santi baru.',
                ],
            ]);
        }

        // NIK sudah ada
        $user = auth()->user();
        $existingLink = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingLink) {
            return response()->json([
                'success' => true,
                'data' => [
                    'nik' => $nik,
                    'status' => 'already_linked_to_you',
                    'student_name' => $student->name,
                    'message' => 'NIK ini sudah terdaftar dan terhubung dengan akun Anda.',
                ],
            ]);
        }

        // NIK ada, milik orang lain
        return response()->json([
            'success' => true,
            'data' => [
                'nik' => $nik,
                'status' => 'registered_by_other',
                'student_name' => $student->name,
                'message' => 'NIK ini sudah terdaftar di sistemas dan milik orang lain.',
                'suggestion' => 'Gunakan menu "Minta Jadi Wali" untuk mengajukan hubungan.',
                'masked_kk' => $student->no_kk ? substr($student->no_kk, 0, 4) . '••••' : null,
            ],
        ]);
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private function formatStudent($link, bool $detailed = false): array
    {
        $s = $link->student;

        $data = [
            'id' => $s->id,
            'nik' => $s->nik,
            'name' => $s->name,
            'gender' => $s->gender,
            'birth_date' => $s->birth_date?->format('Y-m-d'),
            'birth_place' => $s->birth_place,
            'role' => $link->role,
            'is_primary' => $link->is_primary,
            'school' => $s->school ? [
                'id' => $s->school->id,
                'name' => $s->school->name,
            ] : null,
        ];

        if ($detailed) {
            $data['no_kk'] = $s->no_kk;
            $data['address'] = $s->address;
            $data['phone'] = $s->phone;
            $data['mobile_phone'] = $s->mobile_phone;
            $data['father_name'] = $s->father_name;
            $data['mother_name'] = $s->mother_name;
            $data['other_walis'] = WaliSantri::with('user:id,name,email,no_hp')
                ->where('student_id', $s->id)
                ->active()
                ->where('user_id', '!=', auth()->id())
                ->get()
                ->map(fn($wl) => [
                    'user_id' => $wl->user_id,
                    'name' => $wl->user->name,
                    'role' => $wl->role,
                    'is_primary' => $wl->is_primary,
                ]);
        }

        return $data;
    }

    private function formatStudentDetail(Student $student): array
    {
        return [
            'id' => $student->id,
            'nik' => $student->nik,
            'name' => $student->name,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'birth_place' => $student->birth_place,
            'status' => $student->status,
        ];
    }

    private function handleServiceException(\Exception $e): JsonResponse
    {
        $code = $e->getCode() ?: 500;
        $errorData = $e->getPrevious()?->getResponse() ?? [
            'code' => $e->getMessage() ?: 'SERVER_ERROR',
            'message' => $e->getMessage(),
        ];

        $validCodes = [400, 401, 403, 404, 409, 422, 500];
        if (!in_array($code, $validCodes)) $code = 500;

        $errorMessages = [
            'NIK_ALREADY_EXISTS' => 'NIK_ALREADY_EXISTS',
            'KK_MISMATCH' => 'KK_MISMATCH',
            'MAX_WALI_EXCEEDED' => 'MAX_WALI_EXCEEDED',
            'DB_ERROR' => 'SERVER_ERROR',
        ];

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorMessages[$e->getMessage()] ?? $e->getMessage(),
                'message' => $e->getPrevious()?->getMessage() ?? $e->getMessage(),
                'details' => $e->getPrevious()?->getResponse() ?? null,
            ],
        ], $code);
    }
}