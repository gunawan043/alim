<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Collection;

/**
 * KKTP hanya boleh berbeda ANTAR TINGKAT, tidak antar rombel.
 *
 * Sumber tunggalnya subject_kktp dengan kunci (mapel × tingkat × TA × semester),
 * diisi dari Peta Kurikulum. Buku nilai menyimpan tautan kktp_id; tautan yang
 * menunjuk baris lain (mis. duplikat rombel lain) diperbaiki di sini agar rapor
 * dan leger membaca angka yang sama.
 */
class SubjectKktpService
{
    /** @var array<string, SubjectKktp|null> */
    private array $cache = [];

    /**
     * KKTP resmi satu mapel pada satu tingkat.
     */
    public function forGradeLevel(
        ?string $schoolId,
        string $subjectId,
        ?string $gradeLevelId,
        ?string $academicYearId,
        ?string $semester,
    ): ?SubjectKktp {
        if (! $gradeLevelId || ! $academicYearId || ! $semester) {
            return null;
        }

        $key = implode('|', [$schoolId, $subjectId, $gradeLevelId, $academicYearId, $semester]);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = SubjectKktp::query()
            ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('subject_id', $subjectId)
            ->where('grade_level_id', $gradeLevelId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();
    }

    /**
     * Semua KKTP satu tingkat, dikunci per subject_id.
     *
     * @return Collection<string, SubjectKktp>
     */
    public function mapForGradeLevel(
        ?string $schoolId,
        ?string $gradeLevelId,
        ?string $academicYearId,
        ?string $semester,
    ): Collection {
        if (! $gradeLevelId || ! $academicYearId || ! $semester) {
            return collect();
        }

        return SubjectKktp::query()
            ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('grade_level_id', $gradeLevelId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('subject_id');
    }

    /**
     * Simpan KKTP Peta Kurikulum, lalu selaraskan semua buku nilai di tingkat itu.
     *
     * @param  array<string, mixed>  $values  [subject_id => nilai]
     * @return array{saved: int, books: int}
     */
    public function saveForGradeLevel(
        ?string $schoolId,
        string $gradeLevelId,
        string $academicYearId,
        string $semester,
        array $values,
        ?string $userId = null,
    ): array {
        $saved = 0;
        $booksUpdated = 0;

        foreach ($values as $subjectId => $raw) {
            $score = $this->normalizeScore($raw);

            $existing = SubjectKktp::query()
                ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
                ->where('subject_id', $subjectId)
                ->where('grade_level_id', $gradeLevelId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester', $semester)
                ->first();

            if ($score === null) {
                $existing?->delete();
            } else {
                $kktp = SubjectKktp::updateOrCreate(
                    [
                        'subject_id' => $subjectId,
                        'grade_level_id' => $gradeLevelId,
                        'academic_year_id' => $academicYearId,
                        'semester' => $semester,
                    ],
                    [
                        'school_id' => $schoolId,
                        'kktp_score' => $score,
                        'kkm_score' => $score,
                        'created_by' => $existing?->created_by ?? $userId,
                    ]
                );

                $booksUpdated += $this->relinkBooks($schoolId, $gradeLevelId, $subjectId, $academicYearId, $semester, $kktp);
            }

            $saved++;
        }

        $this->cache = [];

        return ['saved' => $saved, 'books' => $booksUpdated];
    }

    /**
     * Arahkan seluruh buku nilai mapel × tingkat ke baris KKTP tingkat itu.
     * Buku yang belum punya KKTP tetap diberi tautan agar rapor langsung
     * membaca angka yang baru disimpan.
     */
    private function relinkBooks(
        ?string $schoolId,
        string $gradeLevelId,
        string $subjectId,
        string $academicYearId,
        string $semester,
        SubjectKktp $kktp,
    ): int {
        // Buku yang tertaut ke baris KKTP lain (rombel lain/tingkat lain).
        TeacherAdminBook::query()
            ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('kktp_id')->orWhere('kktp_id', '!=', $kktp->id))
            ->whereHas('studyGroup', fn ($q) => $q->where('grade_level_id', $gradeLevelId))
            ->update(['kktp_id' => $kktp->id]);

        return TeacherAdminBook::query()
            ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('kktp_id', $kktp->id)
            ->count();
    }

    private function normalizeScore(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $raw));

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return round(max(0, min(100, (float) $value)), 2);
    }

}
