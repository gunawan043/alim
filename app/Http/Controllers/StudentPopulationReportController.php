<?php

namespace App\Http\Controllers;

use App\Exports\AcademicOperationsExport;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Services\Academic\StudentPopulationService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan mutasi, populasi, keberlanjutan, dan kelulusan santri.
 */
class StudentPopulationReportController extends Controller
{
    public function mutations(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $service->mutations($this->schoolIds($request), $filters);
        $rows = $this->paginate($report['rows'], $request, 50);

        return view('user.laporan.populasi-mutasi', array_merge(
            compact('userId', 'filters', 'academicYear', 'report', 'rows'),
            $this->options($request, $academicYear),
        ));
    }

    public function growth(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $service->growth($this->schoolIds($request), $filters);

        return view('user.laporan.populasi-pertumbuhan', array_merge(
            compact('userId', 'filters', 'academicYear', 'report'),
            $this->options($request, $academicYear),
        ));
    }

    public function profile(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $service->profile($this->schoolIds($request), $filters);

        return view('user.laporan.populasi-profil', array_merge(
            compact('userId', 'filters', 'academicYear', 'report'),
            $this->options($request, $academicYear),
        ));
    }

    public function continuity(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $service->continuity($this->schoolIds($request), $filters);
        $rows = $this->paginate($report['journey'], $request, 50);

        return view('user.laporan.populasi-keberlanjutan', array_merge(
            compact('userId', 'filters', 'academicYear', 'report', 'rows'),
            $this->options($request, $academicYear),
        ));
    }

    public function promotions(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $service->promotions($this->schoolIds($request), $filters);
        $rows = $this->paginate($report['details'], $request, 50);

        return view('user.laporan.populasi-kenaikan-kelulusan', array_merge(
            compact('userId', 'filters', 'academicYear', 'report', 'rows'),
            $this->options($request, $academicYear),
        ));
    }

    public function mutationsExport(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->mutations($this->schoolIds($request), $filters);

        return $this->download(
            $request,
            'Laporan Mutasi Santri',
            $this->periodText($filters),
            $this->mutationSummary($report['summary']),
            ['No', 'Tanggal', 'Jenis', 'Nama Santri', 'NISN', 'Unit', 'Jenjang', 'Kelas', 'Dari', 'Ke', 'Alasan', 'Sumber'],
            $report['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['date'] ? Carbon::parse($row['date'])->format('d/m/Y') : '-',
                $row['type_label'],
                $row['student_name'] ?: '-',
                $row['nisn'] ?: '-',
                $row['school_name'] ?: '-',
                $row['grade_name'] ?: '-',
                $row['class_name'] ?: '-',
                $row['from_name'] ?: '-',
                $row['to_name'] ?: '-',
                $row['reason'] ?: '-',
                $row['source'],
            ])->all(),
            'Laporan-Mutasi-Santri-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function mutationsPdf(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->mutations($this->schoolIds($request), $filters);

        return $this->pdf(
            $request,
            'Laporan Mutasi Santri',
            $this->periodText($filters),
            $this->mutationSummary($report['summary']),
            ['No', 'Tanggal', 'Jenis', 'Nama Santri', 'Unit', 'Jenjang/Kelas', 'Dari → Ke', 'Sumber'],
            $report['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['date'] ? Carbon::parse($row['date'])->format('d/m/Y') : '-',
                $row['type_label'],
                $row['student_name'] ?: '-',
                $row['school_name'] ?: '-',
                trim(($row['grade_name'] ?: '-').' / '.($row['class_name'] ?: '-'), ' /'),
                ($row['from_name'] ?: '-').' → '.($row['to_name'] ?: '-'),
                $row['source'],
            ])->all(),
            'Laporan-Mutasi-Santri-'.now()->format('Ymd-His'),
            $report['source_note'],
        );
    }

    public function growthExport(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->growth($this->schoolIds($request), $filters);

        return $this->download(
            $request,
            'Laporan Pertumbuhan Populasi Santri',
            $this->periodText($filters),
            $this->growthSummary($report['summary']),
            ['Tahun Ajaran', 'Awal', 'Santri Baru', 'Mutasi Masuk', 'Mutasi Keluar', 'Berhenti', 'Lulusan', 'Akhir', 'Pertumbuhan Bersih', 'Pertumbuhan (%)'],
            collect($report['series'])->map(fn (array $row) => [
                $row['name'],
                $row['opening'],
                $row['new'],
                $row['mutation_in'],
                $row['mutation_out'],
                $row['dropout'],
                $row['graduates'],
                $row['closing'],
                $row['net_growth'],
                $row['growth_rate'] !== null ? number_format($row['growth_rate'], 2).'%' : '-',
            ])->all(),
            'Laporan-Pertumbuhan-Populasi-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function growthPdf(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->growth($this->schoolIds($request), $filters);

        return $this->pdf(
            $request,
            'Laporan Pertumbuhan Populasi Santri',
            $this->periodText($filters),
            $this->growthSummary($report['summary']),
            ['Tahun Ajaran', 'Awal', 'Baru', 'Masuk', 'Keluar', 'Berhenti', 'Lulus', 'Akhir', 'Net', '%'],
            collect($report['series'])->map(fn (array $row) => [
                $row['name'], $row['opening'], $row['new'], $row['mutation_in'], $row['mutation_out'],
                $row['dropout'], $row['graduates'], $row['closing'], $row['net_growth'],
                $row['growth_rate'] !== null ? number_format($row['growth_rate'], 2).'%' : '-',
            ])->all(),
            'Laporan-Pertumbuhan-Populasi-'.now()->format('Ymd-His'),
            $report['source_note'],
        );
    }

    public function profileExport(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->profile($this->schoolIds($request), $filters);

        return $this->download(
            $request,
            'Laporan Profil dan Sebaran Santri',
            $this->periodText($filters),
            $this->profileSummary($report['summary']),
            ['Rombel', 'Unit', 'Jenjang', 'Jumlah Santri', 'Kapasitas', 'Keterisian (%)'],
            $report['by_class']->map(fn (array $row) => [
                $row['name'], $row['school_name'], $row['grade_name'], $row['total'],
                $row['capacity'] ?: '-', $row['fill_rate'] !== null ? number_format($row['fill_rate'], 2).'%' : '-',
            ])->all(),
            'Laporan-Profil-Santri-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function profilePdf(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->profile($this->schoolIds($request), $filters);

        return $this->pdf(
            $request,
            'Laporan Profil dan Sebaran Santri',
            $this->periodText($filters),
            $this->profileSummary($report['summary']),
            ['Rombel', 'Unit', 'Jenjang', 'Santri', 'Kapasitas', 'Keterisian'],
            $report['by_class']->map(fn (array $row) => [
                $row['name'], $row['school_name'], $row['grade_name'], $row['total'],
                $row['capacity'] ?: '-',
                $row['fill_rate'] !== null ? number_format($row['fill_rate'], 2).'%' : '-',
            ])->all(),
            'Laporan-Profil-Santri-'.now()->format('Ymd-His'),
            $report['source_note'],
        );
    }

    public function continuityExport(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->continuity($this->schoolIds($request), $filters);

        return $this->download(
            $request,
            'Laporan Keberlanjutan Pendidikan dan Retensi',
            $this->periodText($filters),
            $this->continuitySummary($report['summary']),
            ['No', 'NISN', 'Nama Santri', 'Tahun Masuk', 'Kelas Awal', 'Tahun Terakhir', 'Kelas Akhir', 'Melanjutkan', 'Status'],
            $report['journey']->values()->map(fn (array $row, int $index) => [
                $index + 1, $row['nisn'] ?: '-', $row['student_name'] ?: '-', $row['first_year'] ?: '-',
                $row['first_class'] ?: '-', $row['last_year'] ?: '-', $row['last_class'] ?: '-',
                $row['continued'] ? 'Ya' : 'Tidak tercatat', $row['status'] ?: '-',
            ])->all(),
            'Laporan-Keberlanjutan-Santri-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function continuityPdf(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->continuity($this->schoolIds($request), $filters);

        return $this->pdf(
            $request,
            'Laporan Keberlanjutan Pendidikan dan Retensi',
            $this->periodText($filters),
            $this->continuitySummary($report['summary']),
            ['No', 'Nama Santri', 'Tahun Masuk', 'Kelas Awal', 'Tahun Terakhir', 'Kelas Akhir', 'Melanjutkan'],
            $report['journey']->values()->map(fn (array $row, int $index) => [
                $index + 1, $row['student_name'] ?: '-', $row['first_year'] ?: '-', $row['first_class'] ?: '-',
                $row['last_year'] ?: '-', $row['last_class'] ?: '-', $row['continued'] ? 'Ya' : 'Tidak tercatat',
            ])->all(),
            'Laporan-Keberlanjutan-Santri-'.now()->format('Ymd-His'),
            $report['source_note'],
        );
    }

    public function promotionsExport(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->promotions($this->schoolIds($request), $filters);

        return $this->download(
            $request,
            'Laporan Kenaikan Kelas dan Kelulusan',
            $this->periodText($filters),
            $this->promotionSummary($report['summary'], $report['graduation']),
            ['No', 'NISN', 'Nama Santri', 'Unit', 'Jenjang', 'Kelas', 'Tindakan', 'Kelas Berikutnya'],
            $report['details']->values()->map(fn (array $row, int $index) => [
                $index + 1, $row['nisn'] ?: '-', $row['student_name'] ?: '-', $row['school_name'] ?: '-',
                $row['grade_name'] ?: '-', $row['class_name'] ?: '-', $row['action_label'],
                $row['next_class_name'] ?: '-',
            ])->all(),
            'Laporan-Kenaikan-Kelulusan-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function promotionsPdf(Request $request, string $userId, StudentPopulationService $service)
    {
        $filters = $this->filters($request);
        $report = $service->promotions($this->schoolIds($request), $filters);

        return $this->pdf(
            $request,
            'Laporan Kenaikan Kelas dan Kelulusan',
            $this->periodText($filters),
            $this->promotionSummary($report['summary'], $report['graduation']),
            ['No', 'Nama Santri', 'Unit', 'Jenjang/Kelas', 'Tindakan', 'Berikutnya'],
            $report['details']->values()->map(fn (array $row, int $index) => [
                $index + 1, $row['student_name'] ?: '-', $row['school_name'] ?: '-',
                ($row['grade_name'] ?: '-').' / '.($row['class_name'] ?: '-'),
                $row['action_label'], $row['next_class_name'] ?: '-',
            ])->all(),
            'Laporan-Kenaikan-Kelulusan-'.now()->format('Ymd-His'),
            $report['source_note'],
        );
    }

    private function download(Request $request, string $title, string $subtitle, array $summaryRows, array $headings, array $rows, string $filename)
    {
        return Excel::download(
            new AcademicOperationsExport($title, $subtitle, $summaryRows, $headings, $rows, $this->schoolLabel($request)['name'] ?? null),
            $filename,
        );
    }

    private function pdf(Request $request, string $title, string $subtitle, array $summaryRows, array $headings, array $rows, string $filename, ?string $sourceNote = null)
    {
        return app(DocumentPdfService::class)->fromView(
            'user.laporan.pdf.akademik-operasional',
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'summaryRows' => $summaryRows,
                'headings' => $headings,
                'rows' => $rows,
                'sourceNote' => $sourceNote ?: 'Sumber data populasi santri ALIM',
                'school' => $this->schoolLabel($request),
                'printedAt' => now(),
            ],
            $filename,
            'landscape',
        );
    }

    private function mutationSummary(array $summary): array
    {
        return [
            ['Total peristiwa', number_format($summary['total'])],
            ['Masuk', number_format($summary['total_in'])],
            ['Keluar', number_format($summary['total_out'])],
            ['Internal', number_format($summary['total_internal'])],
            ['Berhenti', number_format($summary['berhenti'])],
            ['Selisih masuk-keluar', number_format($summary['net'])],
        ];
    }

    private function growthSummary(array $summary): array
    {
        return [
            ['Awal tahun', number_format($summary['opening'])],
            ['Santri baru', number_format($summary['new'])],
            ['Mutasi masuk', number_format($summary['mutation_in'])],
            ['Mutasi keluar', number_format($summary['mutation_out'])],
            ['Berhenti', number_format($summary['dropout'])],
            ['Lulusan', number_format($summary['graduates'])],
            ['Akhir tahun', number_format($summary['closing'])],
            ['Pertumbuhan', number_format($summary['net_growth'])],
            ['Persentase', $summary['growth_rate'] !== null ? number_format($summary['growth_rate'], 2).'%' : '-'],
            ['Rekonsiliasi', number_format($summary['reconciliation_difference'])],
        ];
    }

    private function profileSummary(array $summary): array
    {
        return [
            ['Santri aktif', number_format($summary['total'])],
            ['Laki-laki', number_format($summary['male'])],
            ['Perempuan', number_format($summary['female'])],
            ['Rombel', number_format($summary['rombel'])],
            ['Rata-rata/rombel', $summary['average_per_rombel'] !== null ? number_format($summary['average_per_rombel'], 2) : '-'],
            ['Pertumbuhan populasi', $summary['growth_rate'] !== null ? number_format($summary['growth_rate'], 2).'%' : '-'],
        ];
    }

    private function continuitySummary(array $summary): array
    {
        return [
            ['Santri acuan', number_format($summary['cohort'])],
            ['Melanjutkan', number_format($summary['continued'])],
            ['Tidak tercatat', number_format($summary['not_continued'])],
            ['Sudah lulus', number_format($summary['graduated'])],
            ['Keluar tercatat', number_format($summary['known_exit'])],
            ['Persentase', $summary['rate'] !== null ? number_format($summary['rate'], 2).'%' : '-'],
        ];
    }

    private function promotionSummary(array $summary, array $graduation): array
    {
        return [
            ['Santri acuan', number_format($summary['total'])],
            ['Naik kelas', number_format($summary['promoted'])],
            ['Tinggal kelas', number_format($summary['retained'])],
            ['Pindah unit', number_format($summary['moved_unit'])],
            ['Pindah rombel', number_format($summary['rombel_moved'])],
            ['Lulusan', number_format($graduation['graduates'] ?? 0)],
            ['Angka kelulusan', isset($graduation['rate']) && $graduation['rate'] !== null ? number_format($graduation['rate'], 2).'%' : '-'],
        ];
    }

    private function periodText(array $filters): string
    {
        $year = AcademicYear::find($filters['academic_year_id']);
        $period = $year?->name ?? '-';

        if (! empty($filters['dari']) || ! empty($filters['sampai'])) {
            $period .= ' · '.($filters['dari'] ? Carbon::parse($filters['dari'])->format('d/m/Y') : 'awal')
                .' — '.($filters['sampai'] ? Carbon::parse($filters['sampai'])->format('d/m/Y') : 'akhir');
        }

        return $period;
    }

    private function filters(Request $request): array
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id')
            ?: AcademicYear::orderByDesc('start_date')->value('id');
        $academicYearId = (string) ($request->input('academic_year_id') ?: $activeYearId);
        $academicYear = AcademicYear::find($academicYearId);

        $defaultFrom = $academicYear?->start_date?->toDateString();
        $defaultTo = $academicYear?->end_date?->toDateString();

        $validated = Validator::make([
            'academic_year_id' => $academicYearId,
            'school_id' => $request->input('school_id'),
            'grade_level_id' => $request->input('grade_level_id'),
            'study_group_id' => $request->input('study_group_id'),
            'dari' => $request->input('dari') ?: $defaultFrom,
            'sampai' => $request->input('sampai') ?: $defaultTo,
            'gender' => $request->input('gender'),
            'q' => $request->input('q'),
        ], [
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'school_id' => 'nullable|uuid|exists:schools,id',
            'grade_level_id' => 'nullable|uuid|exists:grade_levels,id',
            'study_group_id' => 'nullable|uuid|exists:study_groups,id',
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date',
            'gender' => 'nullable|in:L,P',
            'q' => 'nullable|string|max:100',
        ])->validate();

        foreach (['school_id', 'grade_level_id', 'study_group_id', 'dari', 'sampai', 'gender', 'q'] as $key) {
            $validated[$key] = $validated[$key] ?? null;
        }

        $lockedSchoolId = $this->lockedSchoolId($request);
        if ($lockedSchoolId) {
            $validated['school_id'] = $lockedSchoolId;
        } elseif ($validated['school_id'] && ! in_array($validated['school_id'], $this->allSchoolIds(), true)) {
            abort(403, 'Unit tidak dapat diakses.');
        }

        if ($validated['dari'] && $validated['sampai'] && $validated['dari'] > $validated['sampai']) {
            [$validated['dari'], $validated['sampai']] = [$validated['sampai'], $validated['dari']];
        }

        return $validated;
    }

    private function academicYearOrFail(array $filters): AcademicYear
    {
        $academicYear = AcademicYear::find($filters['academic_year_id']);
        abort_unless($academicYear, 404, 'Tahun ajaran tidak ditemukan.');

        return $academicYear;
    }

    private function schoolIds(Request $request): array
    {
        $locked = $this->lockedSchoolId($request);
        if ($locked) {
            return [$locked];
        }

        $selected = $request->input('school_id');
        if ($selected) {
            return in_array($selected, $this->allSchoolIds(), true) ? [$selected] : [];
        }

        return $this->allSchoolIds();
    }

    private function lockedSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId')
            ?: $request->user()?->employment?->school_id;
    }

    private function allSchoolIds(): array
    {
        return School::query()->where('is_active', true)->orderBy('name')->pluck('id')->all();
    }

    private function options(Request $request, AcademicYear $academicYear): array
    {
        $locked = $this->lockedSchoolId($request);
        $schoolIds = $locked ? [$locked] : $this->allSchoolIds();
        $selectedSchool = $locked ?: $request->input('school_id');

        $schools = School::query()
            ->whereIn('id', $schoolIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $gradeLevels = GradeLevel::query()
            ->whereIn('school_id', $schoolIds)
            ->when($selectedSchool, fn ($query, $id) => $query->where('school_id', $id))
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        $yearIdsForOptions = AcademicYear::query()
            ->where('name', $academicYear->name)
            ->pluck('id')
            ->all();

        $studyGroups = StudyGroup::query()
            ->with('school')
            ->whereIn('school_id', $schoolIds)
            ->when($selectedSchool, fn ($query, $id) => $query->where('school_id', $id))
            ->whereIn('academic_year_id', $yearIdsForOptions)
            ->where('is_active', true)
            ->orderedByGrade()
            ->get();

        return [
            'academicYears' => AcademicYear::query()
                ->orderByDesc('is_active')
                ->orderByDesc('start_date')
                ->get()
                ->unique('name')
                ->values(),
            'schools' => $schools,
            'gradeLevels' => $gradeLevels,
            'studyGroups' => $studyGroups,
            'schoolLocked' => (bool) $locked,
        ];
    }

    private function schoolLabel(Request $request): ?School
    {
        $locked = $this->lockedSchoolId($request);
        $selected = $locked ?: $request->input('school_id');

        if ($selected) {
            return School::find($selected);
        }

        $ids = $this->schoolIds($request);

        return count($ids) === 1 ? School::find($ids[0]) : null;
    }

    private function paginate($rows, Request $request, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $items = collect($rows)->forPage($page, $perPage)->values();

        return new Paginator($items, count($rows), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);
    }
}
