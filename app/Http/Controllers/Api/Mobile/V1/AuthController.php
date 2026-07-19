<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LoginWaliRequest;
use App\Http\Requests\Mobile\RegisterWaliRequest;
use App\Http\Services\WaliSantriService;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\MobileSessionIntrospector;
use App\Support\AbilityRegistry;
use App\Support\TokenExpiration;
use App\Support\TokenName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private WaliSantriService $waliService;

    private MobileSessionIntrospector $sessions;

    public function __construct(WaliSantriService $waliService, MobileSessionIntrospector $sessions)
    {
        $this->waliService = $waliService;
        $this->sessions = $sessions;
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

        [$token, $payload] = $this->issueMobileToken(
            user: $user,
            channel: TokenName::CHANNEL_PASSWORD,
            platform: $request,
            deviceFp: 'fp_register_'.substr(Str::uuid(), 0, 8),
        );

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Selamat datang!',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $payload['expires_in'],
                'expires_at' => $payload['expires_at'],
                'abilities' => $payload['abilities'],
            ],
        ], 201);
    }

    // ── POST /api/mobile/v1/auth/login ───────────────────────────────────────

    public function login(LoginWaliRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda terkunci sementara. Coba lagi nanti atau reset password.'],
            ]);
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $user->incrementFailedLoginAttempts();
            }

            throw ValidationException::withMessages([
                'email' => ['Email atau password yang Anda masukkan salah.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Silakan hubungi administrators.'],
            ]);
        }

        $user->resetFailedLoginAttempts();
        $user->last_login_at = now();
        $user->save();

        [$token, $payload] = $this->issueMobileToken(
            user: $user,
            channel: TokenName::CHANNEL_PASSWORD,
            platform: $request,
            deviceFp: $this->fingerprintFromRequest($request),
        );

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $payload['expires_in'],
                'expires_at' => $payload['expires_at'],
                'abilities' => $payload['abilities'],
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

        $isNewUser = false;

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $request->google_id;
                $user->save();
            }
        } else {
            $isNewUser = true;
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

        [$token, $payload] = $this->issueMobileToken(
            user: $user,
            channel: TokenName::CHANNEL_GOOGLE,
            platform: $request,
            deviceFp: $this->fingerprintFromRequest($request),
        );

        return response()->json([
            'success' => true,
            'message' => $user->wasRecentlyCreated || $isNewUser
                ? 'Akun dibuat dengan Google.'
                : 'Login Google berhasil.',
            'data' => [
                'user' => $this->formatUser($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $payload['expires_in'],
                'expires_at' => $payload['expires_at'],
                'abilities' => $payload['abilities'],
                'is_new_user' => $isNewUser,
            ],
        ]);
    }

    // ── POST /api/mobile/v1/auth/logout ──────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    // ── POST /api/mobile/v1/auth/logout-all ──────────────────────────────────

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->failResponse('Tidak terautentikasi.', 401);
        }

        $current = $user->currentAccessToken();
        $currentId = $current?->getKey();

        $deleted = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->when($currentId !== null, fn ($q) => $q->where('id', '!=', $currentId))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua sesi lain berhasil dicabut.',
            'data' => ['revoked' => (int) $deleted],
        ]);
    }

    // ── GET /api/mobile/v1/auth/sessions ─────────────────────────────────────

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->failResponse('Tidak terautentikasi.', 401);
        }

        $rows = $this->sessions->listForUser($user, $request);

        return response()->json([
            'success' => true,
            'message' => 'Daftar sesi berhasil diambil.',
            'data' => ['sessions' => $rows],
        ]);
    }

    // ── PATCH /api/mobile/v1/auth/sessions/current ───────────────────────────

    public function updateCurrentSession(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->failResponse('Tidak terautentikasi.', 401);
        }

        $current = $user->currentAccessToken();

        if (! $current instanceof PersonalAccessToken) {
            return $this->failResponse('Sesi saat ini tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'device_label' => 'required|string|min:1|max:80',
        ]);

        $current->device_label = $validated['device_label'];
        $current->save();

        return response()->json([
            'success' => true,
            'message' => 'Label perangkat berhasil diperbarui.',
            'data' => [
                'session' => $this->sessions->describe($current, $request),
            ],
        ]);
    }

    // ── DELETE /api/mobile/v1/auth/sessions/others ───────────────────────────

    public function revokeOtherSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->failResponse('Tidak terautentikasi.', 401);
        }

        $current = $user->currentAccessToken();
        $currentId = $current?->getKey();

        $deleted = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->when($currentId !== null, fn ($q) => $q->where('id', '!=', $currentId))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua sesi lain berhasil dicabut.',
            'data' => ['revoked' => (int) $deleted],
        ]);
    }

    // ── GET /api/mobile/v1/auth/me ───────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $students = WaliSantri::with(['student', 'student.school'])
            ->where('user_id', $user->id)
            ->active()
            ->get()
            ->map(fn ($link) => [
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
        $user = $request->user();

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

    // ── Token issuance ───────────────────────────────────────────────────────

    /**
     * Issue a Sanctum personal access token for the mobile surface.
     *
     * Returns [plainTextToken, payload] where payload exposes
     *   - expires_in (seconds, never hardcoded — sourced via TokenExpiration)
     *   - expires_at (ISO-8601 string or null)
     *   - abilities (list<string>)
     *
     * Backward compatibility: legacy clients still receive
     * "access_token" + "token_type" + "expires_in" in the same positions.
     */
    private function issueMobileToken(
        User $user,
        string $channel,
        ?Request $request,
        string $deviceFp,
    ): array {
        $platform = TokenName::platformFromRequest($request);

        $tokenName = TokenName::mobile(
            clientKind: 'user',
            channel: $channel,
            platform: $platform,
            deviceFp: $deviceFp,
        );

        $abilities = AbilityRegistry::forRoles($user->effectiveRoles());

        $expiresAt = TokenExpiration::mobileDefaultExpiresAt();

        /** @var NewAccessToken $new */
        $new = $user->createToken(
            name: $tokenName,
            abilities: $abilities,
            expiresAt: $expiresAt,
        );

        $accessToken = $new->accessToken;

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->device_label = $this->buildDeviceLabel($platform, $request);
            $accessToken->ip_last = $this->clientIp($request);
            $accessToken->fingerprint = $deviceFp;
            $accessToken->save();
        }

        $minutes = TokenExpiration::mobileDefaultMinutes();

        $expiresIn = $minutes === null ? null : $minutes * 60;

        return [
            $new->plainTextToken,
            [
                'expires_in' => $expiresIn,
                'expires_at' => optional($expiresAt)->toIso8601String(),
                'abilities' => $abilities,
            ],
        ];
    }

    /**
     * Best-effort short device fingerprint. Never trusts the client.
     */
    private function fingerprintFromRequest(Request $request): string
    {
        $candidates = [
            $request->header('X-Device-Id'),
            $request->header('X-Install-Id'),
            $request->input('device_id'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $value) ?? '';
                if ($clean !== '') {
                    return 'fp_'.substr($clean, 0, 24);
                }
            }
        }

        return 'fp_ip_'.substr(md5((string) $request->ip()), 0, 12);
    }

    // ── User formatting ──────────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'no_kk' => $user->no_kk ? WaliSantri::maskNoKk($user->no_kk) : null,
            'nik_wali' => $user->nik_wali ? substr($user->nik_wali, 0, 6).'••••••••'.substr($user->nik_wali, -2) : null,
            'no_hp' => $user->no_hp,
            'hubungan' => $user->hubungan,
            'is_wali' => $user->is_wali,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }

    // ── Sprint 2 helpers ─────────────────────────────────────────────────────

    private function buildDeviceLabel(string $platform, ?Request $request): string
    {
        $ua = $request?->userAgent() ?? 'unknown';

        $clean = preg_replace('/[^a-zA-Z0-9 .()\-_]/', ' ', $ua) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        if ($clean === '') {
            $clean = 'unknown';
        }

        $label = $platform.' / '.substr($clean, 0, 60);

        return substr($label, 0, 80);
    }

    private function clientIp(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $ip = $request->ip();

        return is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : null;
    }

    private function failResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => null,
        ], $status);
    }
}
