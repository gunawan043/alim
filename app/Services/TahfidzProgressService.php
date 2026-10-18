<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzMaster;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;

/**
 * Menghitung progres juz dan rekap capaian dari setoran yang sudah diverifikasi.
 *
 * Progres juz hanya memakai setoran ziyadah berstatus lulus. Murajaah dan
 * tikror tetap dihitung pada rekap, tetapi tidak menambah halaman baru.
 */
class TahfidzProgressService
{
    public function recalculateForSetoran(TahfidzSetoran $setoran): void
    {
        $setoran->loadMissing('group:id,academic_year_id');

        $this->recalculateStudent(
            $setoran->student_id,
            $setoran->academic_year_id,
            $setoran->tahfidz_group_id,
        );
    }

    public function recalculateStudent(string $studentId, string $academicYearId, ?string $groupId = null, ?string $semester = null): void
    {
        $verified = TahfidzSetoran::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('verification_status', 'verified')
            ->orderBy('setoran_date')
            ->get();

        $juzTotals = TahfidzJuzMaster::query()
            ->pluck('total_halaman', 'juz_number')
            ->map(fn ($value) => (float) $value)
            ->all();

        $ziyadah = $verified
            ->where('setoran_type', 'ziyadah')
            ->where('status', 'lulus')
            ->filter(fn ($setoran) => $setoran->juz !== null && $setoran->juz >= 1 && $setoran->juz <= 30);

        $byJuz = $ziyadah->groupBy('juz');
        $progressByJuz = [];

        foreach ($byJuz as $juz => $rows) {
            $total = $juzTotals[$juz] ?? ((int) $juz === 30 ? 23.0 : 20.0);
            $pages = min($total, $rows->sum(fn ($setoran) => $this->pages($setoran)));
            $percentage = $total > 0 ? min(100, round(($pages / $total) * 100, 2)) : 0;
            $completed = $pages >= $total;
            $existing = TahfidzJuzProgress::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('juz_number', $juz)
                ->first();

            $status = match (true) {
                $existing?->status === 'sudah_tasmi' => 'sudah_tasmi',
                $completed => 'selesai_ziyadah',
                $pages > 0 => 'sedang',
                default => 'belum',
            };

            $progress = TahfidzJuzProgress::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'juz_number' => (int) $juz,
                ],
                [
                    'status' => $status,
                    'halaman_completed' => $pages,
                    'total_halaman_juz' => $total,
                    'percentage' => $percentage,
                    'ziyadah_started_at' => $rows->min('setoran_date'),
                    'ziyadah_completed_at' => $completed ? $rows->max('setoran_date') : null,
                    'last_setoran_date' => $rows->max('setoran_date'),
                    'avg_nilai_setoran' => $rows->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
                ]
            );

            $progressByJuz[(int) $juz] = $progress;
        }

        $staleProgress = TahfidzJuzProgress::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId);

        if (empty($progressByJuz)) {
            $staleProgress->delete();
        } else {
            $staleProgress->whereNotIn('juz_number', array_keys($progressByJuz))->delete();
        }

        $semester = $this->resolveSemester($studentId, $academicYearId, $semester);
        $group = $groupId
            ?: $verified->sortByDesc('setoran_date')->first()?->tahfidz_group_id
            ?: TahfidzGroupMember::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'aktif')
                ->value('tahfidz_group_id');

        $targets = TahfidzStudentTarget::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get();

        $targetPages = (float) $targets->sum(fn ($target) => $target->target_halaman ?? 0);
        if ($targetPages <= 0) {
            $latest = $targets->sortByDesc('target_bulan')->first();
            if ($latest?->juz_start && $latest?->juz_end) {
                $targetPages = (float) (max(1, (int) $latest->juz_end - (int) $latest->juz_start + 1) * 20);
            }
        }

        $ziyadahPages = (float) $verified
            ->where('setoran_type', 'ziyadah')
            ->where('status', 'lulus')
            ->sum(fn ($setoran) => $this->pages($setoran));
        $murajaahPages = (float) $verified
            ->where('setoran_type', 'murajaah')
            ->sum(fn ($setoran) => $this->pages($setoran));

        $latest = $verified->sortByDesc('setoran_date')->first();
        $completedJuz = collect($progressByJuz)
            ->filter(fn ($progress) => in_array($progress->status, ['selesai_ziyadah', 'sudah_tasmi'], true))
            ->count();

        TahfidzProgressRecap::updateOrCreate(
            [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
            ],
            [
                'tahfidz_group_id' => $group,
                'total_juz_ziyadah' => round($ziyadahPages / 20, 1),
                'total_halaman_ziyadah' => round($ziyadahPages, 1),
                'total_juz_murajaah' => round($murajaahPages / 20, 1),
                'total_halaman_murajaah' => round($murajaahPages, 1),
                'total_setoran' => $verified->count(),
                'total_hari_setoran' => $verified->pluck('setoran_date')->filter()->map(fn ($date) => $date->toDateString())->unique()->count(),
                'rata_rata_nilai' => $verified->whereNotNull('nilai_setoran')->avg('nilai_setoran'),
                'pencapaian_target_persen' => $targetPages > 0 ? min(100, round(($ziyadahPages / $targetPages) * 100, 2)) : null,
                'last_position_juz' => $latest?->juz,
                'last_position_surah_id' => $latest?->surah_start_id,
                'last_position_ayat' => $latest?->ayat_start,
                'last_position_halaman' => $latest?->halaman_end ?: $latest?->halaman_start,
                'total_juz_completed' => $completedJuz,
                'hadits_count' => (int) $targets->sum('target_hadits'),
            ]
        );
    }

    private function pages(TahfidzSetoran $setoran): float
    {
        if ($setoran->jumlah_halaman !== null && (float) $setoran->jumlah_halaman > 0) {
            return (float) $setoran->jumlah_halaman;
        }

        if ($setoran->halaman_start !== null && $setoran->halaman_end !== null) {
            return max(0, (float) $setoran->halaman_end - (float) $setoran->halaman_start + 1);
        }

        return 0;
    }

    private function resolveSemester(string $studentId, string $academicYearId, ?string $semester): string
    {
        if (in_array($semester, ['ganjil', 'genap'], true)) {
            return $semester;
        }

        $fromTarget = TahfidzStudentTarget::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->orderByDesc('created_at')
            ->value('semester');

        if (in_array($fromTarget, ['ganjil', 'genap'], true)) {
            return $fromTarget;
        }

        return AcademicYear::whereKey($academicYearId)->value('semester') ?: 'ganjil';
    }
}
