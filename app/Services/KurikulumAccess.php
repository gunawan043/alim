<?php

namespace App\Services;

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
    public function isKurikulumTeam(User $user): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $jabatan = $this->jabatan($user);

        if ($jabatan === '') {
            return false;
        }

        if (str_contains($jabatan, 'kurikulum')) {
            return true;
        }

        if (in_array($jabatan, ['kepala satuan pendidikan', 'wakil kepala satuan pendidikan'], true)) {
            return true;
        }

        return in_array('tim kurikulum', $this->tugasTambahan($user), true);
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
     * Boleh mengelola TP/ATP/Perangkat untuk mapel ini?
     */
    public function canManageSubject(User $user, string $subjectId, string $academicYearId, ?string $schoolId = null): bool
    {
        return $this->isKurikulumTeam($user)
            || $this->teachesSubject($user, $subjectId, $academicYearId, $schoolId);
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
