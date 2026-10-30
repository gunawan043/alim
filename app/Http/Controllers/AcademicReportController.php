<?php

namespace App\Http\Controllers;

use App\Exports\AcademicAnalysisExport;
use App\Exports\AcademicOperationsExport;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Academic\AcademicAnalysisService;
use App\Services\Academic\AcademicOperationsService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan analisis akademik — prioritas: nilai murni STS/SAS.
 */
class AcademicReportController extends Controller
{
    public function index(Request $request, string $userId, AcademicAnalysisService $analysisService)
    {
        $filters = $this->filters($request);
        $academicYear = AcademicYear::find($filters['academic_year_id']);

        abort_unless($academicYear, 404, 'Tahun ajaran tidak ditemukan.');

        $analysis = $analysisService->analyze($this->schoolId($request), $filters);
        $rows = $this->paginate($analysis['rows'], $request, 50);

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $gradeLevels = GradeLevel::query()
            ->when($this->schoolId($request), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('level')
            ->get();
        $studyGroups = StudyGroup::query()
            ->when($this->schoolId($request), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->orderedByGrade()
            ->get();
        $subjects = Subject::query()
            ->when($this->schoolId($request), fn ($q, $schoolId) => $q->where(fn ($sub) => $sub->whereNull('school_id')->orWhere('school_id', $schoolId)))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('user.laporan.akademik', compact(
            'userId', 'filters', 'analysis', 'rows', 'academicYears', 'gradeLevels', 'studyGroups', 'subjects',
        ));
    }

    public function exportExcel(Request $request, string $userId, AcademicAnalysisService $analysisService)
    {
        $filters = $this->filters($request);
        $analysis = $analysisService->analyze($this->schoolId($request), $filters);
        $school = $this->school($request);

        return Excel::download(
            new AcademicAnalysisExport($analysis, $school, $filters),
            'Analisis-Akademik-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function pdf(Request $request, string $userId, AcademicAnalysisService $analysisService)
    {
        $filters = $this->filters($request);
        $analysis = $analysisService->analyze($this->schoolId($request), $filters);
        $school = $this->school($request);

        return app(DocumentPdfService::class)->fromView(
            'user.laporan.pdf.akademik',
            [
                'analysis' => $analysis,
                'school' => $school,
                'filters' => $filters,
                'printedAt' => now(),
            ],
            'Analisis-Akademik-'.now()->format('Ymd-His'),
            'landscape',
        );
    }

    public function history(Request $request, string $userId, string $studentId)
    {
        $schoolId = $this->schoolId($request);
        $student = Student::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->findOrFail($studentId);

        $values = NilaiSumatif::query()
            ->with(['adminBook.subject', 'adminBook.studyGroup.gradeLevel', 'adminBook.academicYear'])
            ->where('student_id', $studentId)
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('adminBook', fn ($book) => $book->where('subject_id', $request->input('subject_id'))))
            ->when($schoolId, fn ($q, $id) => $q->whereHas('adminBook', fn ($book) => $book->where('school_id', $id)))
            ->get()
            ->sortBy(fn (NilaiSumatif $row) => implode('|', [
                $row->adminBook?->academicYear?->start_date?->format('Ymd') ?? '',
                $row->semester,
                $row->adminBook?->subject?->name ?? '',
            ]))
            ->values();

        return view('user.laporan.partials.student-history', compact('student', 'values'));
    }

    public function completion(Request $request, string $userId, AcademicAnalysisService $analysisService, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $analysis = $operationsService->completion($this->schoolId($request), $filters, $analysisService);
        $rows = $this->paginate($analysis['rows'], $request, 50);

        return view('user.laporan.akademik-ketuntasan', array_merge(
            compact('userId', 'filters', 'analysis', 'rows'),
            $this->options($request, $academicYear, true),
        ));
    }

    public function attendance(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->attendanceFilters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $operationsService->attendance($this->schoolId($request), $filters);
        $rows = $this->paginate($report['rows'], $request, 50);

        return view('user.laporan.kehadiran-santri', array_merge(
            compact('userId', 'filters', 'report', 'rows'),
            $this->options($request, $academicYear, true),
        ));
    }

    public function implementation(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $operationsService->implementation($this->schoolId($request), $filters);
        $rows = $this->paginate($report['books'], $request, 50);

        return view('user.laporan.keterlaksanaan-pembelajaran', array_merge(
            compact('userId', 'filters', 'report', 'rows'),
            $this->options($request, $academicYear, true),
        ));
    }

    public function administration(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);
        $report = $operationsService->administration($this->schoolId($request), $filters);
        $rows = $this->paginate($report['books'], $request, 50);

        return view('user.laporan.administrasi-akademik', array_merge(
            compact('userId', 'filters', 'report', 'rows'),
            $this->options($request, $academicYear, true),
        ));
    }

    public function completionExport(Request $request, string $userId, AcademicAnalysisService $analysisService, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $analysis = $operationsService->completion($this->schoolId($request), $filters, $analysisService);

        return $this->downloadOperational(
            $request,
            'Laporan Nilai & Ketuntasan Akademik',
            $this->comparisonText($analysis),
            $this->completionSummaryRows($analysis),
            ['No', 'NISN/NIS', 'Nama Santri', 'Kelas', 'Mapel', 'STS', 'SAS', 'KKTP', 'Status'],
            $analysis['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['nisn'] ?: $row['nis'] ?: '-',
                $row['student_name'],
                $row['class_name'],
                $row['subject_name'],
                $this->number($row['sts']),
                $this->number($row['sas']),
                $this->number($row['kktp']),
                $this->completionStatus($row),
            ])->all(),
            'Ketuntasan-Akademik-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function completionPdf(Request $request, string $userId, AcademicAnalysisService $analysisService, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $analysis = $operationsService->completion($this->schoolId($request), $filters, $analysisService);

        return $this->pdfOperational(
            $request,
            'Laporan Nilai & Ketuntasan Akademik',
            $this->comparisonText($analysis),
            $this->completionSummaryRows($analysis),
            ['No', 'NISN/NIS', 'Nama Santri', 'Kelas', 'Mapel', 'STS', 'SAS', 'KKTP', 'Status'],
            $analysis['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['nisn'] ?: $row['nis'] ?: '-',
                $row['student_name'],
                $row['class_name'],
                $row['subject_name'],
                $this->number($row['sts']),
                $this->number($row['sas']),
                $this->number($row['kktp']),
                $this->completionStatus($row),
            ])->all(),
            'Ketuntasan-Akademik-'.now()->format('Ymd-His'),
        );
    }

    public function attendanceExport(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->attendanceFilters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->attendance($this->schoolId($request), $filters);

        return $this->downloadOperational(
            $request,
            'Laporan Kehadiran Santri',
            $this->attendancePeriodText($filters, $report['source']),
            $this->attendanceSummaryRows($report['summary']),
            ['No', 'Tanggal', 'NISN/NIS', 'Nama Santri', 'Kelas', 'Mapel', 'Status'],
            $report['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['date'] ? Carbon::parse($row['date'])->format('d/m/Y') : '-',
                $row['nisn'] ?: '-',
                $row['student_name'],
                $row['class_name'],
                $row['subject_name'] ?: '-',
                $row['status_text'],
            ])->all(),
            'Kehadiran-Santri-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function attendancePdf(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->attendanceFilters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->attendance($this->schoolId($request), $filters);

        return $this->pdfOperational(
            $request,
            'Laporan Kehadiran Santri',
            $this->attendancePeriodText($filters, $report['source']),
            $this->attendanceSummaryRows($report['summary']),
            ['No', 'Tanggal', 'NISN/NIS', 'Nama Santri', 'Kelas', 'Mapel', 'Status'],
            $report['rows']->values()->map(fn (array $row, int $index) => [
                $index + 1,
                $row['date'] ? Carbon::parse($row['date'])->format('d/m/Y') : '-',
                $row['nisn'] ?: '-',
                $row['student_name'],
                $row['class_name'],
                $row['subject_name'] ?: '-',
                $row['status_text'],
            ])->all(),
            'Kehadiran-Santri-'.now()->format('Ymd-His'),
        );
    }

    public function implementationExport(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->implementation($this->schoolId($request), $filters);

        return $this->downloadOperational(
            $request,
            'Laporan Keterlaksanaan Pembelajaran',
            $this->periodText($filters),
            $this->implementationSummaryRows($report['summary']),
            ['Guru', 'Mapel', 'Kelas', 'Perangkat', 'Jurnal', 'Presensi', 'Jadwal/minggu', 'Nilai', 'Status'],
            $report['books']->values()->map(fn (array $row) => [
                $row['teacher_name'],
                $row['subject_name'],
                $row['class_name'],
                $row['plan_count'],
                $row['journal_count'],
                $row['presensi_count'],
                $row['scheduled_slots'],
                $row['nilai_percent'] !== null ? number_format($row['nilai_percent'], 2).'%' : '-',
                $row['implementation_label'],
            ])->all(),
            'Keterlaksanaan-Pembelajaran-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function implementationPdf(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->implementation($this->schoolId($request), $filters);

        return $this->pdfOperational(
            $request,
            'Laporan Keterlaksanaan Pembelajaran',
            $this->periodText($filters),
            $this->implementationSummaryRows($report['summary']),
            ['Guru', 'Mapel', 'Kelas', 'Perangkat', 'Jurnal', 'Presensi', 'Jadwal/minggu', 'Nilai', 'Status'],
            $report['books']->values()->map(fn (array $row) => [
                $row['teacher_name'],
                $row['subject_name'],
                $row['class_name'],
                $row['plan_count'],
                $row['journal_count'],
                $row['presensi_count'],
                $row['scheduled_slots'],
                $row['nilai_percent'] !== null ? number_format($row['nilai_percent'], 2).'%' : '-',
                $row['implementation_label'],
            ])->all(),
            'Keterlaksanaan-Pembelajaran-'.now()->format('Ymd-His'),
        );
    }

    public function administrationExport(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->administration($this->schoolId($request), $filters);

        return $this->downloadOperational(
            $request,
            'Laporan Kelengkapan Administrasi Akademik',
            $this->periodText($filters),
            $this->administrationSummaryRows($report['summary']),
            ['Guru', 'Mapel', 'Kelas', 'Perangkat', 'Jurnal', 'Presensi', 'Nilai', 'Skor', 'Tindak Lanjut'],
            $report['books']->values()->map(fn (array $row) => [
                $row['teacher_name'],
                $row['subject_name'],
                $row['class_name'],
                $row['has_plan'] ? 'Ada' : 'Belum',
                $row['has_journal'] ? 'Ada' : 'Belum',
                $row['has_presensi'] ? 'Ada' : 'Belum',
                $row['nilai_complete'] ? 'Lengkap' : 'Belum',
                number_format($row['administration_score'], 2).'%',
                $row['administration_complete'] ? 'Lengkap' : $row['action'],
            ])->all(),
            'Administrasi-Akademik-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function administrationPdf(Request $request, string $userId, AcademicOperationsService $operationsService)
    {
        $filters = $this->filters($request);
        $this->academicYearOrFail($filters);
        $report = $operationsService->administration($this->schoolId($request), $filters);

        return $this->pdfOperational(
            $request,
            'Laporan Kelengkapan Administrasi Akademik',
            $this->periodText($filters),
            $this->administrationSummaryRows($report['summary']),
            ['Guru', 'Mapel', 'Kelas', 'Perangkat', 'Jurnal', 'Presensi', 'Nilai', 'Skor', 'Tindak Lanjut'],
            $report['books']->values()->map(fn (array $row) => [
                $row['teacher_name'],
                $row['subject_name'],
                $row['class_name'],
                $row['has_plan'] ? 'Ada' : 'Belum',
                $row['has_journal'] ? 'Ada' : 'Belum',
                $row['has_presensi'] ? 'Ada' : 'Belum',
                $row['nilai_complete'] ? 'Lengkap' : 'Belum',
                number_format($row['administration_score'], 2).'%',
                $row['administration_complete'] ? 'Lengkap' : $row['action'],
            ])->all(),
            'Administrasi-Akademik-'.now()->format('Ymd-His'),
        );
    }

    private function downloadOperational(Request $request, string $title, string $subtitle, array $summaryRows, array $headings, array $rows, string $filename)
    {
        return Excel::download(
            new AcademicOperationsExport($title, $subtitle, $summaryRows, $headings, $rows, $this->school($request)?->name),
            $filename,
        );
    }

    private function pdfOperational(Request $request, string $title, string $subtitle, array $summaryRows, array $headings, array $rows, string $filename)
    {
        return app(DocumentPdfService::class)->fromView(
            'user.laporan.pdf.akademik-operasional',
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'summaryRows' => $summaryRows,
                'headings' => $headings,
                'rows' => $rows,
                'sourceNote' => 'Sumber data akademik ALIM',
                'school' => $this->school($request),
                'printedAt' => now(),
            ],
            $filename,
            'landscape',
        );
    }

    private function completionSummaryRows(array $analysis): array
    {
        return [
            ['Periode', $analysis['current_label']],
            ['Pembanding', $analysis['previous_label'] ?? '-'],
            ['Data nilai', number_format($analysis['summary']['total_rows'])],
            ['Nilai lengkap', $analysis['summary']['complete_rate'] !== null ? number_format($analysis['summary']['complete_rate'], 2).'%' : '-'],
            ['Ketuntasan', $analysis['summary']['mastery_rate'] !== null ? number_format($analysis['summary']['mastery_rate'], 2).'%' : '-'],
            ['Belum lengkap', number_format($analysis['summary']['missing'])],
            ['Di bawah KKTP', number_format($analysis['summary']['below_kktp'])],
            ['Santri', number_format($analysis['summary']['students'])],
        ];
    }

    private function attendanceSummaryRows(array $summary): array
    {
        return [
            ['Catatan', number_format($summary['total'])],
            ['Kehadiran', $summary['attendance_rate'] !== null ? number_format($summary['attendance_rate'], 2).'%' : '-'],
            ['Hadir/terlambat', number_format($summary['present'])],
            ['Alpa', number_format($summary['alpa'])],
            ['Izin', number_format($summary['izin'])],
            ['Sakit', number_format($summary['sakit'])],
            ['Santri', number_format($summary['students'])],
            ['Kelas', number_format($summary['classes'])],
        ];
    }

    private function implementationSummaryRows(array $summary): array
    {
        return [
            ['Buku', number_format($summary['total_books'])],
            ['Perangkat', number_format($summary['planned_books'])],
            ['Jurnal', number_format($summary['journal_books'])],
            ['Presensi', number_format($summary['presensi_books'])],
            ['Terlaksana', number_format($summary['implemented_books'])],
            ['Sebagian', number_format($summary['partial_books'])],
            ['Belum', number_format($summary['not_implemented_books'])],
            ['Slot jadwal/minggu', number_format($summary['total_scheduled_slots'])],
        ];
    }

    private function administrationSummaryRows(array $summary): array
    {
        return [
            ['Buku', number_format($summary['total_books'])],
            ['Guru', number_format($summary['total_teachers'])],
            ['Kewajiban', number_format($summary['obligations'])],
            ['Terpenuhi', number_format($summary['completed'])],
            ['Persentase', $summary['percentage'] !== null ? number_format($summary['percentage'], 2).'%' : '-'],
            ['Buku lengkap', number_format($summary['complete_books'])],
            ['Belum lengkap', number_format($summary['incomplete_books'])],
        ];
    }

    private function comparisonText(array $analysis): string
    {
        return $analysis['current_label'].' · Pembanding: '.($analysis['previous_label'] ?? '-');
    }

    private function attendancePeriodText(array $filters, string $source): string
    {
        return Carbon::parse($filters['dari'])->format('d/m/Y').' — '.Carbon::parse($filters['sampai'])->format('d/m/Y')
            .' · Sumber: '.($source === 'mapel' ? 'Presensi Mapel' : 'Presensi Harian');
    }

    private function periodText(array $filters): string
    {
        $year = AcademicYear::find($filters['academic_year_id']);

        return ($year?->name ?? '-').' — '.(ucfirst($filters['semester']));
    }

    private function completionStatus(array $row): string
    {
        if ($row['missing']) {
            return 'Belum lengkap';
        }

        if ($row['below_kktp']) {
            return 'Di bawah KKTP';
        }

        return $row['kktp'] !== null ? 'Tuntas' : 'KKTP belum ada';
    }

    private function number($value): string
    {
        return $value !== null ? number_format((float) $value, 2) : '-';
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $academicYearId = (string) ($request->input('academic_year_id') ?: $activeYearId);
        $academicYear = AcademicYear::find($academicYearId);
        $semester = (string) ($request->input('semester') ?: ($academicYear?->semester ?? 'ganjil'));

        $validated = Validator::make([
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
            'grade_level_id' => $request->input('grade_level_id'),
            'study_group_id' => $request->input('study_group_id'),
            'subject_id' => $request->input('subject_id'),
            'teacher_id' => $request->input('teacher_id'),
            'q' => $request->input('q'),
            'compare_mode' => $request->input('compare_mode', 'previous_period'),
        ], [
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'grade_level_id' => 'nullable|uuid|exists:grade_levels,id',
            'study_group_id' => 'nullable|uuid|exists:study_groups,id',
            'subject_id' => 'nullable|uuid|exists:subjects,id',
            'teacher_id' => 'nullable|uuid|exists:users,id',
            'q' => 'nullable|string|max:100',
            'compare_mode' => 'nullable|in:previous_period,previous_year',
        ])->validate();

        $validated['grade_level_id'] = $validated['grade_level_id'] ?? null;
        $validated['study_group_id'] = $validated['study_group_id'] ?? null;
        $validated['subject_id'] = $validated['subject_id'] ?? null;
        $validated['teacher_id'] = $validated['teacher_id'] ?? null;
        $validated['q'] = $validated['q'] ?? null;
        $validated['compare_mode'] = $validated['compare_mode'] ?? 'previous_period';

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceFilters(Request $request): array
    {
        $filters = $this->filters($request);
        $academicYear = $this->academicYearOrFail($filters);

        $source = $request->input('source') === 'mapel' ? 'mapel' : 'harian';
        $defaultStart = $academicYear->start_date?->toDateString() ?: now()->startOfMonth()->toDateString();
        $defaultEnd = $academicYear->end_date?->toDateString() ?: now()->toDateString();

        try {
            $dari = $request->filled('dari') ? Carbon::parse($request->input('dari'))->toDateString() : $defaultStart;
        } catch (\Throwable) {
            $dari = $defaultStart;
        }

        try {
            $sampai = $request->filled('sampai') ? Carbon::parse($request->input('sampai'))->toDateString() : $defaultEnd;
        } catch (\Throwable) {
            $sampai = $defaultEnd;
        }

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return array_merge($filters, compact('source', 'dari', 'sampai'));
    }

    private function academicYearOrFail(array $filters): AcademicYear
    {
        $academicYear = AcademicYear::find($filters['academic_year_id']);

        abort_unless($academicYear, 404, 'Tahun ajaran tidak ditemukan.');

        return $academicYear;
    }

    /**
     * @return array<string, mixed>
     */
    private function options(Request $request, AcademicYear $academicYear, bool $withTeachers = false): array
    {
        $schoolId = $this->schoolId($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('is_active', true)
            ->orderBy('level')
            ->get();
        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('academic_year_id', $academicYear->id)
            ->where('is_active', true)
            ->orderedByGrade()
            ->get();
        $subjects = Subject::query()
            ->when($schoolId, fn ($q, $id) => $q->where(fn ($sub) => $sub->whereNull('school_id')->orWhere('school_id', $id)))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $teachers = collect();
        if ($withTeachers) {
            $selectedSemester = (string) (request('semester') ?: $academicYear->semester);
            $teacherIds = TeacherAdminBook::query()
                ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
                ->where('academic_year_id', $academicYear->id)
                ->where('semester', $selectedSemester)
                ->whereNotNull('teacher_id')
                ->pluck('teacher_id')
                ->unique();

            $teachers = User::query()->whereIn('id', $teacherIds)->orderBy('name')->get();
        }

        return compact('academicYears', 'gradeLevels', 'studyGroups', 'subjects', 'teachers');
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId') ?: $request->user()?->employment?->school_id;
    }

    private function school(Request $request): ?School
    {
        $schoolId = $this->schoolId($request);

        return $schoolId ? School::find($schoolId) : null;
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
