<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GradeLevelSubject;
use App\Models\RaporSubject;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu sumber aturan "mapel mana yang masuk rapor, dan dengan urutan apa".
 *
 * Aturan (disetujui): urutan, daftar mapel, dan KKTP ditetapkan SATU KALI per
 * TINGKAT di Peta Kurikulum. Rapor tidak boleh berbeda antar rombel pada
 * tingkat yang sama. Urutan cetak: blok Pondok lebih dulu, lalu blok Dinas,
 * masing-masing mengikuti nomor urut yang diatur di Peta Kurikulum.
 */
class RaporSubjectOrderService
{
    /**
     * Urutkan mapel untuk rapor satu tingkat.
     *
     * @param  Collection<int, Subject>  $subjects
     * @return Collection<int, Subject>
     */
    public function sortSubjects(Collection $subjects, ?string $gradeLevelId, ?string $academicYearId): Collection
    {
        if ($subjects->isEmpty()) {
            return $subjects;
        }

        $meta = $this->structureMeta($gradeLevelId, $academicYearId, $subjects->pluck('id')->all());

        return $subjects
            ->sortBy(fn (Subject $subject) => $this->sortKey($subject, $meta->get($subject->id)))
            ->values();
    }

    /**
     * ID mapel yang dicetak di rapor untuk satu tingkat pada TA + semester.
     *
     * Null berarti belum pernah diatur — pemanggil memakai seluruh mapel yang
     * tersedia. Array kosong berarti semua mapel sengaja dikeluarkan.
     *
     * @return array<int, string>|null
     */
    public function reportSubjectIds(?string $schoolId, ?string $academicYearId, ?string $semester, ?string $gradeLevelId): ?array
    {
        if (! $academicYearId || ! $semester || ! $gradeLevelId) {
            return null;
        }

        $rows = RaporSubject::query()
            ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
            ->get(['subject_id', 'sort_order']);

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->pluck('subject_id')->all();
    }

    /**
     * Simpan daftar mapel rapor satu tingkat pada TA + semester.
     *
     * @param  array<int, string>  $subjectIds
     */
    public function saveReportSubjects(?string $schoolId, ?string $academicYearId, ?string $semester, ?string $gradeLevelId, array $subjectIds): void
    {
        if (! $schoolId || ! $academicYearId || ! $semester || ! $gradeLevelId) {
            return;
        }

        $keep = array_values(array_unique(array_filter($subjectIds)));
        $now = now();

        // Baris lama dihapus lebih dulu: baris tanpa mapel ini yang tidak ikut
        // dipilih, sekaligus mencegah unik (tingkat, mapel) bentrok di SQLite.
        RaporSubject::query()
            ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('subject_id', $keep))
            ->delete();

        $existing = $keep === [] ? [] : RaporSubject::query()
            ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
            ->pluck('subject_id')
            ->all();

        $insert = [];
        foreach ($keep as $index => $subjectId) {
            if (in_array($subjectId, $existing, true)) {
                continue;
            }

            $insert[] = [
                'id' => (string) Str::uuid(),
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'grade_level_id' => $gradeLevelId,
                'semester' => $semester,
                'subject_id' => $subjectId,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($insert !== []) {
            DB::table('rapor_subjects')->insert($insert);
        }
    }

    /**
     * Metadata urutan + status pondok/dinas per mapel pada satu tingkat.
     *
     * Baris versi TA terpilih diprioritaskan; baris tanpa TA (lama) menjadi
     * cadangan sehingga data lama tetap terbaca.
     *
     * @param  array<int, string>  $subjectIds
     * @return Collection<string, GradeLevelSubject>
     */
    public function structureMeta(?string $gradeLevelId, ?string $academicYearId, array $subjectIds): Collection
    {
        if (! $gradeLevelId || $subjectIds === []) {
            return collect();
        }

        return GradeLevelSubject::query()
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('subject_id', $subjectIds)
            ->when($academicYearId, fn ($q) => $q->forAcademicYear($academicYearId))
            ->orderByAcademicYearFirst()
            ->get()
            ->unique('subject_id')
            ->keyBy('subject_id');
    }

    /**
     * Kunci pengurutan: blok (Pondok → Dinas → belum ditandai), nomor urut,
     * lalu nama mapel sebagai penstabil.
     *
     * @return array{0: int, 1: int, 2: string}
     */
    private function sortKey(Subject $subject, ?GradeLevelSubject $meta): array
    {
        return [
            GradeLevelSubject::sourceRank($meta?->subject_source),
            $meta?->sort_order ?? PHP_INT_MAX,
            mb_strtolower((string) $subject->name),
        ];
    }
}
