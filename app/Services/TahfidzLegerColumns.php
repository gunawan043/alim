<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\TahfidzGradebookScore;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\TeacherAdminBook;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Kolom nilai tahfidz untuk leger (layar, PDF, Excel) dan halaman penilaian.
 *
 * Format leger tahfidz:
 *   NPT | Ziyadah | Rabt | Tikrar | STS | SAS
 *
 * - NPT (Nilai Pencapaian Target) = capaian halaman ziyadah ÷ target halaman
 *   semester × 100, maksimal 100 (sumber: tahfidz_progress_recaps). Bila guru
 *   mengisi NPT di buku halaqah, nilai guru yang dipakai.
 * - Ziyadah, Rabt, STS, SAS = nilai buku halaqah (diisi guru).
 * - Tikrar = nilai tikrar di buku halaqah; bila belum diisi, rata-rata nilai
 *   tikrar dari setoran.
 *
 * Semua kolom di sini hanya untuk mapel berformat tahfidz; mapel umum tetap
 * memakai komponen STS/SAS biasa.
 */
class TahfidzLegerColumns
{
    public const COLUMNS = [
        'npt' => 'NPT',
        'ziyadah' => 'Ziyadah',
        'rabt' => 'Rabt',
        'tikrar' => 'Tikrar',
        'sts' => 'STS',
        'sas' => 'SAS',
    ];

    private ?AcademicYear $academicYear = null;

    private string $semester = 'ganjil';

    /** @var array<string, array<int, array<string, mixed>>> student_id => [bulan => target] */
    private array $targetCache = [];

    /** @var array<string, TahfidzProgressRecap> */
    private array $recapCache = [];

    /** @var array<string, Collection<int, TahfidzSetoran>> */
    private array $setoranCache = [];

    /**
     * Nilai per santri per mapel tahfidz, siap dipakai view.
     *
     * @param  Collection<int, TeacherAdminBook>  $tahfidzBooks
     * @param  Collection<int, TahfidzGradebookScore>  $scores  hasil query TahfidzGradebookScore
     * @return array<string, array<string, array<string, float|null>>> [$studentId][$bookId][$kolom]
     */
    /**
     * Tetapkan periode sebelum memanggil npt()/tikrarAverage() satu per satu.
     */
    public function forPeriod(?AcademicYear $academicYear, string $semester): static
    {
        $this->academicYear = $academicYear;
        $this->semester = $semester;

        return $this;
    }

    public function valuesFor(
        Collection $tahfidzBooks,
        Collection $scores,
        Collection $studentIds,
        ?AcademicYear $academicYear,
        string $semester,
        bool $tikrarFromSetoran = true,
    ): array {
        $this->forPeriod($academicYear, $semester);

        $scoreMap = [];
        foreach ($scores as $score) {
            $scoreMap[$score->student_id][$score->gradebook?->subject_id] = $score;
        }

        $values = [];
        foreach ($studentIds as $studentId) {
            foreach ($tahfidzBooks as $book) {
                $score = $scoreMap[$studentId][$book->subject_id] ?? null;

                $values[$studentId][$book->id] = [
                    // NPT & Tikrar: nilai yang sudah disimpan guru di buku
                    // halaqah menang; bila kosong, dihitung dari target/setoran.
                    'npt' => $score?->npt !== null ? (float) $score->npt : $this->npt($studentId),
                    'ziyadah' => $score?->ziyadah !== null ? (float) $score->ziyadah : null,
                    'rabt' => $score?->rabt !== null ? (float) $score->rabt : null,
                    'tikrar' => $score?->tikrar !== null
                        ? (float) $score->tikrar
                        : ($tikrarFromSetoran
                            ? $this->tikrarAverage($studentId, $score?->gradebook?->tahfidz_group_id)
                            : null),
                    'sts' => $score?->sts !== null ? (float) $score->sts : null,
                    'sas' => $score?->sas !== null ? (float) $score->sas : null,
                ];
            }
        }

        return $values;
    }

    public function npt(string $studentId): ?float
    {
        if (! $this->academicYear) {
            return null;
        }

        $recap = $this->recap($studentId);
        $targetPages = $this->targetPages($studentId);

        if ($targetPages > 0) {
            // Sama dengan TahfidzProgressService: hanya ziyadah yang lulus yang
            // dihitung. Rekap bisa belum tersedia, jadi setoran dijumlah langsung.
            $ziyadahPages = $recap?->total_halaman_ziyadah !== null
                ? (float) $recap->total_halaman_ziyadah
                : $this->ziyadahPages($studentId);

            return round(min(100, ($ziyadahPages / $targetPages) * 100), 2);
        }

        return $recap?->pencapaian_target_persen !== null
            ? (float) $recap->pencapaian_target_persen
            : null;
    }

    /**
     * Halaman ziyadah yang lulus pada semester berjalan.
     */
    private function ziyadahPages(string $studentId): float
    {
        return (float) $this->setorans($studentId, null)
            ->where('setoran_type', 'ziyadah')
            ->where('status', 'lulus')
            ->sum(fn ($setoran) => $this->pages($setoran));
    }

    /**
     * Halaman sebuah setoran — aturannya sama dengan TahfidzProgressService.
     */
    private function pages(TahfidzSetoran $setoran): float
    {
        if ($setoran->jumlah_halaman !== null && (float) $setoran->jumlah_halaman > 0) {
            return (float) $setoran->jumlah_halaman;
        }

        if ($setoran->halaman_start !== null && $setoran->halaman_end !== null) {
            return max(0, (float) $setoran->halaman_end - (float) $setoran->halaman_start + 1);
        }

        return 0.0;
    }

    public function tikrarAverage(string $studentId, ?string $groupId = null): ?float
    {
        if (! $this->academicYear) {
            return null;
        }

        $avg = $this->setorans($studentId, $groupId)
            ->where('setoran_type', 'tikror')
            ->whereNotNull('nilai_tikrar')
            ->avg('nilai_tikrar');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    /**
     * Target halaman semester milik santri — logikanya sama dengan
     * TahfidzProgressService: jumlah target bulanan, lalu jatuh ke rentang juz.
     */
    private function targetPages(string $studentId): float
    {
        $targets = collect($this->targets($studentId));

        $pages = (float) $targets->sum(fn ($target) => $target['target_halaman'] ?? 0);
        if ($pages > 0) {
            return $pages;
        }

        $latest = $targets->sortByDesc('target_bulan')->first();
        if ($latest && $latest['juz_start'] && $latest['juz_end']) {
            return (float) (max(1, (int) $latest['juz_end'] - (int) $latest['juz_start'] + 1) * 20);
        }

        return 0.0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function targets(string $studentId): array
    {
        $semester = $this->semesterKey();

        if (isset($this->targetCache[$studentId][$semester])) {
            return $this->targetCache[$studentId][$semester];
        }

        return $this->targetCache[$studentId][$semester] = TahfidzStudentTarget::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $this->academicYear->id)
            ->where('semester', $semester)
            ->get(['id', 'target_bulan', 'target_halaman', 'juz_start', 'juz_end'])
            ->toArray();
    }

    private function recap(string $studentId): ?TahfidzProgressRecap
    {
        if (array_key_exists($studentId, $this->recapCache)) {
            return $this->recapCache[$studentId];
        }

        return $this->recapCache[$studentId] = TahfidzProgressRecap::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $this->academicYear->id)
            ->where('semester', $this->semesterKey())
            ->first();
    }

    /**
     * Setoran semester berjalan. Tanpa grup, diambil semua setoran santri di
     * semester ini — sama seperti rekap buku halaqah yang tidak mengikat grup.
     *
     * @return Collection<int, TahfidzSetoran>
     */
    private function setorans(string $studentId, ?string $groupId): Collection
    {
        $key = $studentId.'|'.$groupId;

        if (isset($this->setoranCache[$key])) {
            return $this->setoranCache[$key];
        }

        [$start, $end] = $this->semesterRange();

        return $this->setoranCache[$key] = TahfidzSetoran::query()
            ->where('student_id', $studentId)
            ->when($groupId, fn ($q) => $q->where('tahfidz_group_id', $groupId))
            ->whereBetween('setoran_date', [$start->toDateString(), $end->toDateString()])
            ->get();
    }

    private function semesterKey(): string
    {
        return in_array($this->semester, ['ganjil', 'genap'], true) ? $this->semester : 'ganjil';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function semesterRange(): array
    {
        $start = $this->academicYear?->start_date
            ? Carbon::parse($this->academicYear->start_date)->startOfMonth()
            : now()->startOfYear();
        $end = $this->academicYear?->end_date
            ? Carbon::parse($this->academicYear->end_date)->endOfMonth()
            : now()->endOfYear();

        if ($this->semesterKey() === 'ganjil') {
            return [$start, $start->copy()->addMonths(5)->endOfMonth()->min($end)];
        }

        return [$start->copy()->addMonths(6)->startOfMonth(), $end];
    }
}
