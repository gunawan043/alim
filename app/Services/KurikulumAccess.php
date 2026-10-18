<?php

namespace App\Services;

use App\Models\KurikulumEditGrant;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;

/**
 * Kewenangan modul Kurikulum & Perangkat Pembelajaran.
 *
 * - Tim Kurikulum (Koordinator Kurikulum / Tim Kurikulum / Kepala / Wakil /
 *   Super Admin) → mengelola CP, TP, ATP, dan Perangkat lintas mapel sekolah.
 * - Guru → mengelola TP/ATP/Perangkat untuk mapel yang diampu (SK aktif).
 *
 * Tidak memakai permission snapshot agar tetap kompatibel dengan registrar
 * Gate global; kewenangan berbasis jabatan/tugas tambahan + data mengajar.
 */
class KurikulumAccess
{
    /**
     * Pengelola kurikulum (editor): Koordinator/Tim Kurikulum + Super Admin.
     * KSP/Wakil adalah pengawas — tidak mengedit dokumen guru (kebijakan akses).
     */
    public function isKurikulumEditor(User $user): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $jabatan = $this->jabatan($user);

        if (str_contains($jabatan, 'kurikulum')) {
            return true;
        }

        return in_array('tim kurikulum', $this->tugasTambahan($user), true);
    }

    /**
     * Pengawas kurikulum (lihat semua + validasi): Kepala/Wakil Satuan Pendidikan.
     */
    public function isKurikulumSupervisor(User $user): bool
    {
        $jabatan = $this->jabatan($user);

        return str_contains($jabatan, 'kepala satuan pendidikan')
            || str_contains($jabatan, 'wakil kepala satuan pendidikan')
            || str_contains($jabatan, 'kepala sekolah')
            || str_contains($jabatan, 'wakil kepala sekolah');
    }

    /**
     * Tim kurikulum (editor + pengawas): boleh melihat seluruh dokumen sekolah.
     */
    public function isKurikulumTeam(User $user): bool
    {
        return $this->isKurikulumEditor($user) || $this->isKurikulumSupervisor($user);
    }

    /**
     * Tata Usaha: menerima paket final & repositori soal (tanpa hak edit soal).
     */
    public function isTataUsaha(User $user): bool
    {
        return str_contains($this->jabatan($user), 'tata usaha');
    }

    /**
     * Akses seluruh repositori soal: Waka, Kurikulum, TU, dan KSP
     * (Kepala/Wakil satuan pendidikan) + Super Admin.
     */
    public function canAccessAllBankSoal(User $user): bool
    {
        return $this->isKurikulumTeam($user) || $this->isTataUsaha($user);
    }

    /**
     * Hak melihat kunci jawaban/pembahasan lintas bank.
     *
     * Kebijakan: TU tidak otomatis memperoleh kunci — hanya sesuai kebutuhan
     * tugas administrasi (diberikan lewat izin khusus). Kurikulum/Waka/Kepala
     * (tim kurikulum) memperolehnya sesuai kewenangan organisasi.
     */
    public function canViewAllAnswerKeys(User $user): bool
    {
        return $this->isKurikulumTeam($user);
    }

    /**
     * Kebijakan Tahap 3: kunci jawaban & pembahasan hanya untuk guru
     * SERUMPUN (mengampu mapel dalam rumpun yang sama, aktif) dan tim
     * kurikulum. Pihak lain hanya pelihat — kunci disamarkan.
     */
    public function canViewAnswerKeys(User $user, ?Subject $subject = null): bool
    {
        if ($this->isKurikulumTeam($user)) {
            return true;
        }

        if (! $subject) {
            return false;
        }

        $resolver = app(SubjectGroupResolver::class);
        $group = $resolver->groupForSubject($subject);

        if (! $group) {
            return false;
        }

        return $resolver->groupsForTeacher($user)->contains(fn ($g) => (string) $g->id === (string) $group->id);
    }

    public function teachesSubject(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->exists();
    }

    /**
     * ID mapel yang benar-benar diampu (bukan serumpun).
     * Tim kurikulum editor → null (semua mapel).
     *
     * @return array<int, string>|null
     */
    public function taughtSubjectIds(User $user, ?string $schoolId = null, ?string $academicYearId = null): ?array
    {
        if ($this->isKurikulumEditor($user)) {
            return null;
        }

        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Boleh MEMBUAT dokumen baru (ATP/PROTA/PROSEM/RPM/AI draft) untuk mapel ini?
     * Hanya editor kurikulum atau guru pengampu — edit grant tidak memberi hak
     * membuat dokumen baru untuk mapel yang tidak diampu.
     */
    public function canAuthorSubject(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return $this->isKurikulumEditor($user)
            || $this->teachesSubject($user, $subjectId, $academicYearId, $schoolId);
    }

    /**
     * Boleh mengelola TP/ATP/Perangkat untuk mapel ini?
     */
    public function canManageSubject(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return $this->isKurikulumEditor($user)
            || $this->teachesSubject($user, $subjectId, $academicYearId, $schoolId)
            || $this->hasEditGrant($user, $subjectId, $academicYearId, $schoolId);
    }

    /**
     * Pemegang izin edit (permintaan yang telah disetujui) untuk mapel + TA ini.
     */
    public function hasEditGrant(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return KurikulumEditGrant::query()
            ->where('requested_by', $user->id)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', KurikulumEditGrant::STATUS_APPROVED)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->exists();
    }

    /**
     * Boleh memutuskan permintaan edit? Tim kurikulum (editor) atau pengampu mapel ybs.
     */
    public function canApproveEditGrant(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return $this->isKurikulumEditor($user)
            || $this->teachesSubject($user, $subjectId, $academicYearId, $schoolId);
    }

    /**
     * Boleh mengajukan permintaan edit? (bisa melihat, tetapi belum bisa mengelola).
     */
    public function canRequestEditGrant(User $user, string $subjectId, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        if (! $this->canViewSubjectDocs($user, $subjectId, $schoolId, $academicYearId)) {
            return false;
        }

        return ! ($academicYearId && $this->canManageSubject($user, $subjectId, $academicYearId, $schoolId));
    }

    /**
     * Boleh masuk modul Kurikulum? (tim kurikulum, guru/GTK pengajar).
     * TU dan unit non-pendidikan ditolak (kebijakan akses menu kurikulum).
     */
    public function canAccessKurikulumModule(User $user): bool
    {
        if ($this->isKurikulumTeam($user)) {
            return true;
        }

        if (str_starts_with($this->jabatan($user), 'guru')) {
            return true;
        }

        return TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * ID mapel yang boleh DILIHAT guru: mapel diampu + mapel serumpun.
     * Tim kurikulum → null (semua mapel).
     *
     * @return array<int, string>|null
     */
    public function viewableSubjectIds(User $user, ?string $schoolId = null, ?string $academicYearId = null): ?array
    {
        if ($this->isKurikulumTeam($user)) {
            return null;
        }

        $taught = TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();

        $groupIds = app(SubjectGroupResolver::class)->groupsForTeacher($user)->pluck('id')->all();

        $rumpun = $groupIds
            ? Subject::query()
                ->whereIn('subject_group_id', $groupIds)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->pluck('id')
                ->all()
            : [];

        return array_values(array_unique(array_merge($taught, $rumpun)));
    }

    /**
     * Boleh MELIHAT dokumen mapel ini? Tim kurikulum, guru pengampu, atau guru serumpun.
     */
    public function canViewSubjectDocs(User $user, string $subjectId, ?string $schoolId = null, ?string $academicYearId = null): bool
    {
        $viewable = $this->viewableSubjectIds($user, $schoolId, $academicYearId);

        if ($viewable === null) {
            return true;
        }

        return in_array((string) $subjectId, array_map('strval', $viewable), true);
    }

    private function jabatan(User $user): string
    {
        return strtolower(trim((string) ($user->employment?->jabatan ?? '')));
    }

    /**
     * @return array<int, string>
     */
    private function tugasTambahan(User $user): array
    {
        return $user->tugasTambahan
            ?->pluck('nama')
            ->map(fn ($nama) => strtolower(trim((string) $nama)))
            ->all() ?? [];
    }
}
