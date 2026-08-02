<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Support\Facades\DB;

class WaliSantriService
{
    private const MAX_WALI_PER_SANTRI = 5;

    private const TOKEN_EXPIRY_HOURS = 48;

    /**
     * Daftarkan NIK Santri baru dan langsung klaim ke wali yang login.
     *
     * @param  User  $wali  User yang login (wali)
     * @param  array  $data  { nik, name, gender, birth_place, birth_date, no_kk, role }
     * @return array  { student, wali_santri, token }
     *
     * @throws \Exception
     */
    public function registerStudentAndClaim(User $wali, array $data): array
    {
        $nik = $data['nik'];
        $role = $data['role'] ?? 'ayah';

        // ── Tier 1: Cek NIK sudah ada di sistem ─────────────────────────────────
        $existingStudent = Student::where('nik', $nik)->first();

        if ($existingStudent) {
            // ── Tier 2: Cek apakah sudah terhubung ke wali ini ─────────────────
            $existingLink = WaliSantri::where('user_id', $wali->id)
                ->where('student_id', $existingStudent->id)
                ->where('role', $role)
                ->first();

            if ($existingLink) {
                return [
                    'student' => $existingStudent,
                    'wali_santri' => $existingLink,
                    'token' => null,
                    'already_linked' => true,
                ];
            }

            // ── Cek apakah sudah punya MAX wali ───────────────────────────────
            $currentCount = WaliSantri::where('student_id', $existingStudent->id)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= self::MAX_WALI_PER_SANTRI) {
                throw new \Exception(
                    'Santri ini sudah mencapai batas maksimum ({self::MAX_WALI_PER_SANTRI}) wali aktif. '.
                    'Hubungi administrators jika ada situasi khusus.',
                    422
                );
            }

            // ── Santri sudah ada → proses: minta verifikasi dari wali pertama ───
            return $this->linkToExistingStudent($wali, $existingStudent, $role, $data);
        }

        // ── Santri belum ada → daftarkan baru ──────────────────────────────────
        return $this->createNewStudentAndClaim($wali, $data);
    }

    /**
     * Minta jadi wali tambahan Santri yang sudah punya wali.
     */
    public function requestAsWali(User $wali, string $nikSantri, string $role, ?string $noKk = null): array
    {
        $student = Student::where('nik', $nikSantri)->firstOrFail();

        // Cek apakah sudah ada hubungan
        $existingLink = WaliSantri::where('user_id', $wali->id)
            ->where('student_id', $student->id)
            ->where('role', $role)
            ->first();

        if ($existingLink) {
            throw new \Exception('Hubungan wali-santri sudah ada.', 409);
        }

        // Cek MAX
        $count = WaliSantri::where('student_id', $student->id)->where('status', 'active')->count();
        if ($count >= self::MAX_WALI_PER_SANTRI) {
            throw new \Exception('Santri sudah mencapai batas maksimum wali aktif.', 422);
        }

        // Validasi No KK Santri (jika provided)
        if ($noKk && $student->no_kk && $noKk !== $student->no_kk) {
            throw new \Exception(
                'No KK yang Anda masukkan tidak cocok dengan data KK Santri.',
                422
            );
        }

        // Buat request: status = pending, generate access token untuk wali utama
        $link = WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'role' => $role,
            'is_primary' => false,
            'status' => 'pending',
        ]);

        // Generate token otorisasi → kirim notifikasi ke wali utama
        $accessToken = $link->generateAccessToken();

        // Notifikasi ke wali utama (yang first)
        $primaryLink = WaliSantri::where('student_id', $student->id)
            ->where('is_primary', true)
            ->where('status', 'active')
            ->first();

        if ($primaryLink) {
            $this->sendApprovalRequestNotification($primaryLink, $link, $wali, $student, $accessToken);
        }

        return [
            'wali_santri' => $link,
            'access_token' => $accessToken, // plain token — ditampilkan ke user atau dikirim email
            'santri' => $student,
            'status' => 'pending_approval',
        ];
    }

    /**
     * Approve / reject request dari wali kedua.
     */
    public function handleApproval(WaliSantri $link, string $action, ?User $approver = null, ?string $note = null): WaliSantri
    {
        if ($action === 'approve') {
            $link->markAsVerified($approver ?? auth()->user());

            // Kirim notifikasi ke wali kedua bahwa permintaannya disetujui
            Notification::create([
                'user_id' => $link->user_id,
                'type' => 'wali_request_approved',
                'title' => 'Persetujuan Wali Diterima',
                'message' => "Anda sekarang terhubung sebagai {$link->role} dari {$link->student->name}.",
                'data' => json_encode(['wali_santri_id' => $link->id, 'student_id' => $link->student_id]),
                'action_url' => "/dashboard/santri/{$link->student_id}",
            ]);
        } else {
            // reject → hapus link
            $link->delete();

            Notification::create([
                'user_id' => $link->user_id,
                'type' => 'wali_request_rejected',
                'title' => 'Persetujuan Wali Ditolak',
                'message' => "Permintaan menjadi wali {$link->role} dari {$link->student->name} ditolak.",
                'data' => json_encode(['student_nik' => $link->student->nik]),
            ]);
        }

        return $link;
    }

    /**
     * Terima koneksi: siswa baru + klaim otomatis ke wali.
     */
    private function createNewStudentAndClaim(User $wali, array $data): array
    {
        return DB::transaction(function () use ($wali, $data) {
            $student = Student::create([
                'nik' => $data['nik'],
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'no_kk' => $data['no_kk'] ?? null,
                'status' => 'active',
            ]);

            $link = WaliSantri::create([
                'user_id' => $wali->id,
                'student_id' => $student->id,
                'role' => $data['role'] ?? 'ayah',
                'is_primary' => true,
                'status' => 'active',
                'verified_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $wali->id,
                'action' => 'STUDENT_REGISTERED_BY_WALI',
                'table_name' => 'students',
                'record_id' => $student->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return ['student' => $student, 'wali_santri' => $link, 'token' => null, 'already_linked' => false];
        });
    }

    /**
     * Hubungkan ke siswa yang sudah ada (pending approval dari wali utama).
     */
    private function linkToExistingStudent(User $wali, Student $student, string $role, array $data): array
    {
        return DB::transaction(function () use ($wali, $student, $role) {
            // Cek apakah ada wali utama yang aktif
            $primaryLink = WaliSantri::where('student_id', $student->id)
                ->where('is_primary', true)
                ->where('status', 'active')
                ->first();

            if ($primaryLink && $primaryLink->user_id !== $wali->id) {
                // Ada wali lain → buat request pending, jangan langsung active
                $link = WaliSantri::create([
                    'user_id' => $wali->id,
                    'student_id' => $student->id,
                    'role' => $role,
                    'is_primary' => false,
                    'status' => 'pending',
                ]);

                $accessToken = $link->generateAccessToken();
                $this->sendApprovalRequestNotification($primaryLink, $link, $wali, $student, $accessToken);

                return [
                    'student' => $student,
                    'wali_santri' => $link,
                    'token' => $accessToken,
                    'already_linked' => false,
                    'requires_approval' => true,
                ];
            }

            // Tidak ada wali aktif → langsung activate (menjadi wali utama)
            $link = WaliSantri::create([
                'user_id' => $wali->id,
                'student_id' => $student->id,
                'role' => $role,
                'is_primary' => true,
                'status' => 'active',
                'verified_at' => now(),
            ]);

            return ['student' => $student, 'wali_santri' => $link, 'token' => null, 'already_linked' => false];
        });
    }

    /**
     * Kirim notifikasi ke wali utama untuk approval request.
     */
    private function sendApprovalRequestNotification(
        WaliSantri $primaryLink,
        WaliSantri $newLink,
        User $requester,
        Student $student,
        string $accessToken
    ): void {
        Notification::create([
            'user_id' => $primaryLink->user_id,
            'type' => 'wali_approval_request',
            'title' => 'Permintaan Menjadi Wali',
            'message' => "{$requester->name} ingin menjadi {$newLink->role} dari {$student->name}. "
                ."Jika ini benar, izinkan dari menu Santri > {$student->name}.",
            'data' => json_encode([
                'wali_santri_id' => $newLink->id,
                'student_id' => $student->id,
                'requester_id' => $requester->id,
                'requester_name' => $requester->name,
                'role' => $newLink->role,
                'access_token' => $accessToken,
            ]),
            'action_url' => "/dashboard/santri/{$student->id}",
        ]);
    }

    /**
     * Verifikasi NIK format saja (bukan validasi online).
     */
    public function validateNikFormat(string $nik): array
    {
        $errors = [];

        if (! preg_match('/^\d{16}$/', $nik)) {
            return ['valid' => false, 'errors' => ['NIK harus terdiri dari tepat 16 digit angka.']];
        }

        $provinceCode = (int) substr($nik, 0, 2);
        if ($provinceCode < 1 || $provinceCode > 91) {
            $errors[] = 'Kode provinsi pada NIK tidak valid.';
        }

        $genderDigit = (int) $nik[6];
        if ($genderDigit === 0) {
            $errors[] = 'Digit kode gender pada NIK tidak valid.';
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors];
    }

    /**
     * Cek apakah NIK sudah terdaftar di siswa lain (dengan wali berbeda).
     */
    public function checkNikAvailability(string $nik): array
    {
        $student = Student::where('nik', $nik)->first();

        if (! $student) {
            return ['available' => true, 'exists' => false];
        }

        $link = WaliSantri::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        return [
            'available' => false,
            'exists' => true,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'linked_to_user_id' => $link ? $link->user_id : null,
            'suggestion' => 'NIK sudah terdaftar. Minta wali utama untuk mengundang Anda melalui menu "Undang Wali".',
        ];
    }
}
