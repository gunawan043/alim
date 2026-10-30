<?php

namespace App\Services\Wali;

use App\Exceptions\ServiceErrorCode;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliLinkVerification;
use App\Models\WaliSantri;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penghubungan wali–santri berbasis verifikasi identitas.
 *
 * Alur: preview (menampilkan data santri + token) → confirm (membuat link).
 * - Ayah/ibu dengan data cocok → link langsung aktif.
 * - Wali non-orang tua → link pending sampai disetujui sekolah (TU).
 *
 * Data yang dicocokkan: NISN (10 digit) atau NIK (16 digit), No KK, dan
 * nama ibu kandung dengan toleransi typo ≥85%. Email/No HP tidak dipakai
 * untuk mencocokkan hubungan.
 */
class WaliLinkService
{
    public const TOKEN_TTL_MINUTES = 10;

    public const NAME_SIMILARITY_THRESHOLD = 0.85;

    public const MAX_WALI_PER_STUDENT = 5;

    /** Hubungan yang langsung aktif tanpa approval sekolah. */
    public const AUTO_APPROVE_RELATIONSHIPS = ['ayah', 'ibu'];

    /** Hubungan yang menunggu persetujuan sekolah. */
    public const VALID_RELATIONSHIPS = ['ayah', 'ibu', 'kakek', 'nenek', 'paman', 'bibi', 'wali', 'lainnya'];

    /**
     * Langkah 1: cocokkan identitas santri dan kembalikan data verifikasi.
     *
     * @return array<string, mixed>
     */
    public function preview(User $user, array $data): array
    {
        [$identity, $type] = $this->parseIdentity((string) ($data['identity_number'] ?? ''));
        $noKk = $this->digits((string) ($data['no_kk'] ?? ''));
        $motherName = (string) ($data['mother_name'] ?? '');
        $relationship = (string) ($data['relationship'] ?? '');

        if (! in_array($relationship, self::VALID_RELATIONSHIPS, true)) {
            throw new ServiceErrorCode('Hubungan wali tidak valid.', 422, ['code' => 'INVALID_RELATIONSHIP']);
        }

        $students = Student::query()
            ->with([
                'school:id,name',
                'currentClassHistory.studyGroup:id,name,grade_level_id',
                'currentClassHistory.studyGroup.gradeLevel:id,level,name',
            ])
            ->where($type, $identity)
            ->get();

        $matches = $students
            ->filter(fn (Student $student) => $this->matchesStudent($student, $type, $noKk, $motherName))
            ->values();

        $forcedStudentId = $data['student_id'] ?? null;

        if (is_string($forcedStudentId) && $forcedStudentId !== '') {
            $matches = $matches->where('id', $forcedStudentId)->values();
        }

        if ($matches->isEmpty()) {
            throw new ServiceErrorCode(
                'Data tidak cocok. Periksa kembali identitas santri, No KK, dan nama ibu kandung.',
                422,
                ['code' => 'WALI_LINK_MISMATCH']
            );
        }

        if ($matches->count() > 1) {
            return [
                'ambiguous' => true,
                'message' => 'Ditemukan lebih dari satu santri yang cocok. Pilih data yang benar.',
                'candidates' => $matches->map(fn (Student $student) => $this->formatCandidate($student))->values()->all(),
            ];
        }

        return $this->preparePreview($user, $matches->first(), $relationship);
    }

    /**
     * Langkah 2: konfirmasi token → buat link aktif/pending.
     *
     * @return array<string, mixed>
     */
    public function confirm(User $user, string $plainToken): array
    {
        $verification = WaliLinkVerification::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('user_id', $user->id)
            ->valid()
            ->first();

        if (! $verification) {
            throw new ServiceErrorCode(
                'Token verifikasi tidak valid atau sudah kedaluwarsa.',
                422,
                ['code' => 'WALI_LINK_TOKEN_INVALID']
            );
        }

        $student = Student::with('school:id,name')->find($verification->student_id);

        if (! $student) {
            throw new ServiceErrorCode('Santri tidak ditemukan.', 404, ['code' => 'STUDENT_NOT_FOUND']);
        }

        $existing = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            $verification->update(['used_at' => now()]);

            if ($existing->isActive() || $existing->isPending()) {
                return [
                    'already_linked' => true,
                    'status' => $existing->status,
                    'student' => $this->formatStudent($student),
                    'wali_santri' => $this->formatLink($existing),
                    'siblings' => $existing->isActive() ? $this->siblingsFor($user, $student->id) : [],
                ];
            }

            throw new ServiceErrorCode(
                'Hubungan wali sebelumnya tidak aktif. Silakan hubungi sekolah.',
                422,
                ['code' => 'WALI_LINK_INACTIVE']
            );
        }

        $activeCount = $this->activeGuardianCount($student);

        if ($activeCount >= self::MAX_WALI_PER_STUDENT) {
            throw new ServiceErrorCode(
                'Santri ini sudah memiliki maksimum '.self::MAX_WALI_PER_STUDENT.' wali aktif.',
                422,
                ['code' => 'WALI_LINK_LIMIT_REACHED']
            );
        }

        $isParent = in_array($verification->relationship, self::AUTO_APPROVE_RELATIONSHIPS, true);
        $isActive = $isParent;

        $link = DB::transaction(function () use ($verification, $student, $user, $isActive, $activeCount) {
            $link = WaliSantri::create([
                'user_id' => $user->id,
                'student_id' => $student->id,
                'school_id' => $student->school_id,
                'role' => $verification->relationship,
                'is_primary' => $isActive && $activeCount === 0,
                'status' => $isActive ? WaliSantri::STATUS_ACTIVE : WaliSantri::STATUS_PENDING,
                'verified_at' => $isActive ? now() : null,
                'verified_by' => null,
            ]);

            $verification->update(['used_at' => now()]);

            return $link;
        });

        $this->audit(
            $user,
            $isActive ? 'WALI_LINK_ACTIVE' : 'WALI_LINK_PENDING',
            $link->id
        );

        app(WaliLinkNotifier::class)->notifySchool($link, $isActive ? 'active' : 'pending');

        return [
            'already_linked' => false,
            'status' => $isActive ? WaliSantri::STATUS_ACTIVE : WaliSantri::STATUS_PENDING,
            'requires_approval' => ! $isActive,
            'student' => $this->formatStudent($student),
            'wali_santri' => $this->formatLink($link),
            'siblings' => $isActive ? $this->siblingsFor($user, $student->id) : [],
        ];
    }

    /**
     * Daftar santri yang sudah terhubung (aktif/pending) untuk wali.
     *
     * @return array<int, array<string, mixed>>
     */
    public function children(User $user): array
    {
        return WaliSantri::with(['student.school:id,name', 'student.currentClassHistory.studyGroup:id,name'])
            ->where('user_id', $user->id)
            ->whereIn('status', [WaliSantri::STATUS_ACTIVE, WaliSantri::STATUS_PENDING])
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn (WaliSantri $link) => [
                'wali_santri' => $this->formatLink($link),
                'status' => $link->status,
                'requires_approval' => $link->isPending(),
                'student' => $link->student ? $this->formatStudent($link->student) : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Kandidat saudara kandung (No KK sama + nama ibu mirip).
     *
     * @return array<int, array<string, mixed>>
     */
    public function siblingsFor(User $user, string $studentId): array
    {
        $primaryLink = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $studentId)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->first();

        if (! $primaryLink) {
            return [];
        }

        $student = Student::with(['school:id,name', 'currentClassHistory.studyGroup:id,name'])
            ->find($studentId);

        if (! $student || $this->digits((string) $student->no_kk) === '') {
            return [];
        }

        $noKk = $this->digits((string) $student->no_kk);

        return Student::with(['school:id,name', 'currentClassHistory.studyGroup:id,name'])
            ->where('id', '!=', $student->id)
            ->where('no_kk', $student->no_kk)
            ->whereNotNull('no_kk')
            ->get()
            ->filter(function (Student $candidate) use ($noKk, $student) {
                if ($this->digits((string) $candidate->no_kk) !== $noKk) {
                    return false;
                }

                return $this->nameSimilarity((string) $student->mother_name, (string) $candidate->mother_name)
                    >= self::NAME_SIMILARITY_THRESHOLD;
            })
            ->filter(function (Student $candidate) use ($user) {
                return ! WaliSantri::where('user_id', $user->id)
                    ->where('student_id', $candidate->id)
                    ->exists();
            })
            ->take(20)
            ->map(fn (Student $candidate) => $this->formatCandidate($candidate))
            ->values()
            ->all();
    }

    /**
     * Preview untuk kandidat saudara: identitas anak wajib dikonfirmasi.
     *
     * @return array<string, mixed>
     */
    public function previewSibling(User $user, array $data): array
    {
        $student = Student::with('school:id,name')->find($data['student_id'] ?? null);

        if (! $student) {
            throw new ServiceErrorCode('Santri tidak ditemukan.', 404, ['code' => 'STUDENT_NOT_FOUND']);
        }

        if (! $this->isSiblingCandidateOfLinkedStudent($user, $student)) {
            throw new ServiceErrorCode('Santri bukan kandidat saudara dari data yang terhubung.', 403, ['code' => 'NOT_SIBLING_CANDIDATE']);
        }

        [$identity, $type] = $this->parseIdentity((string) ($data['identity_number'] ?? ''));

        $storedIdentity = $type === 'nisn'
            ? $this->digits((string) $student->nisn)
            : $this->digits((string) $student->nik);

        if ($storedIdentity === '' || ! hash_equals($storedIdentity, $identity)) {
            throw new ServiceErrorCode(
                'Identitas anak tidak cocok dengan data santri.',
                422,
                ['code' => 'WALI_LINK_MISMATCH']
            );
        }

        $relationship = (string) ($data['relationship'] ?? 'wali');

        if (! in_array($relationship, self::VALID_RELATIONSHIPS, true)) {
            throw new ServiceErrorCode('Hubungan wali tidak valid.', 422, ['code' => 'INVALID_RELATIONSHIP']);
        }

        return $this->preparePreview($user, $student, $relationship);
    }

    /**
     * Daftar pengajuan pending untuk sekolah.
     */
    /**
     * Daftar link untuk halaman admin (pending/aktif/suspended/revoked).
     */
    public function adminLinks(array $filters, ?string $schoolId = null, int $perPage = 20)
    {
        $query = WaliSantri::with([
            'user:id,name,email,no_hp',
            'student:id,name,nisn,nik,no_kk,school_id',
            'student.school:id,name',
        ]);

        if ($schoolId !== null && $schoolId !== '') {
            $query->where('school_id', $schoolId);
        }

        $status = $filters['status'] ?? 'pending';

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%");
                })->orWhereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                });
            });
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(?string $schoolId = null): array
    {
        $counts = WaliSantri::query()
            ->when($schoolId !== null && $schoolId !== '', fn ($query) => $query->where('school_id', $schoolId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        foreach ([
            WaliSantri::STATUS_PENDING,
            WaliSantri::STATUS_ACTIVE,
            WaliSantri::STATUS_SUSPENDED,
            WaliSantri::STATUS_REVOKED,
        ] as $status) {
            $counts[$status] = $counts[$status] ?? 0;
        }

        return $counts;
    }

    public function findForAdmin(string $linkId, ?string $schoolId = null): WaliSantri
    {
        $link = WaliSantri::with(['user', 'student.school'])->findOrFail($linkId);

        if ($schoolId !== null && (string) $link->school_id !== (string) $schoolId) {
            throw new ServiceErrorCode(
                'Data penghubungan tidak ditemukan pada sekolah Anda.',
                404,
                ['code' => 'WALI_LINK_NOT_FOUND']
            );
        }

        return $link;
    }

    public function approve(User $actor, string $linkId): WaliSantri
    {
        return DB::transaction(function () use ($actor, $linkId) {
            $link = WaliSantri::where('status', WaliSantri::STATUS_PENDING)->findOrFail($linkId);

            $activeCount = $this->activeGuardianCount($link->student);

            if ($activeCount >= self::MAX_WALI_PER_STUDENT) {
                throw new ServiceErrorCode(
                    'Santri ini sudah memiliki maksimum '.self::MAX_WALI_PER_STUDENT.' wali aktif.',
                    422,
                    ['code' => 'WALI_LINK_LIMIT_REACHED']
                );
            }

            $link->update([
                'status' => WaliSantri::STATUS_ACTIVE,
                'is_primary' => $activeCount === 0,
                'verified_at' => now(),
                'verified_by' => $actor->id,
            ]);

            $this->audit($actor, 'WALI_LINK_APPROVED', $link->id);

            return $link->fresh(['user', 'student']);
        });
    }

    public function reject(User $actor, string $linkId): WaliSantri
    {
        $link = WaliSantri::where('status', WaliSantri::STATUS_PENDING)->findOrFail($linkId);

        $link->update([
            'status' => WaliSantri::STATUS_REVOKED,
            'verified_at' => now(),
            'verified_by' => $actor->id,
        ]);

        $this->audit($actor, 'WALI_LINK_REJECTED', $link->id);

        return $link->fresh(['user', 'student']);
    }

    public function suspend(User $actor, string $linkId, string $action = 'WALI_LINK_SUSPENDED'): WaliSantri
    {
        $link = WaliSantri::findOrFail($linkId);

        $link->update(['status' => WaliSantri::STATUS_SUSPENDED]);

        $this->audit($actor, $action, $link->id);

        return $link->fresh(['user', 'student']);
    }

    public function revoke(User $actor, string $linkId): WaliSantri
    {
        $link = WaliSantri::findOrFail($linkId);

        $link->update(['status' => WaliSantri::STATUS_REVOKED]);

        $this->audit($actor, 'WALI_LINK_REVOKED', $link->id);

        return $link->fresh(['user', 'student']);
    }

    /**
     * @return array<string, mixed>
     */
    private function preparePreview(User $user, Student $student, string $relationship): array
    {
        $existing = WaliSantri::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            if ($existing->isActive() || $existing->isPending()) {
                return [
                    'ambiguous' => false,
                    'already_linked' => true,
                    'status' => $existing->status,
                    'requires_approval' => $existing->isPending(),
                    'message' => $existing->isActive()
                        ? 'Santri ini sudah terhubung dengan akun Anda.'
                        : 'Penghubungan santri ini masih menunggu persetujuan sekolah.',
                    'student' => $this->formatStudent($student),
                    'wali_santri' => $this->formatLink($existing),
                ];
            }

            throw new ServiceErrorCode(
                'Hubungan wali sebelumnya tidak aktif. Silakan hubungi sekolah.',
                422,
                ['code' => 'WALI_LINK_INACTIVE']
            );
        }

        $activeCount = $this->activeGuardianCount($student);

        if ($activeCount >= self::MAX_WALI_PER_STUDENT) {
            throw new ServiceErrorCode(
                'Santri ini sudah memiliki maksimum '.self::MAX_WALI_PER_STUDENT.' wali aktif.',
                422,
                ['code' => 'WALI_LINK_LIMIT_REACHED']
            );
        }

        WaliLinkVerification::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plainToken = bin2hex(random_bytes(32));

        $verification = WaliLinkVerification::create([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'token_hash' => hash('sha256', $plainToken),
            'relationship' => $relationship,
            'method' => 'identity',
            'ip_address' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 1000),
            'expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ]);

        return [
            'ambiguous' => false,
            'already_linked' => false,
            'status' => 'preview',
            'requires_approval' => ! in_array($relationship, self::AUTO_APPROVE_RELATIONSHIPS, true),
            'student' => $this->formatStudent($student),
            'relationship' => $relationship,
            'guardians_count' => $activeCount,
            'max_guardians' => self::MAX_WALI_PER_STUDENT,
            'verification_token' => $plainToken,
            'expires_at' => $verification->expires_at?->toIso8601String(),
        ];
    }

    private function matchesStudent(Student $student, string $type, string $noKk, string $motherName): bool
    {
        $studentKk = $this->digits((string) $student->no_kk);

        if ($studentKk !== '') {
            if (! hash_equals($studentKk, $noKk)) {
                return false;
            }
        } elseif ($type !== 'nik') {
            // Data KK santri kosong → wajib verifikasi lewat NIK.
            return false;
        }

        return $this->nameSimilarity($motherName, (string) $student->mother_name)
            >= self::NAME_SIMILARITY_THRESHOLD;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseIdentity(string $value): array
    {
        $digits = $this->digits($value);

        return match (strlen($digits)) {
            10 => [$digits, 'nisn'],
            16 => [$digits, 'nik'],
            default => throw new ServiceErrorCode(
                'Nomor identitas harus NISN (10 digit) atau NIK (16 digit).',
                422,
                ['code' => 'INVALID_IDENTITY']
            ),
        };
    }

    private function isSiblingCandidateOfLinkedStudent(User $user, Student $candidate): bool
    {
        $candidateKk = $this->digits((string) $candidate->no_kk);

        if ($candidateKk === '') {
            return false;
        }

        $linkedStudents = Student::query()
            ->whereIn('id', WaliSantri::where('user_id', $user->id)
                ->where('status', WaliSantri::STATUS_ACTIVE)
                ->pluck('student_id'))
            ->get();

        foreach ($linkedStudents as $linked) {
            if ($this->digits((string) $linked->no_kk) !== $candidateKk) {
                continue;
            }

            if ($this->nameSimilarity((string) $linked->mother_name, (string) $candidate->mother_name)
                >= self::NAME_SIMILARITY_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    private function activeGuardianCount(Student $student): int
    {
        return WaliSantri::where('student_id', $student->id)
            ->where('status', WaliSantri::STATUS_ACTIVE)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'nama' => $student->name,
            'nisn' => $this->maskIdentity($student->nisn),
            'nik' => $this->maskIdentity($student->nik),
            'sekolah' => $student->school?->name,
            'sekolah_id' => $student->school_id,
            'kelas' => $student->currentClassHistory?->studyGroup?->name,
            'status' => $student->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCandidate(Student $student): array
    {
        return [
            'student_id' => $student->id,
            'nama' => $this->maskName($student->name),
            'nisn' => $this->maskIdentity($student->nisn),
            'nik' => $this->maskIdentity($student->nik),
            'sekolah' => $student->school?->name,
            'kelas' => $student->currentClassHistory?->studyGroup?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLink(WaliSantri $link): array
    {
        return [
            'id' => $link->id,
            'role' => $link->role,
            'status' => $link->status,
            'is_primary' => (bool) $link->is_primary,
            'verified_at' => $link->verified_at?->toIso8601String(),
        ];
    }

    private function maskIdentity(?string $value): ?string
    {
        $value = $this->digits((string) $value);

        if ($value === '') {
            return null;
        }

        if (strlen($value) <= 6) {
            return substr($value, 0, 2).str_repeat('*', max(0, strlen($value) - 2));
        }

        return substr($value, 0, 4).str_repeat('*', max(0, strlen($value) - 6)).substr($value, -2);
    }

    private function maskName(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name)) ?: [];

        return collect($words)
            ->filter()
            ->map(function (string $word) {
                $length = mb_strlen($word);

                return mb_substr($word, 0, 1).str_repeat('*', max(2, $length - 1));
            })
            ->implode(' ');
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Similarity nama setelah normalisasi; toleran typo & urutan kata.
     */
    private function nameSimilarity(string $left, string $right): float
    {
        $a = $this->normalizeName($left);
        $b = $this->normalizeName($right);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $wordsA = explode(' ', $a);
        $wordsB = explode(' ', $b);
        sort($wordsA);
        sort($wordsB);

        if ($wordsA === $wordsB) {
            return 1.0;
        }

        $percent = 0.0;
        similar_text($a, $b, $percent);

        $maxLength = max(mb_strlen($a), mb_strlen($b));
        $levenshtein = $maxLength > 0
            ? 1 - (levenshtein($a, $b) / $maxLength)
            : 0.0;

        return max($percent / 100, $levenshtein);
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));

        // Gelar/awalan umum yang sering ikut tertulis.
        $value = preg_replace('/\b(hj|hjh|haji|hajjah|drs|dra|dr|ir|prof|s\.?pd|m\.?pd|s\.?si|m\.?si|s\.?kom|m\.?kom)\b\.?/u', ' ', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function audit(User $actor, string $action, string $linkId): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'table_name' => 'wali_santri',
            'record_id' => $linkId,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
