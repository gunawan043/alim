<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RegisterWaliRequest;
use App\Http\Requests\Mobile\LoginWaliRequest;
use App\Http\Requests\Mobile\RegisterStudentRequest;
use App\Http\Requests\Mobile\LinkWaliSantriRequest;
use App\Http\Requests\Mobile\RequestWaliRoleRequest;
use App\Http\Requests\Mobile\ApproveRejectWaliRequest;
use App\Http\Services\WaliSantriService;
use App\Models\WaliRegistrationToken;
use App\Models\WaliSantri;
use App\Mail\WaliAccessRequestMail;
use App\Mail\WaliRequestApprovedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WaliAuthController extends Controller
{
    private WaliSantriService $waliService;

    public function __construct(WaliSantriService $waliService)
    {
        $this->waliService = $waliService;
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function register(RegisterWaliRequest $request): JsonResponse
    {
        $data = $request->validated();

        $existing = \App\Models\User::where('email', $data['email'])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_ALREADY_EXISTS',
                    'message' => 'Email sudah terdaftar. Silakan login.',
                ],
            ], 422);
        }

        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'no_kk' => $data['no_kk'] ?? null,
            'nik_wali' => $data['nik_wali'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'hubungan' => $data['hubungan'] ?? null,
            'is_wali' => true,
            'is_active' => true,
        ]);

        $token = $this->generateTokens($user);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'data' => $this->buildUserResponse($user, $token),
        ], 201);
    }

    public function login(LoginWaliRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = \App\Models\User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Email atau password salah.',
                ],
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => 'Akun tidak aktif. Hubungi administrators.',
                ],
            ], 403);
        }

        if ($user->isLocked()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_LOCKED',
                    'message' => 'Akun terkunci sementara karena terlalu banyak percobaan login.',
                    'locked_until' => $user->locked_until?->toIso8601String(),
                ],
            ], 423);
        }

        $user->resetFailedLoginAttempts();
        $user->update(['last_login_at' => now()]);

        $token = $this->generateTokens($user);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => $this->buildUserResponse($user, $token),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['waliSantri.student', 'students']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->buildUserResponse($user),
                'students' => $user->students->map(fn($s) => [
                    'id' => $s->id,
                    'nik' => $s->nik,
                    'name' => $s->name,
                    'gender' => $s->gender,
                    'birth_date' => $s->birth_date,
                    'school_id' => $s->school_id,
                    'status' => $s->pivot->status ?? null,
                    'role' => $s->pivot->role ?? null,
                    'is_primary' => (bool) ($s->pivot->is_primary ?? false),
                ]),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|min:2|max:100',
            'no_kk' => 'sometimes|nullable|string|size:16|regex:/^\d{16}$/',
            'nik_wali' => 'sometimes|nullable|string|size:16|regex:/^\d{16}$/',
            'no_hp' => 'sometimes|nullable|string|min:10|max:20',
            'hubungan' => 'sometimes|in:ayah,ibu,kakek,nenek,wali,lainnya',
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->buildUserResponse($user),
        ]);
    }

    // ── Token generation helpers ──────────────────────────────────────────────

    private function generateTokens(\App\Models\User $user): array
    {
        $accessToken = $user->createToken('mobile_access')->plainTextToken;
        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 20160),
        ];
    }

    private function buildUserResponse(\App\Models\User $user, array $token = []): array
    {
        $res = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'no_kk' => $user->no_kk ? $this->maskNoKk($user->no_kk) : null,
            'nik_wali' => $user->nik_wali ? $this->maskNik($user->nik_wali) : null,
            'no_hp' => $user->no_hp,
            'hubungan' => $user->hubungan,
            'is_wali' => $user->is_wali,
            'created_at' => $user->created_at->toIso8601String(),
        ];
        if ($token) {
            $res['token'] = $token;
        }
        return $res;
    }

    private function maskNoKk(string $noKk): string
    {
        return substr($noKk, 0, 4) . '••••••••' . substr($noKk, -4);
    }

    private function maskNik(string $nik): string
    {
        return substr($nik, 0, 6) . '••••••••' . substr($nik, -2);
    }
}