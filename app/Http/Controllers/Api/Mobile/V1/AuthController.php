<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RegisterWaliRequest;
use App\Http\Requests\Mobile\LoginWaliRequest;
use App\Http\Services\WaliSantriService;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private WaliSantriService $waliService;

    public function __construct(WaliSantriService $waliService)
    {
        $this->waliService = $waliService;
    }

    // ── POST /api/mobile/v1/auth/register ────────────────────────────────────

    public function register(RegisterWaliRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'no_kk' => $data['no_kk'] ?? null,
            'nik_wali' => $data['nik_wali'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'hubungan' => $data['hubungan'] ?? 'wali',
            'is_wali' => true,
            'is_active' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Selamat datang!',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ], 201);
    }

    // ── POST /api/mobile/v1/auth/login ───────────────────────────────────────

    public function login(LoginWaliRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Hanya allow login untuk user yang is_wali = true
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password yang Anda masukkan salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Silakan hubungi administrators.'],
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda terkunci sementara. Coba lagi nanti atau reset password.'],
            ]);
        }

        $user->resetFailedLoginAttempts();
        $user->last_login_at = now();
        $user->save();

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    // ── POST /api/mobile/v1/auth/google ───────────────────────────────────────

    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'google_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string|min:2',
            'id_token' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)
            ->orWhere('google_id', $request->google_id)
            ->first();

        if ($user) {
            // Login / sinkronkan google_id
            if (!$user->google_id) {
                $user->google_id = $request->google_id;
                $user->save();
            }
        } else {
            // Registrasi otomatis
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'google_id' => $request->google_id,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'is_wali' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => $user->google_id ? 'Login Google berhasil.' : 'Akun dibuat dengan Google.',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'is_new_user' => !$user->google_id,
            ],
        ]);
    }

    // ── POST /api/mobile/v1/auth/logout ──────────────────────────────────────

    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token invalid/expired — tetap logout sisi client
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    // ── GET /api/mobile/v1/auth/me ───────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Load wali-related data
        $students = WaliSantri::with(['student', 'student.school'])
            ->where('user_id', $user->id)
            ->active()
            ->get()
            ->map(fn($link) => [
                'id' => $link->student->id,
                'name' => $link->student->name,
                'nik' => $link->student->nik,
                'role' => $link->role,
                'is_primary' => $link->is_primary,
                'school' => $link->student->school?->name,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'students' => $students,
            ],
        ]);
    }

    // ── PUT /api/mobile/v1/auth/me ───────────────────────────────────────────

    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|min:2|max:100',
            'no_kk' => 'sometimes|nullable|string|size:16|regex:/^\d{16}$/',
            'nik_wali' => 'sometimes|nullable|string|size:16|regex:/^\d{16}$/',
            'no_hp' => 'sometimes|nullable|string|min:10|max:20',
            'hubungan' => 'sometimes|nullable|in:ayah,ibu,kakek,nenek,wali,lainnya',
        ]);

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => ['user' => $this->formatUser($user)],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'no_kk' => $user->no_kk ? WaliSantri::maskNoKk($user->no_kk) : null,
            'nik_wali' => $user->nik_wali ? substr($user->nik_wali, 0, 6) . '••••••••' . substr($user->nik_wali, -2) : null,
            'no_hp' => $user->no_hp,
            'hubungan' => $user->hubungan,
            'is_wali' => $user->is_wali,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }
}