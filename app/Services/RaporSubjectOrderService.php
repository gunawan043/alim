<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\RaporSubject;
use App\Models\Subject;
use App\Services\Academic\RombelSubjectProvisioner;
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
    public function sortSubjects(Collection $subjects, ?string $gradeLevelId, ?string $academicYearId, ?string $peminatan = null): Collection
    {
        if ($subjects->isEmpty()) {
            return $subjects;
        }

        $meta = $this->structureMeta($gradeLevelId, $academicYearId, $subjects->pluck('id')->all(), $peminatan);

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
    public function reportSubjectIds(
        ?string $schoolId,
        ?string $academicYearId,
        ?string $semester,
        ?string $gradeLevelId,
        ?string $studyGroupId = null,
    ): ?array {
        if (! $academicYearId || ! $semester || ! $gradeLevelId) {
            return null;
        }

        // Bila rombel punya daftar sendiri (Kelas 11 & 12 dengan penjurusan),
        // pakai itu; selainnya berlaku pengaturan tingkat.
        $useGroup = $studyGroupId && $this->perStudyGroup($gradeLevelId);

        $rows = $useGroup
            ? RaporSubject::query()
                ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
                ->where('study_group_id', $studyGroupId)
                ->get(['subject_id', 'sort_order'])
            : collect();

        if ($rows->isEmpty()) {
            $rows = RaporSubject::query()
                ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
                ->whereNull('study_group_id')
                ->get(['subject_id', 'sort_order']);
        }

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->pluck('subject_id')->all();
    }

    /**
     * Apakah daftar mapel rapor tingkat ini disimpan per rombel.
     */
    public function perStudyGroup(?string $gradeLevelId): bool
    {
        if (! $gradeLevelId) {
            return false;
        }

        $level = GradeLevel::query()->whereKey($gradeLevelId)->value('level');

        return $level !== null && (new GradeLevel(['level' => $level]))->raporSubjectsPerStudyGroup();
    }

    /**
     * Simpan daftar mapel rapor satu tingkat pada TA + semester.
     *
     * @param  array<int, string>  $subjectIds
     */
    public function saveReportSubjects(
        ?string $schoolId,
        ?string $academicYearId,
        ?string $semester,
        ?string $gradeLevelId,
        array $subjectIds,
        ?string $studyGroupId = null,
    ): void {
        if (! $schoolId || ! $academicYearId || ! $semester || ! $gradeLevelId) {
            return;
        }

        $keep = array_values(array_unique(array_filter($subjectIds)));
        $now = now();

        // Hanya Kelas 11 & 12 yang menyimpan daftar per rombel. Tingkat lain
        // tetap satu daftar untuk seluruh rombel di tingkat itu.
        $studyGroupId = $studyGroupId && $this->perStudyGroup($gradeLevelId) ? $studyGroupId : null;

        $scope = fn () => RaporSubject::query()
            ->forScope($schoolId, $academicYearId, $semester, $gradeLevelId)
            ->when($studyGroupId, fn ($q) => $q->where('study_group_id', $studyGroupId), fn ($q) => $q->whereNull('study_group_id'));

        // Baris lama dihapus lebih dulu: baris tanpa mapel ini yang tidak ikut
        // dipilih, sekaligus mencegah unik (tingkat/rombel, mapel) bentrok.
        $scope()->when($keep !== [], fn ($q) => $q->whereNotIn('subject_id', $keep))->delete();

        $existing = $keep === [] ? [] : $scope()->pluck('subject_id')->all();

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
                'study_group_id' => $studyGroupId,
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
     * Bobot rapor SAS per mapel untuk satu tingkat.
     *
     * Bobot adalah properti TINGKAT: leger, buku nilai, dan rapor membaca angka
     * yang sama untuk semua rombel. Belum diatur → JP ÷ 2 (JP 1 tetap 1).
     *
     * @return array<string, float> [subject_id => bobot efektif]
     */
    public function bobotForGradeLevel(?string $gradeLevelId, ?string $academicYearId, array $subjectIds): array
    {
        if (! $gradeLevelId || $subjectIds === []) {
            return [];
        }

        return $this->structureMeta($gradeLevelId, $academicYearId, $subjectIds)
            ->map(fn (GradeLevelSubject $meta) => $meta->effectiveBobot())
            ->all();
    }

    /**
     * Simpan bobot rapor SAS satu tingkat. Kosong = kembali ke JP ÷ 2.
     *
     * @param  array<string, mixed>  $values  [subject_id => bobot]
     */
    public function saveBobotGradeLevel(string $gradeLevelId, ?string $academicYearId, array $values): int
    {
        $structures = $this->structureMeta($gradeLevelId, $academicYearId, array_keys($values));
        $diubah = 0;

        foreach ($values as $subjectId => $raw) {
            $structure = $structures->get($subjectId);

            if (! $structure) {
                continue;
            }

            $raw = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
            $bobot = ($raw === null || $raw === '' || ! is_numeric($raw))
                ? null
                : round(max(0, (float) $raw), 2);

            $structure->bobot = $bobot;
            $structure->save();
            $diubah++;
        }

        return $diubah;
    }

    /**
     * Metadata urutan + status pondok/dinas per mapel pada satu tingkat.
     *
     * Baris versi TA terpilih diprioritaskan; baris tanpa TA (lama) menjadi
     * cadangan sehingga data lama tetap terbaca. Bila rombel berjurusan dan
     * mapel punya baris khusus jurusan itu, baris tersebut yang dipakai
     * agar urutan/sumber rapor rombel mengikuti penyusunan untuk jurusannya.
     *
     * @param  array<int, string>  $subjectIds
     * @return Collection<string, GradeLevelSubject>
     */
    public function structureMeta(?string $gradeLevelId, ?string $academicYearId, array $subjectIds, ?string $peminatan = null): Collection
    {
        if (! $gradeLevelId || $subjectIds === []) {
            return collect();
        }

        $peminatan = strtolower(trim((string) $peminatan));

        return GradeLevelSubject::query()
            ->where('grade_level_id', $gradeLevelId)
            ->whereIn('subject_id', $subjectIds)
            ->when($academicYearId, fn ($q) => $q->forAcademicYear($academicYearId))
            ->orderByAcademicYearFirst()
            ->get()
            ->sortBy(fn (GradeLevelSubject $meta) => [
                // Jurusan rombel didahulukan, lalu mapel umum, terakhir
                // baris jurusan lain — yang hanya jadi cadangan tampilan.
                ! RombelSubjectProvisioner::isTrackUmum($meta->track)
                    && strtolower(trim((string) $meta->track)) === $peminatan ? 0 : 1,
            ])
            ->unique('subject_id')
            ->keyBy('subject_id');
    }

    /**
     * Kunci pengurutan: blok (Pondok → Dinas → belum ditandai), nomor urut,
     * lalu nama mapel sebagai penstabil.
     */
    public function sortKey(Subject $subject, ?GradeLevelSubject $meta): string
    {
        // Kunci berupa STRING, bukan array: perbandingan array pada uasort
        // membandingkan jumlah elemen lebih dulu dan berhenti di elemen yang
        // belum tentu sama panjang, sehingga urutan bisa salah.
        return sprintf(
            '%d|%06d|%s',
            GradeLevelSubject::sourceRank($meta?->subject_source),
            $meta?->sort_order ?? 999999,
            mb_strtolower((string) $subject->name),
        );
    }
}
