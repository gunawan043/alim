<?php

namespace App\Services;

use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal sinkronisasi JP mata pelajaran.
 *
 * Peta Kurikulum (grade_level_subjects.allocation_hours) adalah sumber
 * kebenaran JP per tingkat. Setiap perubahan JP WAJIB mengalir ke bawah
 * agar semua modul membaca angka yang sama:
 *
 *   grade_level_subjects.allocation_hours   ← diatur (Peta Kurikulum / Mapel)
 *   → study_group_subjects.weekly_hours     (JP rombel)
 *   → teaching_assignments.weekly_hours     (JP pembagian tugas guru)
 *
 * Urutan pembacaan saat menampilkan JP tetap milik TeachingHoursResolver.
 * Kelas ini hanya mengurusi PENYEBARAN nilai yang sudah diputuskan.
 */
class SubjectHoursSynchronizer
{
    /**
     * Simpan JP untuk satu pasangan mapel × tingkat, lalu sebarkan.
     *
     * @return array{rombel: int, guru: int}
     */
    public function applyGradeLevelHours(
        string $subjectId,
        string $gradeLevelId,
        ?string $academicYearId,
        int $hours,
    ): array {
        return DB::transaction(function () use ($subjectId, $gradeLevelId, $academicYearId, $hours) {
            $row = GradeLevelSubject::query()
                ->where('subject_id', $subjectId)
                ->where('grade_level_id', $gradeLevelId)
                ->forAcademicYear($academicYearId)
                ->orderByAcademicYearFirst()
                ->first();

            if (! $row) {
                return ['rombel' => 0, 'guru' => 0];
            }

            $row->forceFill([
                'allocation_hours' => $hours,
                'is_active' => true,
            ])->save();

            return $this->propagate($row->academic_year_id, $subjectId, [$gradeLevelId], $hours);
        });
    }

    /**
     * Sebarkan JP satu tingkat ke rombel dan penugasan guru pada tingkat itu.
     *
     * Hanya rombel & penugasan pada TAHUN AJARAN baris tingkat tersebut yang
     * disentuh, sehingga Peta TA lama (arsip) tidak berubah saat TA aktif
     * diedit.
     *
     * @param  array<int, string>  $gradeLevelIds
     * @return array{rombel: int, guru: int}
     */
    public function propagate(?string $academicYearId, string $subjectId, array $gradeLevelIds, int $hours): array
    {
        if ($academicYearId === null || $gradeLevelIds === []) {
            return ['rombel' => 0, 'guru' => 0];
        }

        $groupQuery = StudyGroup::query()
            ->whereIn('grade_level_id', $gradeLevelIds)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true);

        $groupIds = $groupQuery->pluck('id');

        if ($groupIds->isEmpty()) {
            return ['rombel' => 0, 'guru' => 0];
        }

        $rombel = StudyGroupSubject::query()
            ->whereIn('study_group_id', $groupIds)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->update(['weekly_hours' => $hours]);

        // Penugasan guru ikut disamakan agar beban mengajar, jadwal, dan
        // pekan efektif tidak berbeda dari Peta Kurikulum.
        $guru = TeachingAssignment::query()
            ->whereIn('study_group_id', $groupIds)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->update(['weekly_hours' => $hours]);

        return ['rombel' => (int) $rombel, 'guru' => (int) $guru];
    }

    /**
     * Sebarkan JP satu mapel ke SELURUH tingkatnya sekaligus.
     *
     * Dipakai saat JP mapel diubah dari menu Mapel tetapi tingkatnya belum
     * punya alokasi sendiri: nilai bawaan hanya mengisi yang kosong.
     *
     * @return array{tingkat: int, rombel: int, guru: int}
     */
    public function fillEmptyGradeLevels(string $subjectId, ?string $academicYearId, int $hours): array
    {
        if ($academicYearId === null) {
            return ['tingkat' => 0, 'rombel' => 0, 'guru' => 0];
        }

        $rows = GradeLevelSubject::query()
            ->where('subject_id', $subjectId)
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->get()
            ->unique('grade_level_id')
            ->values();

        $kosong = $rows->filter(fn (GradeLevelSubject $r) => (int) $r->allocation_hours <= 0);

        if ($kosong->isEmpty()) {
            return ['tingkat' => 0, 'rombel' => 0, 'guru' => 0];
        }

        $rombel = 0;
        $guru = 0;

        foreach ($kosong as $row) {
            $hasil = $this->applyGradeLevelHours(
                $subjectId,
                $row->grade_level_id,
                $academicYearId,
                $hours,
            );
            $rombel += $hasil['rombel'];
            $guru += $hasil['guru'];
        }

        return ['tingkat' => $kosong->count(), 'rombel' => $rombel, 'guru' => $guru];
    }
}
