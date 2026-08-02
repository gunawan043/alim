<?php

namespace App\Http\Services;

use App\Exceptions\ServiceErrorCode;
use App\Mail\WaliAccessRequestMail;
use App\Mail\WaliRequestApprovedMail;
use App\Mail\WaliRequestRejectedMail;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliRegistrationToken;
use App\Models\WaliSantri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WaliSantriService
{
    private const TOKEN_EXPIRY_HOURS = 24;

    private const MAX_WALI_PER_STUDENT = 5;

    /**
     * Resolve the active school context for the current request.
     *
     * Resolves from the request attribute set by WaliSchoolContextMiddleware
     * (schoolContextId — canonical) or the OrganizationContext binding
     * for admin/web paths.
     *
     * Sentinel strings ('global', 'unknown', '') are treated as "no tenant"
     * and produce null — they must never propagate as valid school IDs.
     *
     * When $required is true and no tenant is available, throws
     * ServiceErrorCode(TENANT_CONTEXT_REQUIRED).  When $required is false,
     * returns null gracefully (e.g. dashboard read path).
     */
    private function currentSchoolId(bool $required = true): ?string
    {
        $request = request();
        if ($request === null) {
            $fromFallback = $this->resolveSchoolIdFromFallback();
        } else {
            // Canonical attribute (WaliSchoolContextMiddleware)
            $fromAttribute = $request->attributes->get('schoolContextId');
            if (is_string($fromAttribute) && $fromAttribute !== '' && ! $this->isSentinel($fromAttribute)) {
                return $fromAttribute;
            }

            // Fall back to OrganizationContext binding (web admin paths).
            $fromFallback = $this->resolveSchoolIdFromFallback();
        }

        $schoolId = $fromFallback !== null && ! $this->isSentinel($fromFallback)
            ? $fromFallback
            : null;

        if ($required && $schoolId === null) {
            throw new ServiceErrorCode(
                'Tidak dapat memproses permintaan: konteks sekolah tidak tersedia.',
                403,
                ['code' => 'TENANT_CONTEXT_REQUIRED']
            );
        }

        return $schoolId;
    }

    /**
     * Sentinel values that must never be treated as valid school IDs.
     * They appear when callers forget to bind a real tenant context.
     */
    private function isSentinel(string $value): bool
    {
        return $value === 'global'
            || $value === 'unknown'
            || $value === 'all'
            || $value === '*';
    }

    /**
     * Derive schoolId from the OrganizationContext binding when no
     * middleware attribute is available.
     * Filters sentinel values ('global', 'unknown', etc.) — returns null for them.
     */
    private function resolveSchoolIdFromFallback(): ?string
    {
        if (app()->bound(\App\Authorization\ValueObjects\OrganizationContext::class)) {
            $ctx = app(\App\Authorization\ValueObjects\OrganizationContext::class);
            if ($ctx instanceof \App\Authorization\ValueObjects\OrganizationContext) {
                $sid = $ctx->schoolId;
                if (is_string($sid) && $sid !== '' && ! $this->isSentinel($sid)) {
                    return $sid;
                }
            }
        }

        return null;
    }

    /**
     * Throw ServiceErrorCode when the student belongs to a different school
     * than the active request context. The error is intentionally generic
     * (STUDENT_NOT_FOUND) so existence of cross-tenant students is not leaked.
     */
    private function assertSameTenant(Student $student): void
    {
        $schoolId = $this->currentSchoolId(true);
        if ($student->school_id === null || $student->school_id !== $schoolId) {
            throw new ServiceErrorCode(
                'Santri tidak ditemukan.',
                404,
                ['code' => 'STUDENT_NOT_FOUND']
            );
        }
    }

    /**
     * Throw when a wali_santri row belongs to a different tenant.
     */
    private function assertLinkSameTenant(WaliSantri $link): void
    {
        $schoolId = $this->currentSchoolId(true);
        if ($link->school_id === null || $link->school_id !== $schoolId) {
            // Cross-tenant: return 404, never 403, to avoid confirming existence.
            throw new ServiceErrorCode(
                'Hubungan wali-Santri tidak ditemukan.',
                404,
                ['code' => 'LINK_NOT_FOUND']
            );
        }
    }

    /**
     * Throw when a registration token belongs to a different tenant.
     */
    private function assertTokenSameTenant(WaliRegistrationToken $token): void
    {
        $schoolId = $this->currentSchoolId(true);
        if ($token->school_id === null || $token->school_id !== $schoolId) {
            // Generic 404 — never confirm token existence cross-tenant.
            throw new ServiceErrorCode(
                'Token tidak valid atau sudah kedaluwarsa.',
                404,
                ['code' => 'TOKEN_INVALID']
            );
        }
    }

    // ── Register Student + Link to Wali ─────────────────────────────────────

    /**
     * Daftarkan Santri baru dan hubungkan ke akun wali.
     *
     * @throws \Exception Error codes: NIK_ALREADY_EXISTS, KK_MISMATCH,
     *                    USER_HAS_NO_KK, MAX_WALI_EXCEEDED, DB_ERROR
     */
    public function registerStudentAndLink(array $data, User $wali): array
    {
        // Tenant assertion: must run BEFORE any DB::transaction so we never
        // start a write transaction without a verified school context.
        $schoolId = $this->currentSchoolId(true);

        return DB::transaction(function () use ($data, $wali, $schoolId) {
            // ── STEP 1: Cek NIK sudah terdaftar? ────��────────────────────────
            $existingStudent = Student::where('nik', $data['nik'])->first();

            if ($existingStudent) {
                // Tenant guard: never reveal cross-tenant existence.
                if ($existingStudent->school_id !== $schoolId) {
                    throw new ServiceErrorCode(
                        'NIK sudah terdaftar di sistem dan milik orang lain. '
                            .'Jika ini adalah anak Anda, silakan hubungi administrators sekolah.',
                        422,
                        ['nik' => $data['nik']]
                    );
                }

                // NIK sudah ada → cek apakah sudah terhubung ke wali ini
                // Tenant guard for the linkage itself.
                // Note: school_id not needed here because $existingStudent was
                // already asserted to match $schoolId at line 156.
                $existingLink = WaliSantri::where('user_id', $wali->id)
                    ->where('student_id', $existingStudent->id)
                    ->first();

                if ($existingLink) {
                    // Tenant guard for the linkage itself.
                    if ($existingLink->school_id !== $schoolId) {
                        throw new ServiceErrorCode(
                            'Hubungan wali-Santri tidak ditemukan.',
                            404,
                            ['code' => 'LINK_NOT_FOUND']
                        );
                    }

                    return [
                        'student' => $existingStudent,
                        'wali_santri' => $existingLink,
                        'already_linked' => true,
                    ];
                }

                // NIK ada tapi milik orang lain (in-tenant)
                throw new ServiceErrorCode(
                    'NIK sudah terdaftar di sistem dan milik orang lain. '
                        .'Jika ini adalah anak Anda, silakan hubungi administrators sekolah.',
                    422,
                    ['nik' => $data['nik']]
                );
            }

            // ── STEP 2: Validasi KK (jika wali punya KK) ──────────────────────
            if ($wali->no_kk && isset($data['no_kk'])) {
                if ($wali->no_kk !== $data['no_kk']) {
                    throw new ServiceErrorCode(
                        'No KK tidak cocok dengan KK yang terdaftar di akun Anda.',
                        422,
                        ['field' => 'no_kk']
                    );
                }
            }

            // ── STEP 3: Buat Student baru (tenant-scoped) ─────────────────────
            $student = Student::create([
                'nik' => $data['nik'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'],
                'no_kk' => $data['no_kk'] ?? $wali->no_kk,
                'school_id' => $schoolId,
                'status' => 'active',
            ]);

            // ── STEP 4: Buat link wali_santri (tenant-scoped) ────────────────
            $waliSantri = WaliSantri::create([
                'user_id' => $wali->id,
                'student_id' => $student->id,
                'school_id' => $schoolId,
                'role' => $data['role'] ?? 'wali',
                'is_primary' => true,
                'status' => WaliSantri::STATUS_ACTIVE,
                'verified_at' => now(),
                'verified_by' => $wali->id,
            ]);

            return [
                'student' => $student,
                'wali_santri' => $waliSantri,
                'already_linked' => false,
            ];
        });
    }

    // ── Request Link: Wali已有 Santri → MintaJadi Wali Kedua ───────────────────

    /**
     * Wali yang sudah punya Santri minta jadi wali Santri lain.
     * Atau: wali baru minta jadi wali kedua/ketiga dari Santri yang sudah punya wali.
     *
     * Flow:
     * 1. Cek NIK Santri ada
     * 2. Cek wali ini belum terhubung ke Santri
     * 3. Cek tidak ada pending request
     * 4. Jika Santri belum punya wali → langsung link
     * 5. Jika Santri sudah punya wali:
     *    a. Ada approval_token → verifikasi → link
     *    b. Tanpa token → generate token → kirim email ke wali utama
     */
    public function requestLinkToStudent(array $data, User $wali): array
    {
        $schoolId = $this->currentSchoolId(true);

        return DB::transaction(function () use ($data, $wali, $schoolId) {
            // ── STEP 1: Cek Santri ada ───────────────────────────────────────────
            $student = Student::where('nik', $data['nik_santri'])->first();

            if (! $student) {
                throw new ServiceErrorCode(
                    'Santri dengan NIK tersebut tidak ditemukan. '
                        .'Pastikan NIK yang Anda masukkan benar.',
                    404,
                    ['nik_santri' => $data['nik_santri']]
                );
            }

            // Tenant guard: refuse cross-tenant student references. We return
            // the same NOT_FOUND error regardless of whether the NIK exists in
            // another school, to prevent cross-tenant existence leakage.
            $this->assertSameTenant($student);

            // ── STEP 2: Cek apakah sudah terhubung ──────────────────────────────
            $existingLink = WaliSantri::where('user_id', $wali->id)
                ->where('student_id', $student->id)
                ->where('school_id', $schoolId)
                ->first();

            if ($existingLink) {
                if ($existingLink->status === WaliSantri::STATUS_PENDING) {
                    throw new ServiceErrorCode(
                        'Anda sudah memiliki permintaan tertunda untuk Santri ini.',
                        422,
                        [
                            'message' => 'Anda sudah memiliki permintaan tertunda untuk Santri ini.',
                            'link_id' => $existingLink->id,
                        ]
                    );
                }

                // Sudah aktif
                return [
                    'student' => $student,
                    'wali_santri' => $existingLink,
                    'already_linked' => true,
                    'message' => 'Anda sudah terhubung dengan Santri ini.',
                ];
            }

            // ── STEP 3: Cek Santri sudah punya wali? (tenant-scoped) ────────────
            $existingWali = WaliSantri::with('user')
                ->where('student_id', $student->id)
                ->where('school_id', $schoolId)
                ->active()
                ->get();

            $role = $data['role'] ?? 'wali';

            // Santri belum punya wali sama sekali → langsung link
            if ($existingWali->isEmpty()) {
                // ── Langsung buat link aktif (tenant-scoped) ─────────────────────
                $waliSantri = WaliSantri::create([
                    'user_id' => $wali->id,
                    'student_id' => $student->id,
                    'school_id' => $schoolId,
                    'role' => $role,
                    'is_primary' => true,
                    'status' => WaliSantri::STATUS_ACTIVE,
                    'verified_at' => now(),
                    'verified_by' => $wali->id,
                ]);

                return [
                    'student' => $student,
                    'wali_santri' => $waliSantri,
                    'new_link' => true,
                    'message' => 'Santri berhasil terhubung ke akun Anda.',
                ];
            }

            // Santri sudah punya wali → proses otorisasi
            // ── STEP 4: Cek tidak ada pending request (tenant-scoped) ────────────
            $pending = WaliRegistrationToken::where('user_id', $wali->id)
                ->where('nik_santri', $data['nik_santri'])
                ->where('school_id', $schoolId)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($pending) {
                throw new ServiceErrorCode(
                    'Anda sudah memiliki permintaan aktif. '
                        .'Silakan tunggu konfirmasi dari wali utama.',
                    422,
                    [
                        'message' => 'Anda sudah memiliki permintaan aktif. '
                            .'Silakan tunggu konfirmasi dari wali utama.',
                        'expires_at' => $pending->expires_at->toIso8601String(),
                    ]
                );
            }

            // ── STEP 5: Cek MAX wali tercapai ─────────────────────────────────
            if ($existingWali->count() >= self::MAX_WALI_PER_STUDENT) {
                throw new ServiceErrorCode(
                    'Santri ini sudah memiliki maksimum '.self::MAX_WALI_PER_STUDENT.' wali.',
                    422,
                    [
                        'max_wali' => self::MAX_WALI_PER_STUDENT,
                        'current_count' => $existingWali->count(),
                        'message' => 'Santri ini sudah memiliki maksimum '.self::MAX_WALI_PER_STUDENT.' wali.',
                    ]
                );
            }

            // ── STEP 6: Generate token otorisasi (tenant-scoped) ────────────────────
            $token = bin2hex(random_bytes(32));

            $regToken = WaliRegistrationToken::create([
                'token' => $token,
                'user_id' => $wali->id,
                'school_id' => $schoolId,
                'nik_santri' => $data['nik_santri'],
                'no_kk' => $data['no_kk'] ?? null,
                'intent' => 'add_wali',
                'student_id' => $student->id,
                'expires_at' => now()->addHours(self::TOKEN_EXPIRY_HOURS),
            ]);

            // Kirim email ke wali utama
            $primaryWali = $existingWali->where('is_primary', true)->first();
            $anyWali = $existingWali->first();

            if ($primaryWali?->user) {
                Mail::to($primaryWali->user->email)->send(
                    new WaliAccessRequestMail($student, $wali, $token)
                );
            } elseif ($anyWali?->user) {
                Mail::to($anyWali->user->email)->send(
                    new WaliAccessRequestMail($student, $wali, $token)
                );
            }

            return [
                'student' => $student,
                'registration_token' => $regToken,
                'needs_approval' => true,
                'message' => 'Permintaan telah dikirim ke wali utama. '
                    .'Anda akan notified ketika permintaan disetujui.',
                'approval_token' => $token,
            ];
        });
    }

    // ── Approve / Reject request from another wali ───────────────────────────

    /**
     * Wali utama approve/reject request dari wali kedua.
     *
     * @throws \Exception Error codes: TOKEN_INVALID, TOKEN_EXPIRED, UNAUTHORIZED
     */
    public function approveRejectRequest(string $token, User $wali, string $action, ?string $note = null): array
    {
        return DB::transaction(function () use ($token, $wali, $action, $note) {
            // ── STEP 1: Cari token ──────────────────────────────────────────────
            $regToken = WaliRegistrationToken::where('token', $token)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if (! $regToken) {
                throw new ServiceErrorCode(
                    'Token tidak valid atau sudah kedaluwarsa.',
                    404,
                    ['message' => 'Token tidak valid atau sudah kedaluwarsa.']
                );
            }

            // Tenant guard: the token must belong to the active school context.
            // This is the most critical assertion in the approval flow — a
            // token issued in school A must never be redeemable from school B,
            // regardless of which wali's email receives the approval link.
            $this->assertTokenSameTenant($regToken);

            // ── STEP 2: Cek apakah wali ini punya akses ke Santri tersebut ────────
            $primaryLink = WaliSantri::where('user_id', $wali->id)
                ->where('student_id', $regToken->student_id)
                ->where('school_id', $regToken->school_id)
                ->active()
                ->first();

            // Jika belum ada link, cek apakah wali ini adalah primary atau punya seniority
            // Untuk approve, perlu jadi primary atau role 'ayah'/'ibu'
            if (! $primaryLink) {
                // Cek apakah ada wali lain yang punya akses — jika tidak, allow (karena dia yang pertama klaim)
                $anyLink = WaliSantri::where('student_id', $regToken->student_id)
                    ->where('school_id', $regToken->school_id)
                    ->active()
                    ->exists();

                if ($anyLink) {
                    throw new ServiceErrorCode(
                        'Anda tidak memiliki otoritas untuk menyetujui permintaan ini.',
                        403,
                        ['message' => 'Anda tidak memiliki otoritas untuk menyetujui permintaan ini.']
                    );
                }
            }

            // ── STEP 3: Proses approve/reject ───────────────────────────────────
            $student = Student::find($regToken->student_id);
            $requester = User::find($regToken->user_id);

            // Defense in depth: the student resolved from the token must be in
            // the same tenant as the token itself.
            $tokenSchoolId = $regToken->school_id;
            if ($student === null || $student->school_id !== $tokenSchoolId) {
                throw new ServiceErrorCode(
                    'Santri tidak ditemukan.',
                    404,
                    ['code' => 'STUDENT_NOT_FOUND']
                );
            }

            if ($action === 'approve') {
                // Cek MAX Wali
                $currentCount = WaliSantri::where('student_id', $regToken->student_id)
                    ->where('school_id', $tokenSchoolId)
                    ->active()
                    ->count();

                if ($currentCount >= self::MAX_WALI_PER_STUDENT) {
                    throw new ServiceErrorCode(
                        'Santri ini sudah mencapai maksimum wali.',
                        422,
                        ['message' => 'Santri ini sudah mencapai maksimum wali.']
                    );
                }

                // Buat link (tenant-scoped: inherit from token)
                $waliSantri = WaliSantri::create([
                    'user_id' => $regToken->user_id,
                    'student_id' => $regToken->student_id,
                    'school_id' => $tokenSchoolId,
                    'role' => 'wali',
                    'is_primary' => false,
                    'status' => WaliSantri::STATUS_ACTIVE,
                    'verified_at' => now(),
                    'verified_by' => $wali->id,
                ]);

                // Kirim notifikasi ke requester
                Mail::to($requester->email)->send(
                    new WaliRequestApprovedMail($student, $wali)
                );

                $regToken->used_at = now();
                $regToken->save();

                return [
                    'approved' => true,
                    'wali_santri' => $waliSantri,
                    'student' => $student,
                    'requester' => $requester,
                ];

            } else {
                // Reject
                $regToken->used_at = now();
                $regToken->save();

                // Kirim notifikasi ke requester
                Mail::to($requester->email)->send(
                    new WaliRequestRejectedMail($student, $wali, $note)
                );

                return [
                    'rejected' => true,
                    'student' => $student,
                    'requester' => $requester,
                    'note' => $note,
                ];
            }
        });
    }

    // ── Remove Wali-Santri link ─────────────────────────────��─────────────────

    /**
     * Lepas hubungan wali-Santri.
     * Bisa dilakukan oleh: (1) wali sendiri, (2) admin, (3) wali utama
     *
     * @throws \Exception Error codes: LINK_NOT_FOUND, CANNOT_REMOVE_LAST_WALI
     */
    public function removeLink(string $waliSantriId, ?User $actingUser = null, bool $isAdmin = false): void
    {
        $link = WaliSantri::with('student')->find($waliSantriId);

        if (! $link) {
            throw new ServiceErrorCode(
                'Hubungan wali-Santri tidak ditemukan.',
                404,
                ['code' => 'LINK_NOT_FOUND']
            );
        }

        // Tenant guard: admin / acting-user cross-tenant remove must be blocked.
        // When isAdmin=true, we still require the admin's context to match the
        // link's tenant — never allow admin to wipe records from another tenant.
        $schoolId = $this->currentSchoolId(true);
        if ($link->school_id !== $schoolId) {
            // Cross-tenant: hide existence.
            throw new ServiceErrorCode(
                'Hubungan wali-Santri tidak ditemukan.',
                404,
                ['code' => 'LINK_NOT_FOUND']
            );
        }

        $requesterRemove = false;
        $isAllowedByParent = false;

        if ($actingUser !== null) {
            if ($link->user_id === $actingUser->id) {
                $requesterRemove = true;
            } else {
                $parentLink = WaliSantri::where('user_id', $actingUser->id)
                    ->where('student_id', $link->student_id)
                    ->where('school_id', $schoolId)
                    ->where('is_primary', true)
                    ->where('status', WaliSantri::STATUS_ACTIVE)
                    ->exists();
                $isAllowedByParent = $parentLink;
            }
        }

        if ($actingUser !== null && ! $isAdmin && ! $requesterRemove && ! $isAllowedByParent) {
            throw new ServiceErrorCode(
                'Anda tidak memiliki izin untuk menghapus hubungan ini.',
                403,
                ['code' => 'FORBIDDEN']
            );
        }

        if ($link->is_primary && $link->status === WaliSantri::STATUS_ACTIVE) {
            $otherActive = WaliSantri::where('student_id', $link->student_id)
                ->where('school_id', $schoolId)
                ->where('id', '!=', $link->id)
                ->where('status', WaliSantri::STATUS_ACTIVE)
                ->exists();

            if ($otherActive) {
                throw new ServiceErrorCode(
                    'Tidak dapat menghapus wali utama. '
                        .'Pindahkan status wali utama ke wali lain terlebih dahulu.',
                    422,
                    ['code' => 'CANNOT_REMOVE_PRIMARY_HAS_OTHERS']
                );
            }
        }

        DB::transaction(function () use ($link) {
            $link->status = WaliSantri::STATUS_REVOKED;
            $link->verified_by = $link->verified_by;
            $link->save();

            $activeCount = WaliSantri::where('student_id', $link->student_id)
                ->where('school_id', $link->school_id)
                ->where('status', WaliSantri::STATUS_ACTIVE)
                ->count();

            if ($activeCount > 0) {
                return;
            }

            $student = $link->student;
            if ($student && $student->status === 'active') {
                // We deliberately do not delete or deactivate the student
                // record — owned identity persists across admin resets.
            }
        });
    }

    // ── Get Dashboard ────���────────────────────────────────────────────────────

    public function getDashboard(User $wali): array
    {
        // Gracefully degrade when no tenant context is available (e.g.
        // multi-school wali didn't pick a context). The API always has the
        // middleware applied so this is a defensive path only.
        $schoolId = $this->currentSchoolId(false);
        if ($schoolId === null) {
            return [
                'wali' => [
                    'id' => $wali->id,
                    'name' => $wali->name,
                    'email' => $wali->email,
                    'no_hp' => $wali->no_hp,
                ],
                'students' => [],
                'total_students' => 0,
            ];
        }

        $links = WaliSantri::with(['student', 'student.school'])
            ->where('user_id', $wali->id)
            ->where('school_id', $schoolId)
            ->active()
            ->get();

        $students = $links->map(fn ($link) => [
            'id' => $link->student->id,
            'nik' => $link->student->nik,
            'name' => $link->student->name,
            'gender' => $link->student->gender,
            'birth_date' => $link->student->birth_date?->format('Y-m-d'),
            'birth_place' => $link->student->birth_place,
            'role' => $link->role,
            'is_primary' => $link->is_primary,
            'school' => $link->student->school ? [
                'id' => $link->student->school->id,
                'name' => $link->student->school->name,
            ] : null,
            'other_walis' => WaliSantri::with('user:id,name,email,no_hp')
                ->where('student_id', $link->student->id)
                ->where('school_id', $schoolId)
                ->where('user_id', '!=', $wali->id)
                ->get()
                ->map(fn ($wl) => [
                    'user_id' => $wl->user_id,
                    'name' => $wl->user->name,
                    'role' => $wl->role,
                    'is_primary' => $wl->is_primary,
                ]),
        ]);

        return [
            'wali' => [
                'id' => $wali->id,
                'name' => $wali->name,
                'email' => $wali->email,
                'no_hp' => $wali->no_hp,
            ],
            'students' => $students,
            'total_students' => $students->count(),
        ];
    }

    public static function validateNikFormat(string $nik): bool
    {
        if (strlen($nik) !== 16 || ! ctype_digit($nik)) {
            return false;
        }

        $dayCode = (int) substr($nik, 6, 2);
        $monthCode = (int) substr($nik, 8, 2);

        if ($monthCode < 1 || $monthCode > 12) {
            return false;
        }
        if ($dayCode < 1 || $dayCode > 31) {
            return false;
        }

        return true;
    }

    public static function validateKkFormat(string $kk): bool
    {
        return strlen($kk) === 16 && ctype_digit($kk);
    }
}
