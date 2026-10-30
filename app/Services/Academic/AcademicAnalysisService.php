<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Analisis nilai murni STS/SAS lintas siswa, kelas, mapel, dan periode.
 *
 * Sumber nilai: admin_nilai_sumatif (sts_murni / sas_murni) melalui buku
 * administrasi guru. Nilai yang belum diisi tetap ditampilkan sebagai data
 * tidak lengkap agar dapat ditindaklanjuti.
 */
class AcademicAnalysisService
{
    public const DECLINE_THRESHOLD = 5;

    /**
     * @param  array{academic_year_id:string,semester:string,grade_level_id?:?string,study_group_id?:?string,subject_id?:?string,q?:?string,compare_mode?:?string}  $filters
     * @return array<string, mixed>
     */
    public function analyze(?string $schoolId, array $filters): array
    {
        $academicYear = AcademicYear::find($filters['academic_year_id']);
        $semester = $filters['semester'] === 'genap' ? 'genap' : 'ganjil';
        $compareMode = $filters['compare_mode'] === 'previous_year' ? 'previous_year' : 'previous_period';

        if (! $academicYear) {
            return $this->emptyResult($semester);
        }

        $currentRows = $this->rowsForPeriod($schoolId, $academicYear, $semester, $filters);
        [$previousYear, $previousSemester] = $this->resolvePreviousPeriod($academicYear, $semester, $compareMode);

        $previousRows = $previousYear
            ? $this->rowsForPeriod($schoolId, $previousYear, $previousSemester, array_merge($filters, [
                'study_group_id' => null,
            ]))
            : collect();

        $previousMap = $previousRows->keyBy(fn (array $row) => $row['student_id'].'|'.$row['subject_id']);

        $rows = $currentRows->map(function (array $row) use ($previousMap) {
            $previous = $previousMap->get($row['student_id'].'|'.$row['subject_id']);
            $previousSts = $previous['sts'] ?? null;
            $previousSas = $previous['sas'] ?? null;
            $previousAvg = $this->average($previousSts, $previousSas);

            $row['previous_sts'] = $previousSts;
            $row['previous_sas'] = $previousSas;
            $row['previous_avg'] = $previousAvg;
            $row['previous_delta'] = $this->delta($previousSts, $previousSas);
            $row['period_delta'] = ($row['average'] !== null && $previousAvg !== null)
                ? round($row['average'] - $previousAvg, 2)
                : null;
            $row['period_trend'] = $this->trend($row['period_delta']);

            return $row;
        })->values();

        $summary = $this->summarize($rows);
        $previousSummary = $this->summarize($previousRows);

        return [
            'academic_year' => $academicYear,
            'semester' => $semester,
            'previous_year' => $previousYear,
            'previous_semester' => $previousSemester,
            'compare_mode' => $compareMode,
            'current_label' => $this->periodLabel($academicYear, $semester),
            'previous_label' => $previousYear ? $this->periodLabel($previousYear, $previousSemester) : null,
            'rows' => $rows,
            'summary' => $summary,
            'previous_summary' => $previousSummary,
            'comparison' => $this->buildComparison($summary, $previousSummary),
            'class_stats' => $this->classStats($rows),
            'subject_stats' => $this->subjectStats($rows),
            'findings' => $this->findings($rows, $summary),
            'row_count' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsForPeriod(?string $schoolId, AcademicYear $academicYear, string $semester, array $filters): Collection
    {
        $books = TeacherAdminBook::query()
            ->with(['subject', 'studyGroup.gradeLevel', 'teacher', 'kktp'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->when($filters['grade_level_id'] ?? null, fn ($q, $gradeLevelId) => $q->whereHas('studyGroup', fn ($group) => $group->where('grade_level_id', $gradeLevelId)))
            ->when($filters['study_group_id'] ?? null, fn ($q, $studyGroupId) => $q->where('study_group_id', $studyGroupId))
            ->when($filters['subject_id'] ?? null, fn ($q, $subjectId) => $q->where('subject_id', $subjectId))
            ->get();

        if ($books->isEmpty()) {
            return collect();
        }

        $values = NilaiSumatif::query()
            ->whereIn('admin_book_id', $books->pluck('id'))
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy(fn (NilaiSumatif $value) => $value->student_id.'|'.$value->admin_book_id);

        $histories = StudentClassHistory::query()
            ->with('student')
            ->whereIn('study_group_id', $books->pluck('study_group_id')->unique())
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->get()
            ->groupBy('study_group_id');

        $search = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $rows = collect();

        foreach ($books as $book) {
            foreach ($histories->get($book->study_group_id, collect()) as $history) {
                $student = $history->student;
                if (! $student || ($search !== '' && ! str_contains(mb_strtolower($student->name), $search))) {
                    continue;
                }

                $value = $values->get($student->id.'|'.$book->id);
                $sts = $value?->sts_murni !== null ? round((float) $value->sts_murni, 2) : null;
                $sas = $value?->sas_murni !== null ? round((float) $value->sas_murni, 2) : null;
                $kktp = $book->kktp?->kkm_score !== null ? (float) $book->kktp->kkm_score : null;

                $rows->push([
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'nisn' => $student->nisn,
                    'nis' => $student->nis,
                    'class_id' => $book->study_group_id,
                    'class_name' => $book->studyGroup?->name ?? '-',
                    'grade_level' => $book->studyGroup?->gradeLevel?->name ?? '-',
                    'subject_id' => $book->subject_id,
                    'subject_name' => $book->subject?->name ?? '-',
                    'subject_code' => $book->subject?->code ?? '-',
                    'teacher_name' => $book->teacher?->name ?? '-',
                    'book_id' => $book->id,
                    'sts' => $sts,
                    'sas' => $sas,
                    'average' => $this->average($sts, $sas),
                    'delta' => $this->delta($sts, $sas),
                    'trend' => $this->trend($this->delta($sts, $sas)),
                    'kktp' => $kktp,
                    'below_kktp' => $kktp !== null && $sas !== null && $sas < $kktp,
                    'missing' => $sts === null || $sas === null,
                ]);
            }
        }

        return $rows->values();
    }

    private function resolvePreviousPeriod(AcademicYear $current, string $semester, string $mode): array
    {
        if ($mode === 'previous_period' && $semester === 'genap') {
            return [$current, 'ganjil'];
        }

        $query = AcademicYear::query()
            ->where('id', '!=', $current->id);

        if ($current->start_date) {
            $query->whereDate('start_date', '<', $current->start_date);
        } else {
            $query->where('name', '<', $current->name);
        }

        $previous = $query->orderByDesc('start_date')->orderByDesc('name')->first();

        if (! $previous) {
            return [null, null];
        }

        return [$previous, $mode === 'previous_period' ? 'genap' : $semester];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function summarize(Collection $rows): array
    {
        $stsValues = $rows->pluck('sts')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();
        $sasValues = $rows->pluck('sas')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values();

        $avgSts = $stsValues->isEmpty() ? null : round($stsValues->avg(), 2);
        $avgSas = $sasValues->isEmpty() ? null : round($sasValues->avg(), 2);

        return [
            'total_rows' => $rows->count(),
            'students' => $rows->pluck('student_id')->unique()->count(),
            'subjects' => $rows->pluck('subject_id')->unique()->count(),
            'classes' => $rows->pluck('class_id')->unique()->count(),
            'avg_sts' => $avgSts,
            'avg_sas' => $avgSas,
            'avg_delta' => ($avgSts !== null && $avgSas !== null) ? round($avgSas - $avgSts, 2) : null,
            'median_sts' => $this->median($stsValues),
            'median_sas' => $this->median($sasValues),
            'max_sts' => $stsValues->max(),
            'min_sts' => $stsValues->min(),
            'max_sas' => $sasValues->max(),
            'min_sas' => $sasValues->min(),
            'increased' => $rows->where('trend', 'increased')->count(),
            'decreased' => $rows->where('trend', 'decreased')->count(),
            'stable' => $rows->where('trend', 'stable')->count(),
            'missing' => $rows->where('missing', true)->count(),
            'complete' => $rows->count() - $rows->where('missing', true)->count(),
            'below_kktp' => $rows->where('below_kktp', true)->count(),
            'with_kktp' => $rows->filter(fn (array $row) => $row['kktp'] !== null && $row['sas'] !== null)->count(),
            'distribution' => [
                'sts' => $this->distribution($stsValues),
                'sas' => $this->distribution($sasValues),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildComparison(array $current, array $previous): array
    {
        $delta = function (?float $currentValue, ?float $previousValue): ?float {
            return $currentValue !== null && $previousValue !== null
                ? round($currentValue - $previousValue, 2)
                : null;
        };

        $percent = function (?float $difference, ?float $previousValue): ?float {
            return $difference !== null && $previousValue !== null && $previousValue != 0.0
                ? round(($difference / $previousValue) * 100, 2)
                : null;
        };

        $deltaSts = $delta($current['avg_sts'], $previous['avg_sts']);
        $deltaSas = $delta($current['avg_sas'], $previous['avg_sas']);

        return [
            'delta_avg_sts' => $deltaSts,
            'delta_avg_sas' => $deltaSas,
            'percent_avg_sts' => $percent($deltaSts, $previous['avg_sts']),
            'percent_avg_sas' => $percent($deltaSas, $previous['avg_sas']),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function classStats(Collection $rows): Collection
    {
        return $rows->groupBy('class_id')->map(function (Collection $group, string $classId) {
            $summary = $this->summarize($group);

            return [
                'id' => $classId,
                'name' => $group->first()['class_name'] ?? $classId,
                'avg_sts' => $summary['avg_sts'],
                'avg_sas' => $summary['avg_sas'],
                'avg_delta' => $summary['avg_delta'],
                'students' => $summary['students'],
                'total' => $summary['total_rows'],
                'complete' => $summary['complete'],
                'below_kktp' => $summary['below_kktp'],
                'with_kktp' => $summary['with_kktp'],
                'decreased' => $summary['decreased'],
                'missing' => $summary['missing'],
            ];
        })->sortBy('name')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function subjectStats(Collection $rows): Collection
    {
        return $rows->groupBy('subject_id')->map(function (Collection $group, string $subjectId) {
            $summary = $this->summarize($group);

            return [
                'id' => $subjectId,
                'name' => $group->first()['subject_name'] ?? $subjectId,
                'code' => $group->first()['subject_code'] ?? '-',
                'avg_sts' => $summary['avg_sts'],
                'avg_sas' => $summary['avg_sas'],
                'avg_delta' => $summary['avg_delta'],
                'total' => $summary['total_rows'],
                'complete' => $summary['complete'],
                'below_kktp' => $summary['below_kktp'],
                'with_kktp' => $summary['with_kktp'],
                'decreased' => $summary['decreased'],
                'missing' => $summary['missing'],
            ];
        })->sortBy('name')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function findings(Collection $rows, array $summary): array
    {
        $decline = $rows
            ->filter(fn (array $row) => $row['delta'] !== null && $row['delta'] <= -self::DECLINE_THRESHOLD)
            ->sortBy('delta')
            ->take(20)
            ->values();

        $missing = $rows->where('missing', true)->take(20)->values();
        $belowKktp = $rows->where('below_kktp', true)->sortBy('sas')->take(20)->values();

        $findings = [];

        if ($decline->isNotEmpty()) {
            $findings[] = [
                'key' => 'decline',
                'title' => 'Nilai SAS menurun ≥ '.self::DECLINE_THRESHOLD.' poin',
                'count' => $decline->count(),
                'description' => 'Bandingkan STS dan SAS, lalu cek kembali proses belajar santri.',
                'rows' => $decline,
            ];
        }

        if ($summary['missing'] > 0) {
            $findings[] = [
                'key' => 'missing',
                'title' => 'Nilai STS/SAS belum lengkap',
                'count' => $summary['missing'],
                'description' => 'Nilai kosong dapat membuat analisis dan rapor tidak lengkap.',
                'rows' => $missing,
            ];
        }

        if ($belowKktp->isNotEmpty()) {
            $findings[] = [
                'key' => 'below_kktp',
                'title' => 'Nilai SAS di bawah KKTP',
                'count' => $summary['below_kktp'],
                'description' => 'Perlu perhatian, bukan kesimpulan otomatis tentang penyebab nilai.',
                'rows' => $belowKktp,
            ];
        }

        return $findings;
    }

    private function median(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return round((float) $sorted[$middle], 2);
        }

        return round(((float) $sorted[$middle - 1] + (float) $sorted[$middle]) / 2, 2);
    }

    /**
     * @return array<int, array{label:string,count:int}>
     */
    private function distribution(Collection $values): array
    {
        $buckets = [
            '0–59' => 0,
            '60–69' => 0,
            '70–79' => 0,
            '80–89' => 0,
            '90–100' => 0,
        ];

        foreach ($values as $value) {
            if ($value < 60) {
                $buckets['0–59']++;
            } elseif ($value < 70) {
                $buckets['60–69']++;
            } elseif ($value < 80) {
                $buckets['70–79']++;
            } elseif ($value < 90) {
                $buckets['80–89']++;
            } else {
                $buckets['90–100']++;
            }
        }

        return collect($buckets)->map(fn (int $count, string $label) => [
            'label' => $label,
            'count' => $count,
        ])->values()->all();
    }

    private function average(?float $sts, ?float $sas): ?float
    {
        if ($sts === null || $sas === null) {
            return null;
        }

        return round(($sts + $sas) / 2, 2);
    }

    private function delta(?float $sts, ?float $sas): ?float
    {
        return $sts !== null && $sas !== null ? round($sas - $sts, 2) : null;
    }

    private function trend(?float $delta): string
    {
        if ($delta === null) {
            return 'missing';
        }

        return $delta > 0.005 ? 'increased' : ($delta < -0.005 ? 'decreased' : 'stable');
    }

    private function periodLabel(AcademicYear $year, string $semester): string
    {
        return $year->name.' — '.Str::ucfirst($semester);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(string $semester): array
    {
        return [
            'academic_year' => null,
            'semester' => $semester,
            'previous_year' => null,
            'previous_semester' => null,
            'compare_mode' => 'previous_period',
            'current_label' => '-',
            'previous_label' => null,
            'rows' => collect(),
            'summary' => $this->summarize(collect()),
            'previous_summary' => $this->summarize(collect()),
            'comparison' => [],
            'class_stats' => collect(),
            'subject_stats' => collect(),
            'findings' => [],
            'row_count' => 0,
        ];
    }
}
