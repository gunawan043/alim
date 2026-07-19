<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Exceptions\ServiceErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LinkWaliSantriRequest;
use App\Http\Requests\Mobile\RequestWaliRoleRequest;
use App\Http\Services\WaliSantriService;
use App\Models\Student;
use App\Models\WaliRegistrationToken;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaliSantriController extends Controller
{
    private WaliSantriService $waliService;

    public function __construct(WaliSantriService $waliService)
    {
        $this->waliService = $waliService;
    }

    // ── POST /api/mobile/v1/wali-santri/start-registration ──────────────────
    // Unified dispatcher for the three wali-santri onboarding intents:
    //   • link_santri      → wali yang sudah punya Santi → klaim Santi lain
    //   • register_new     → daftarkan Santi baru + auto-link
    //   • add_second_wali  → minta jadi wali kedua/ketiga untuk Santi yg ada
    //
    // Why a unified endpoint: mobile UX is a single funnel that branches by
    // intent, so consolidating dispatch keeps validation + response shape
    // consistent across the three flows.

    public function startRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'intent' => ['required', 'string', Rule::in(['link_santri', 'register_new', 'add_second_wali'])],
            'nik_santri' => 'nullable|string|size:16|regex:/^\d{16}$/',
            'student_id' => 'nullable|string|max:36',
            'role' => 'sometimes|string|max:32',
            'name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:L,P,laki-laki,perempuan',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'no_kk' => 'nullable|string|max:20',
            'approval_token' => 'nullable|string',
        ]);

        $user = auth()->user();

        return match ($validated['intent']) {
            'link_santri' => $this->dispatchLinkSantri($validated, $user),
            'register_new' => $this->dispatchRegisterNew($validated, $user),
            'add_second_wali' => $this->dispatchAddSecondWali($validated, $user),
        };
    }

    private function dispatchLinkSantri(array $data, $user): JsonResponse
    {
        $data = $this->resolveStudentIdToNik($data, 'student_id', 'nik_santri');

        if (! empty($data['approval_token'])) {
            return $this->processApprovalToken($data['approval_token'], $user, $data['student_id'] ?? null);
        }

        // Bind student-tenant ke schoolContextId untuk first-time wali.
        $this->bindStudentSchoolToRequest($data);

        try {
            $result = $this->waliService->requestLinkToStudent($data, $user);

            $statusCode = ($result['already_linked'] ?? false) ? 200 : (
                ($result['needs_approval'] ?? false) ? 202 : 201
            );

            return $this->formatLinkResult($result, $statusCode);
        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    private function dispatchRegisterNew(array $data, $user): JsonResponse
    {
        foreach (['name', 'gender', 'birth_place', 'birth_date'] as $required) {
            if (empty($data[$required])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => "Field {$required} wajib diisi untuk intent register_new.",
                    ],
                ], 422);
            }
        }

        if (empty($data['nik_santri'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Field nik_santri wajib diisi untuk intent register_new.',
                ],
            ], 422);
        }

        // Bind schoolContextId via NIK for register_new (no student_id).
        $this->bindStudentSchoolToRequest($data);

        try {
            $result = $this->waliService->registerStudentAndLink($data, $user);

            return $this->formatLinkResult($result, 201);
        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    private function dispatchAddSecondWali(array $data, $user): JsonResponse
    {
        $data = $this->resolveStudentIdToNik($data, 'student_id', 'nik_santri');

        if (! empty($data['approval_token'])) {
            return $this->processApprovalToken($data['approval_token'], $user, $data['student_id'] ?? null);
        }

        $this->bindStudentSchoolToRequest($data);

        try {
            $result = $this->waliService->requestLinkToStudent($data, $user);

            $statusCode = ($result['already_linked'] ?? false) ? 200 : (
                ($result['needs_approval'] ?? false) ? 202 : 201
            );

            return $this->formatLinkResult($result, $statusCode);
        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    /**
     * Helper: jika client mengirim student_id (bukan nik_santri), lookup NIK-nya.
     * Mutates and returns $data with nik_santri populated when student_id is present.
     */
    private function resolveStudentIdToNik(array $data, string $idKey, string $nikKey): array
    {
        if (! empty($data[$nikKey]) || empty($data[$idKey])) {
            return $data;
        }

        $student = Student::where('id', $data[$idKey])->first();
        if ($student) {
            $data[$nikKey] = $student->nik;
        }

        return $data;
    }

    /**
     * Bind student's school to schoolContextId request attribute so that
     * first-time wali (no wali_santri rows) always have a valid tenant
     * context when calling service methods.
     */
    private function bindStudentSchoolToRequest(array $data): void
    {
        $request = request();
        if ($request === null) {
            return;
        }

        $existing = $request->attributes->get('schoolContextId');
        if ($existing !== null) {
            return;
        }

        // Prefer student_id lookup
        $studentId = $data['student_id'] ?? null;
        if ($studentId !== null) {
            $student = Student::find($studentId);
            if ($student?->school_id !== null) {
                $request->attributes->set('schoolContextId', $student->school_id);
                return;
            }
        }

        // Fallback: resolve by NIK
        $nik = $data['nik_santri'] ?? null;
        if ($nik !== null) {
            $student = Student::where('nik', $nik)->first();
            if ($student?->school_id !== null) {
                $request->attributes->set('schoolContextId', $student->school_id);
            }
        }
    }

    // ── POST /api/mobile/v1/wali-santri/link ────────────────────────────────
    // Wali yang sudah punya Santi → klaim Santi lain

    public function link(LinkWaliSantriRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = array_merge($request->validated(), [
            'nik_santri' => null, // gunakan student_id
        ]);

        // Konversi student_id → nik_santri
        $student = Student::find($data['student_id']);
        if (! $student) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'STUDENT_NOT_FOUND', 'message' => 'Santri tidak ditemukan.'],
            ], 404);
        }

        // Tenant scope: student harus berada di school context yang sama (jika sudah ada)
        $currentSchoolContext = $request->attributes->get('schoolContextId');
        if ($currentSchoolContext && $student->school_id && $student->school_id !== $currentSchoolContext) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CROSS_TENANT_ACCESS', 'message' => 'Santri tidak ditemukan.'],
            ], 404);
        }
        $data['nik_santri'] = $student->nik;

        // Bind student-tenant ke schoolContextId untuk first-time wali
        // yang belum memiliki wali_santri row (schoolContextId masih null).
        if ($request->attributes->get('schoolContextId') === null && $student->school_id !== null) {
            $request->attributes->set('schoolContextId', $student->school_id);
        }

        // Jika ada approval_token → langsung proses approve
        if (! empty($data['approval_token'])) {
            return $this->processApprovalToken($data['approval_token'], $user, $student->id);
        }

        try {
            $result = $this->waliService->requestLinkToStudent($data, $user);

            return $this->formatLinkResult($result, 201);

        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    // ── POST /api/mobile/v1/wali-santri/request ────────────────────────────
    // Wali baru minta jadi wali kedua/ketiga

    public function requestWaliRole(RequestWaliRoleRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        // Bind schoolContextId from student NIK for first-time wali.
        $this->bindStudentSchoolToRequest($data);

        try {
            $result = $this->waliService->requestLinkToStudent($data, $user);

            $statusCode = $result['already_linked'] ?? false ? 200 : (
                $result['needs_approval'] ?? false ? 202 : 201
            );

            return $this->formatLinkResult($result, $statusCode);

        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    // ── GET /api/mobile/v1/wali-santri/requests ────────────────────────────
    // Daftar request yang masuk (untuk wali utama)

    public function listRequests(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Ambil semua student yang terhubung ke user ini
        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => ['requests' => [], 'total' => 0],
            ]);
        }

        // Ambil pending tokens yang指向 student miliknya
        $tokens = WaliRegistrationToken::with(['user:id,name,email,no_hp', 'student:id,name,nik'])
            ->whereIn('student_id', $studentIds)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('intent', 'add_wali')
            ->orderByDesc('created_at')
            ->get();

        $requests = $tokens->map(fn ($token) => [
            'id' => $token->id,
            'token' => $token->token,
            'student' => [
                'id' => $token->student->id,
                'name' => $token->student->name,
                'nik' => $token->student->nik,
            ],
            'requester' => [
                'id' => $token->user->id,
                'name' => $token->user->name,
                'email' => $token->user->email,
                'no_hp' => $token->user->no_hp,
            ],
            'expires_at' => $token->expires_at->toIso8601String(),
            'created_at' => $token->created_at->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests,
                'total' => $requests->count(),
            ],
        ]);
    }

    // ── PUT /api/mobile/v1/wali-santri/requests/:token ──────────────────────
    // Approve / Reject request

    public function approveReject(Request $request, string $token): JsonResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->waliService->approveRejectRequest(
                $token,
                $user,
                $data['action'],
                $data['note'] ?? null
            );

            if ($result['approved'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permintaan telah disetujui.',
                    'data' => [
                        'student' => [
                            'id' => $result['student']->id,
                            'name' => $result['student']->name,
                        ],
                        'new_wali' => [
                            'id' => $result['requester']->id,
                            'name' => $result['requester']->name,
                        ],
                        'wali_santri' => [
                            'id' => $result['wali_santri']->id,
                            'role' => $result['wali_santri']->role,
                        ],
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permintaan telah ditolak.',
                'data' => [
                    'student' => [
                        'id' => $result['student']->id,
                        'name' => $result['student']->name,
                    ],
                    'rejected_requester' => [
                        'name' => $result['requester']->name,
                    ],
                    'note' => $result['note'],
                ],
            ]);

        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    // ── DELETE /api/mobile/v1/wali-santri/:id ───────────────────────────────
    // Lepas hubungan

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = canPermission('super-admin-only') || canPermission('admin-role');

        try {
            $this->waliService->removeLink($id, $user, $isAdmin);

            return response()->json([
                'success' => true,
                'message' => 'Hubungan wali-Santi berhasil dilepas.',
            ]);

        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function processApprovalToken(string $token, $user, string $studentId): JsonResponse
    {
        try {
            $result = $this->waliService->approveRejectRequest($token, $user, 'approve');

            return response()->json([
                'success' => true,
                'message' => 'Santi berhasil terhubung ke akun Anda.',
                'data' => [
                    'student' => ['id' => $result['student']->id, 'name' => $result['student']->name],
                    'wali_santri' => ['id' => $result['wali_santri']->id, 'role' => $result['wali_santri']->role],
                ],
            ], 201);

        } catch (\Exception $e) {
            return $this->serviceExceptionResponse($e);
        }
    }

    private function formatLinkResult(array $result, int $statusCode): JsonResponse
    {
        $data = [
            'student' => [
                'id' => $result['student']->id,
                'name' => $result['student']->name,
                'nik' => $result['student']->nik ?? null,
            ],
        ];

        if (isset($result['wali_santri'])) {
            $data['wali_santri'] = [
                'id' => $result['wali_santri']->id,
                'role' => $result['wali_santri']->role,
                'is_primary' => $result['wali_santri']->is_primary,
                'status' => $result['wali_santri']->status,
            ];
        }

        if (isset($result['needs_approval']) && $result['needs_approval']) {
            $data['approval_token'] = $result['approval_token'] ?? null;
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Berhasil.',
            'data' => $data,
        ], $statusCode);
    }

    private function serviceExceptionResponse(\Exception $e): JsonResponse
    {
        $code = 500;
        $errorCode = 'SERVER_ERROR';
        $message = $e->getMessage();
        $details = ['message' => $e->getMessage()];

        if ($e instanceof ServiceErrorCode) {
            $code = $e->getStatus();
            $details = $e->getDetails();
        } else {
            $code = (int) $e->getCode() ?: 500;
            $validCodes = [400, 401, 403, 404, 409, 422, 500];
            if (! in_array($code, $validCodes)) {
                $code = 500;
            }
        }

        $errorMessages = [
            'NIK_ALREADY_EXISTS' => 'NIK_ALREADY_EXISTS',
            'KK_MISMATCH' => 'KK_MISMATCH',
            'STUDENT_NOT_FOUND' => 'STUDENT_NOT_FOUND',
            'LINK_PENDING' => 'LINK_PENDING',
            'DUPLICATE_REQUEST' => 'DUPLICATE_REQUEST',
            'MAX_WALI_EXCEEDED' => 'MAX_WALI_EXCEEDED',
            'TOKEN_INVALID' => 'TOKEN_INVALID',
            'TOKEN_EXPIRED' => 'TOKEN_INVALID',
            'UNAUTHORIZED' => 'UNAUTHORIZED',
            'LINK_NOT_FOUND' => 'LINK_NOT_FOUND',
            'CANNOT_REMOVE_LAST_WALI' => 'CANNOT_REMOVE_LAST_WALI',
            'DB_ERROR' => 'SERVER_ERROR',
        ];

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorMessages[$message] ?? $message,
                'message' => $message,
                'details' => $details,
            ],
        ], $code);
    }
}
