<?php

namespace App\Http\Services;

use App\Exceptions\ServiceErrorCode;
use App\Models\User;
use App\Models\Student;
use App\Models\WaliSantri;
use App\Models\WaliRegistrationToken;
use App\Mail\WaliAccessRequestMail;
use App\Mail\WaliRequestApprovedMail;
use App\Mail\WaliRequestRejectedMail;
use App\Mail\NewStudentRegisteredMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WaliSantriService
{
    private const TOKEN_EXPIRY_HOURS = 24;
    private const MAX_WALI_PER_STUDENT = 5;

    // ── Register Student + Link to Wali ─────────────────────────────────────

    /**
     * Daftarkan Santi baru dan hubungkan ke akun wali.
     *
     * @throws \Exception Error codes: NIK_ALREADY_EXISTS, KK_MISMATCH,
     *                    USER_HAS_NO_KK, MAX_WALI_EXCEEDED, DB_ERROR
     */
    public function registerStudentAndLink(array $data, User $wali): array
    {
        return DB::transaction(function () use ($data, $wali) {
            // ── STEP 1: Cek NIK sudah terdaftar? ─────────────────────────────
            $existingStudent = Student::where('nik', $data['nik'])->first();

            if ($existingStudent) {
                // NIK sudah ada → cek apakah sudah terhubung ke wali ini
                $existingLink = WaliSantri::where('user_id', $wali->id)
                    ->where('student_id', $existingStudent->id)
                    ->first();

                if ($existingLink) {
                    return [
                        'student' => $existingStudent,
                        'wali_santri' => $existingLink,
                        'already_linked' => true,
                    ];
                }

                // NIK ada tapi milik orang lain
                throw new ServiceErrorCode(
                    'NIK sudah terdaftar di sistem dan milik orang lain. '
                        . 'Jika ini adalah anak Anda, silakan hubungi administrators sekolah.',
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

            // ── STEP 3: Buat Student baru ──────────────────────────────────────
            $student = Student::create([
                'nik' => $data['nik'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'],
                'no_kk' => $data['no_kk'] ?? $wali->no_kk,
                'status' => 'active',
            ]);

            // ── STEP 4: Buat link wali_santri (status = active, is_primary = true) ──
            $waliSantri = WaliSantri::create([
                'user_id' => $wali->id,
                'student_id' => $student->id,
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

    // ── Request Link: Wali已有 Santi → MintaJadi Wali Kedua ───────────────────

    /**
     * Wali yang sudah punya Santi minta jadi wali Santi lain.
     * Atau: wali baru minta jadi wali kedua/ketiga dari Santi yang sudah punya wali.
     *
     * Flow:
     * 1. Cek NIK Santi ada
     * 2. Cek wali ini belum terhubung ke Santi
     * 3. Cek tidak ada pending request
     * 4. Jika Santi belum punya wali → langsung link
     * 5. Jika Santi sudah punya wali:
     *    a. Ada approval_token → verifikasi → link
     *    b. Tanpa token → generate token → kirim email ke wali utama
     */
    public function requestLinkToStudent(array $data, User $wali): array
    {
        return DB::transaction(function () use ($data, $wali) {
            // ── STEP 1: Cek Santi ada ───────────────────────────────────────────
            $student = Student::where('nik', $data['nik_santri'])->first();

            if (!$student) {
                throw new ServiceErrorCode(
                    'Santi dengan NIK tersebut tidak ditemukan. '
                        . 'Pastikan NIK yang Anda masukkan benar.',
                    404,
                    ['nik_santri' => $data['nik_santri']]
                );
            }

            // ── STEP 2: Cek apakah sudah terhubung ──────────────────────────────
            $existingLink = WaliSantri::where('user_id', $wali->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existingLink) {
                if ($existingLink->status === WaliSantri::STATUS_PENDING) {
                    throw new ServiceErrorCode(
                        'Anda sudah memiliki permintaan tertunda untuk Santi ini.',
                        422,
                        [
                            'message' => 'Anda sudah memiliki permintaan tertunda untuk Santi ini.',
                            'link_id' => $existingLink->id,
                        ]
                    );
                }

                // Sudah aktif
                return [
                    'student' => $student,
                    'wali_santri' => $existingLink,
                    'already_linked' => true,
                    'message' => 'Anda sudah terhubung dengan Santi ini.',
                ];
            }

            // ── STEP 3: Cek Santi sudah punya wali? ──────────────────────────────
            $existingWali = WaliSantri::with('user')
                ->where('student_id', $student->id)
                ->active()
                ->get();

            $role = $data['role'] ?? 'wali';

            // Santi belum punya wali sama sekali → langsung link
            if ($existingWali->isEmpty()) {
                // ── Langsung buat link aktif ───────────────────────────────────
                $waliSantri = WaliSantri::create([
                    'user_id' => $wali->id,
                    'student_id' => $student->id,
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
                    'message' => 'Santi berhasil terhubung ke akun Anda.',
                ];
            }

            // Santi sudah punya wali → proses otorisasi
            // ── STEP 4: Cek tidak ada pending request ─────────────────────────
            $pending = WaliRegistrationToken::where('user_id', $wali->id)
                ->where('nik_santri', $data['nik_santri'])
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($pending) {
                throw new ServiceErrorCode(
                    'Anda sudah memiliki permintaan aktif. '
                        . 'Silakan tunggu konfirmasi dari wali utama.',
                    422,
                    [
                        'message' => 'Anda sudah memiliki permintaan aktif. '
                            . 'Silakan tunggu konfirmasi dari wali utama.',
                        'expires_at' => $pending->expires_at->toIso8601String(),
                    ]
                );
            }

            // ── STEP 5: Cek MAX wali tercapai ─────────────────────────────────
            if ($existingWali->count() >= self::MAX_WALI_PER_STUDENT) {
                throw new ServiceErrorCode(
                    "Santi ini sudah memiliki maksimum " . self::MAX_WALI_PER_STUDENT . " wali.",
                    422,
                    [
                        'max_wali' => self::MAX_WALI_PER_STUDENT,
                        'current_count' => $existingWali->count(),
                        'message' => "Santi ini sudah memiliki maksimum " . self::MAX_WALI_PER_STUDENT . " wali.",
                    ]
                );
            }

            // ── STEP 6: Generate token otorisasi ────────────────────────────────
            $token = bin2hex(random_bytes(32));

            $regToken = WaliRegistrationToken::create([
                'token' => $token,
                'user_id' => $wali->id,
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
                    . 'Anda akan notified ketika permintaan disetujui.',
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

            if (!$regToken) {
                throw new ServiceErrorCode(
                    'Token tidak valid atau sudah kedaluwarsa.',
                    404,
                    ['message' => 'Token tidak valid atau sudah kedaluwarsa.']
                );
            }

            // ── STEP 2: Cek apakah wali ini punya akses ke Santi tersebut ────────
            $primaryLink = WaliSantri::where('user_id', $wali->id)
                ->where('student_id', $regToken->student_id)
                ->active()
                ->first();

            // Jika belum ada link, cek apakah wali ini adalah primary atau punya seniority
            // Untuk approve, perlu jadi primary atau role 'ayah'/'ibu'
            if (!$primaryLink) {
                // Cek apakah ada wali lain yang punya akses — jika tidak, allow (karena dia yang pertama klaim)
                $anyLink = WaliSantri::where('student_id', $regToken->student_id)
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

            if ($action === 'approve') {
                // Cek MAX Wali
                $currentCount = WaliSantri::where('student_id', $regToken->student_id)
                    ->active()
                    ->count();

                if ($currentCount >= self::MAX_WALI_PER_STUDENT) {
                    throw new ServiceErrorCode(
                        'Santi ini sudah mencapai maksimum wali.',
                        422,
                        ['message' => 'Santi ini sudah mencapai maksimum wali.']
                    );
                }

                // Buat link
                $waliSantri = WaliSantri::create([
                    'user_id' => $regToken->user_id,
                    'student_id' => $regToken->student_id,
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
     * Lepas hubungan wali-Santi.
     * Bisa dilakukan oleh: (1) wali sendiri, (2) admin, (3) wali utama
     *
     * @throws \Exception Error codes: LINK_NOT_FOUND, CANNOT_REMOVE_LAST_WALI
     */
    public function removeLink(string $waliSantriId, ?User $actingUser = null, bool $isAdmin = false): void
    {
        $link = WaliSantri::with('student')->find($waliSantriId);

        if (!$link) {
            throw new ServiceErrorCode(
                'Hubungan wali-Santi tidak ditemukan.',
                404,
                ['message' => 'Hubungan wali-Santi tidak ditemukan.']
            );
        }

        // Cek apakah ini link terakhir
        $activeLinks = WaliSantri::where('student_id', $link->student_id)
            ->active()
            ->count();

        if ($activeLinks <= 1 && !$isAdmin) {
            throw new ServiceErrorCode(
                'Tidak dapat melepas hubungan terakhir. '
                    . 'Hubungi administrators untuk bantuan.',
                422,
                ['message' => 'Tidak dapat melepas hubungan terakhir.']
            );
        }

        $link->status = WaliSantri::STATUS_SUSPENDED;
        $link->save();
    }

    // ── Get Dashboard ────���────────────────────────────────────────────────────

    public function getDashboard(User $wali): array
    {
        $links = WaliSantri::with(['student', 'student.school'])
            ->where('user_id', $wali->id)
            ->active()
            ->get();

        $students = $links->map(fn($link) => [
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
                ->active()
                ->where('user_id', '!=', $wali->id)
                ->get()
                ->map(fn($wl) => [
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

    // ── Validate NIK Format ───────────────────────────────────────────────────

    public static function validateNikFormat(string $nik): bool
    {
        if (strlen($nik) !== 16 || !ctype_digit($nik)) {
            return false;
        }

        $dayCode = (int) substr($nik, 6, 2);
        $monthCode = (int) substr($nik, 8, 2);

        if ($monthCode < 1 || $monthCode > 12) return false;
        if ($dayCode < 1 || $dayCode > 31) return false;

        return true;
    }

    public static function validateKkFormat(string $kk): bool
    {
        return strlen($kk) === 16 && ctype_digit($kk);
    }
}
