<?php

namespace App\Services\Academic;

use App\Models\AdminJurnalPembelajaran;
use App\Models\AdminPresensiHarian;
use App\Models\AdminPresensiMapel;
use App\Models\AdminPresensiSiswa;
use App\Models\JadwalKbm;
use App\Models\NilaiSumatif;
use App\Models\PerangkatPembelajaran;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laporan operasional akademik: ketuntasan, kehadiran, keterlaksanaan,
 * dan kelengkapan administrasi. Semua angka berasal dari data yang tersedia;
 * indikator yang tidak memiliki sumber data tidak diisi dengan asumsi.
 */
class AcademicOperationsService
{
    /**
     * Laporan nilai dan ketuntasan akademik.
     *
     * @return array<string, mixed>
     */
    public function completion(?string $schoolId, array $filters, AcademicAnalysisService $analysisService): array
    {
        $analysis = $analysisService->analyze($schoolId, $filters);

        $summary = $this->withCompletionRates($analysis['summary']);
        $previous = $this->withCompletionRates($analysis['previous_summary']);
        $classes = $analysis['class_stats']->map(fn (array $row) => $this->withCompletionRates($row));
        $subjects = $analysis['subject_stats']->map(fn (array $row) => $this->withCompletionRates($row));

        $comparison = array_merge($analysis['comparison'], [
            'complete_rate_delta' => $this->difference($summary['complete_rate'], $previous['complete_rate']),
            'mastery_rate_delta' => $this->difference($summary['mastery_rate'], $previous['mastery_rate']),
        ]);

        $findings = $analysis['findings'];

        $lowMasteryClasses = $classes
            ->filter(fn (array $row) => $row['with_kktp'] > 0 && $row['mastery_rate'] !== null && $row['mastery_rate'] < 60)
            ->sortBy('mastery_rate')
            ->take(10)
            ->values();

        if ($lowMasteryClasses->isNotEmpty()) {
            $findings[] = [
                'key' => 'low_mastery_class',
                'title' => 'Kelas dengan ketuntasan di bawah 60%',
                'count' => $lowMasteryClasses->count(),
                'description' => 'Kelas ini perlu ditinjau bersama guru mapel terkait.',
                'rows' => $lowMasteryClasses,
            ];
        }

        return array_merge($analysis, [
            'summary' => $summary,
            'previous_summary' => $previous,
            'comparison' => $comparison,
            'class_stats' => $classes,
            'subject_stats' => $subjects,
            'findings' => $findings,
        ]);
    }

    /**
     * Laporan keterlaksanaan pembelajaran berdasarkan perangkat, jurnal, dan presensi mapel.
     *
     * @return array<string, mixed>
     */
    public function implementation(?string $schoolId, array $filters): array
    {
        $books = $this->bookRows($schoolId, $filters);

        $summary = [
            'total_books' => $books->count(),
            'planned_books' => $books->where('has_plan', true)->count(),
            'journal_books' => $books->where('has_journal', true)->count(),
            'presensi_books' => $books->where('has_presensi', true)->count(),
            'implemented_books' => $books->where('implementation_status', 'terlaksana')->count(),
            'partial_books' => $books->where('implementation_status', 'sebagian')->count(),
            'not_implemented_books' => $books->where('implementation_status', 'belum')->count(),
            'total_journals' => $books->sum('journal_count'),
            'total_meetings' => $books->sum('presensi_count'),
            'total_plans' => $books->sum('plan_count'),
            'total_scheduled_slots' => $books->sum('scheduled_slots'),
            'total_students' => $books->sum('student_count'),
        ];

        return [
            'books' => $books,
            'summary' => $summary,
            'teacher_stats' => $this->teacherStats($books),
            'findings' => $this->implementationFindings($books),
        ];
    }

    /**
     * Laporan kelengkapan administrasi akademik per buku/guru.
     *
     * @return array<string, mixed>
     */
    public function administration(?string $schoolId, array $filters): array
    {
        $books = $this->bookRows($schoolId, $filters);
        $obligations = $books->count() * 4;
        $completed = $books->sum(fn (array $row) => collect($row['components'])->where('done', true)->count());

        $summary = [
            'total_books' => $books->count(),
            'total_teachers' => $books->pluck('teacher_id')->filter()->unique()->count(),
            'obligations' => $obligations,
            'completed' => $completed,
            'percentage' => $obligations > 0 ? round($completed / $obligations * 100, 2) : null,
            'complete_books' => $books->where('administration_complete', true)->count(),
            'incomplete_books' => $books->where('administration_complete', false)->count(),
            'missing_plan' => $books->where('has_plan', false)->count(),
            'missing_journal' => $books->where('has_journal', false)->count(),
            'missing_presensi' => $books->where('has_presensi', false)->count(),
            'missing_nilai' => $books->where('nilai_complete', false)->count(),
        ];

        return [
            'books' => $books,
            'summary' => $summary,
            'teacher_stats' => $this->teacherStats($books),
            'incomplete' => $books->where('administration_complete', false)->values(),
        ];
    }

    /**
     * Laporan kehadiran santri dari presensi harian atau presensi mapel.
     *
     * @return array<string, mixed>
     */
    public function attendance(?string $schoolId, array $filters): array
    {
        $source = ($filters['source'] ?? 'harian') === 'mapel' ? 'mapel' : 'harian';
        $rows = $source === 'mapel'
            ? $this->attendanceMapelRows($schoolId, $filters)
            : $this->attendanceDailyRows($schoolId, $filters);

        $summary = $this->attendanceSummary($rows);
        $classStats = $this->attendanceClassStats($rows);
        $studentStats = $this->attendanceStudentStats($rows);
        $monthly = $this->attendanceMonthly($rows);

        return [
            'source' => $source,
            'rows' => $rows,
            'summary' => $summary,
            'class_stats' => $classStats,
            'student_stats' => $studentStats,
            'monthly' => $monthly,
            'findings' => $this->attendanceFindings($studentStats, $classStats),
            'academic_relation' => $this->attendanceAcademicRelation($schoolId, $filters, $studentStats),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function attendanceDailyRows(?string $schoolId, array $filters): Collection
    {
        return AdminPresensiHarian::query()
            ->with(['student', 'studyGroup.gradeLevel'])
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('semester', $filters['semester'])
            ->whereBetween('attendance_date', [$filters['dari'], $filters['sampai']])
            ->when($schoolId, fn ($q, $id) => $q->whereHas('studyGroup', fn ($group) => $group->where('school_id', $id)))
            ->when($filters['study_group_id'] ?? null, fn ($q, $id) => $q->where('study_group_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereHas('student', fn ($student) => $student->where('name', 'like', '%'.$search.'%')))
            ->orderBy('attendance_date')
            ->get()
            ->map(function (AdminPresensiHarian $row) {
                return [
                    'date' => $row->attendance_date?->toDateString(),
                    'student_id' => $row->student_id,
                    'student_name' => $row->student?->name ?? '-',
                    'nisn' => $row->student?->nisn ?: $row->student?->nis,
                    'class_id' => $row->study_group_id,
                    'class_name' => $row->studyGroup?->name ?? '-',
                    'grade_level' => $row->studyGroup?->gradeLevel?->name ?? '-',
                    'subject_id' => null,
                    'subject_name' => null,
                    'status' => $row->status,
                    'status_text' => $row->status_text,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function attendanceMapelRows(?string $schoolId, array $filters): Collection
    {
        return AdminPresensiSiswa::query()
            ->with(['student', 'presensiMapel.adminBook.studyGroup.gradeLevel', 'presensiMapel.adminBook.subject'])
            ->whereHas('presensiMapel', function ($query) use ($schoolId, $filters) {
                $query->where('academic_year_id', $filters['academic_year_id'])
                    ->where('semester', $filters['semester'])
                    ->whereBetween('attendance_date', [$filters['dari'], $filters['sampai']])
                    ->whereHas('adminBook', function ($book) use ($schoolId, $filters) {
                        $book->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
                            ->when($filters['study_group_id'] ?? null, fn ($q, $id) => $q->where('study_group_id', $id))
                            ->when($filters['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id));
                    });
            })
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereHas('student', fn ($student) => $student->where('name', 'like', '%'.$search.'%')))
            ->get()
            ->map(function (AdminPresensiSiswa $row) {
                $meeting = $row->presensiMapel;
                $book = $meeting?->adminBook;

                return [
                    'date' => $meeting?->attendance_date?->toDateString(),
                    'student_id' => $row->student_id,
                    'student_name' => $row->student?->name ?? '-',
                    'nisn' => $row->student?->nisn ?: $row->student?->nis,
                    'class_id' => $book?->study_group_id,
                    'class_name' => $book?->studyGroup?->name ?? '-',
                    'grade_level' => $book?->studyGroup?->gradeLevel?->name ?? '-',
                    'subject_id' => $book?->subject_id,
                    'subject_name' => $book?->subject?->name ?? '-',
                    'status' => $row->status,
                    'status_text' => ucfirst($row->status),
                ];
            })
            ->filter(fn (array $row) => $row['date'] !== null)
            ->sortBy('date')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function bookRows(?string $schoolId, array $filters): Collection
    {
        $books = TeacherAdminBook::query()
            ->with(['teacher', 'subject', 'studyGroup.gradeLevel'])
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('semester', $filters['semester'])
            ->when($filters['grade_level_id'] ?? null, fn ($q, $id) => $q->whereHas('studyGroup', fn ($group) => $group->where('grade_level_id', $id)))
            ->when($filters['study_group_id'] ?? null, fn ($q, $id) => $q->where('study_group_id', $id))
            ->when($filters['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id))
            ->when($filters['teacher_id'] ?? null, fn ($q, $id) => $q->where('teacher_id', $id))
            ->get();

        if ($books->isEmpty()) {
            return collect();
        }

        $bookIds = $books->pluck('id');
        $teacherIds = $books->pluck('teacher_id')->filter()->unique();
        $subjectIds = $books->pluck('subject_id')->filter()->unique();

        $journalStats = AdminJurnalPembelajaran::query()
            ->whereIn('admin_book_id', $bookIds)
            ->selectRaw('admin_book_id, COUNT(*) as total, MAX(meeting_number) as max_meeting, MAX(meeting_date) as last_date')
            ->groupBy('admin_book_id')
            ->get()
            ->keyBy('admin_book_id');

        $presensiStats = AdminPresensiMapel::query()
            ->whereIn('admin_book_id', $bookIds)
            ->selectRaw('admin_book_id, COUNT(*) as total, MAX(attendance_date) as last_date')
            ->groupBy('admin_book_id')
            ->get()
            ->keyBy('admin_book_id');

        $nilaiByBook = NilaiSumatif::query()
            ->whereIn('admin_book_id', $bookIds)
            ->get(['admin_book_id', 'sts_murni', 'sas_murni'])
            ->groupBy('admin_book_id');

        $studentCounts = StudentClassHistory::query()
            ->whereIn('study_group_id', $books->pluck('study_group_id')->unique())
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('is_active', true)
            ->selectRaw('study_group_id, COUNT(*) as total')
            ->groupBy('study_group_id')
            ->pluck('total', 'study_group_id');

        $perangkats = PerangkatPembelajaran::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('semester', $filters['semester'])
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('subject_id', $subjectIds)
            ->get(['id', 'teacher_id', 'subject_id', 'study_group_id', 'status', 'workflow_status'])
            ->groupBy(fn (PerangkatPembelajaran $row) => $row->teacher_id.'|'.$row->subject_id.'|'.($row->study_group_id ?? '*'));

        $schedules = JadwalKbm::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('is_active', true)
            ->whereIn('teacher_id', $teacherIds)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy(fn (JadwalKbm $row) => $row->teacher_id.'|'.$row->subject_id.'|'.$row->study_group_id);

        return $books->map(function (TeacherAdminBook $book) use ($journalStats, $presensiStats, $nilaiByBook, $studentCounts, $perangkats, $schedules) {
            $planKey = $book->teacher_id.'|'.$book->subject_id.'|'.$book->study_group_id;
            $planDocs = collect($perangkats->get($planKey, []))->merge($perangkats->get($book->teacher_id.'|'.$book->subject_id.'|*', []));
            $journal = $journalStats->get($book->id);
            $presensi = $presensiStats->get($book->id);
            $nilaiRows = $nilaiByBook->get($book->id, collect());
            $studentCount = (int) ($studentCounts[$book->study_group_id] ?? 0);
            $expectedStudents = max($studentCount, $nilaiRows->count());
            $filled = $nilaiRows->filter(fn (NilaiSumatif $row) => $row->sts_murni !== null || $row->sas_murni !== null)->count();
            $complete = $nilaiRows->filter(fn (NilaiSumatif $row) => $row->sts_murni !== null && $row->sas_murni !== null)->count();
            $nilaiPercent = $expectedStudents > 0 ? round($complete / $expectedStudents * 100, 2) : null;
            $nilaiComplete = $nilaiPercent !== null && $nilaiPercent >= 100;
            $scheduledSlots = (int) collect($schedules->get($planKey, []))->count();
            $journalCount = (int) ($journal?->total ?? 0);
            $presensiCount = (int) ($presensi?->total ?? 0);
            $planCount = $planDocs->count();
            $approvedPlanCount = $planDocs->whereIn('workflow_status', ['approved'])->count();
            $hasPlan = $planCount > 0;
            $hasJournal = $journalCount > 0;
            $hasPresensi = $presensiCount > 0;

            $components = [
                'perangkat' => ['label' => 'Perangkat/RPM', 'done' => $hasPlan, 'detail' => $planCount.' dokumen'],
                'jurnal' => ['label' => 'Jurnal pembelajaran', 'done' => $hasJournal, 'detail' => $journalCount.' entri'],
                'realisasi' => ['label' => 'Presensi/realisasi', 'done' => $hasPresensi, 'detail' => $presensiCount.' pertemuan'],
                'nilai' => ['label' => 'Input nilai', 'done' => $nilaiComplete, 'detail' => ($nilaiPercent !== null ? number_format($nilaiPercent, 2).'%' : 'belum ada data')],
            ];
            $doneComponents = collect($components)->where('done', true)->count();
            $implementationStatus = $hasJournal && $hasPresensi
                ? 'terlaksana'
                : (($hasJournal || $hasPresensi) ? 'sebagian' : 'belum');
            $lastActivity = collect([$journal?->last_date, $presensi?->last_date])->filter()->max();

            return [
                'book_id' => $book->id,
                'teacher_id' => $book->teacher_id,
                'teacher_name' => $book->teacher?->name ?? '-',
                'subject_id' => $book->subject_id,
                'subject_name' => $book->subject?->name ?? '-',
                'subject_code' => $book->subject?->code ?? '-',
                'class_id' => $book->study_group_id,
                'class_name' => $book->studyGroup?->name ?? '-',
                'grade_level' => $book->studyGroup?->gradeLevel?->name ?? '-',
                'student_count' => $studentCount,
                'plan_count' => $planCount,
                'approved_plan_count' => $approvedPlanCount,
                'journal_count' => $journalCount,
                'presensi_count' => $presensiCount,
                'max_meeting' => (int) ($journal?->max_meeting ?? 0),
                'scheduled_slots' => $scheduledSlots,
                'nilai_rows' => $nilaiRows->count(),
                'nilai_filled' => $filled,
                'nilai_complete_count' => $complete,
                'nilai_percent' => $nilaiPercent,
                'nilai_complete' => $nilaiComplete,
                'has_plan' => $hasPlan,
                'has_journal' => $hasJournal,
                'has_presensi' => $hasPresensi,
                'implementation_status' => $implementationStatus,
                'implementation_label' => match ($implementationStatus) {
                    'terlaksana' => 'Terlaksana',
                    'sebagian' => 'Sebagian',
                    default => 'Belum terlaksana',
                },
                'components' => $components,
                'completed_components' => $doneComponents,
                'administration_score' => round($doneComponents / 4 * 100, 2),
                'administration_complete' => $doneComponents === 4,
                'missing_components' => collect($components)->reject(fn (array $component) => $component['done'])->keys()->values(),
                'last_activity' => $lastActivity,
                'action' => collect($components)->reject(fn (array $component) => $component['done'])->pluck('label')->implode(', '),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $books
     * @return Collection<int, array<string, mixed>>
     */
    private function teacherStats(Collection $books): Collection
    {
        return $books->groupBy('teacher_id')->map(function (Collection $group, string $teacherId) {
            return [
                'teacher_id' => $teacherId,
                'teacher_name' => $group->first()['teacher_name'],
                'books' => $group->count(),
                'planned' => $group->where('has_plan', true)->count(),
                'journal' => $group->where('has_journal', true)->count(),
                'presensi' => $group->where('has_presensi', true)->count(),
                'complete' => $group->where('administration_complete', true)->count(),
                'average_score' => round($group->avg('administration_score'), 2),
                'journals' => $group->sum('journal_count'),
                'meetings' => $group->sum('presensi_count'),
            ];
        })->sortBy('teacher_name')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $books
     * @return array<int, array<string, mixed>>
     */
    private function implementationFindings(Collection $books): array
    {
        $findings = [];
        $noJournal = $books->where('has_journal', false)->values();
        $noPresensi = $books->where('has_presensi', false)->values();
        $noPlan = $books->where('has_plan', false)->values();

        if ($noJournal->isNotEmpty()) {
            $findings[] = [
                'key' => 'no_journal',
                'title' => 'Jurnal pembelajaran belum diisi',
                'count' => $noJournal->count(),
                'description' => 'Buku yang belum memiliki satu pun entri jurnal.',
                'rows' => $noJournal->take(20),
            ];
        }

        if ($noPresensi->isNotEmpty()) {
            $findings[] = [
                'key' => 'no_presensi',
                'title' => 'Presensi/realisasi belum tercatat',
                'count' => $noPresensi->count(),
                'description' => 'Buku yang belum memiliki catatan pertemuan pada presensi mapel.',
                'rows' => $noPresensi->take(20),
            ];
        }

        if ($noPlan->isNotEmpty()) {
            $findings[] = [
                'key' => 'no_plan',
                'title' => 'Perangkat pembelajaran belum ada',
                'count' => $noPlan->count(),
                'description' => 'Belum ditemukan dokumen perangkat/RPM yang cocok.',
                'rows' => $noPlan->take(20),
            ];
        }

        return $findings;
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSummary(Collection $rows): array
    {
        $status = $rows->countBy('status');
        $total = $rows->count();
        $present = (int) ($status['hadir'] ?? 0) + (int) ($status['terlambat'] ?? 0);

        return [
            'total' => $total,
            'hadir' => (int) ($status['hadir'] ?? 0),
            'terlambat' => (int) ($status['terlambat'] ?? 0),
            'izin' => (int) ($status['izin'] ?? 0),
            'sakit' => (int) ($status['sakit'] ?? 0),
            'alpa' => (int) ($status['alpa'] ?? 0),
            'present' => $present,
            'absent' => $total - $present,
            'attendance_rate' => $total > 0 ? round($present / $total * 100, 2) : null,
            'absence_rate' => $total > 0 ? round(($total - $present) / $total * 100, 2) : null,
            'students' => $rows->pluck('student_id')->unique()->count(),
            'classes' => $rows->pluck('class_id')->filter()->unique()->count(),
            'subjects' => $rows->pluck('subject_id')->filter()->unique()->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function attendanceClassStats(Collection $rows): Collection
    {
        return $rows->groupBy('class_id')->map(function (Collection $group, string $classId) {
            $summary = $this->attendanceSummary($group);

            return [
                'id' => $classId,
                'name' => $group->first()['class_name'] ?? $classId,
                'total' => $summary['total'],
                'present' => $summary['present'],
                'alpa' => $summary['alpa'],
                'attendance_rate' => $summary['attendance_rate'],
            ];
        })->sortBy('name')->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function attendanceStudentStats(Collection $rows): Collection
    {
        return $rows->groupBy('student_id')->map(function (Collection $group, string $studentId) {
            $summary = $this->attendanceSummary($group);

            return [
                'student_id' => $studentId,
                'student_name' => $group->first()['student_name'] ?? '-',
                'nisn' => $group->first()['nisn'] ?? null,
                'class_name' => $group->first()['class_name'] ?? '-',
                'total' => $summary['total'],
                'present' => $summary['present'],
                'alpa' => $summary['alpa'],
                'izin' => $summary['izin'],
                'sakit' => $summary['sakit'],
                'attendance_rate' => $summary['attendance_rate'],
                'high_absence' => $summary['attendance_rate'] !== null && $summary['attendance_rate'] < 85,
            ];
        })->sortBy('attendance_rate')->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function attendanceMonthly(Collection $rows): Collection
    {
        return $rows->groupBy(fn (array $row) => Carbon::parse($row['date'])->format('Y-m'))
            ->sortKeys()
            ->map(function (Collection $group, string $month) {
                $summary = $this->attendanceSummary($group);

                return [
                    'month' => $month,
                    'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
                    'total' => $summary['total'],
                    'present' => $summary['present'],
                    'attendance_rate' => $summary['attendance_rate'],
                ];
            })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $studentStats
     * @param  Collection<int, array<string, mixed>>  $classStats
     * @return array<int, array<string, mixed>>
     */
    private function attendanceFindings(Collection $studentStats, Collection $classStats): array
    {
        $findings = [];
        $highAbsence = $studentStats->where('high_absence', true)->values();

        if ($highAbsence->isNotEmpty()) {
            $findings[] = [
                'key' => 'high_absence',
                'title' => 'Kehadiran di bawah 85%',
                'count' => $highAbsence->count(),
                'description' => 'Santri dengan ketidakhadiran tinggi untuk ditinjau lebih lanjut.',
                'rows' => $highAbsence->take(20),
            ];
        }

        $lowClass = $classStats->filter(fn (array $row) => $row['attendance_rate'] !== null && $row['attendance_rate'] < 85)->values();
        if ($lowClass->isNotEmpty()) {
            $findings[] = [
                'key' => 'low_class_attendance',
                'title' => 'Kelas dengan kehadiran di bawah 85%',
                'count' => $lowClass->count(),
                'description' => 'Perlu pengecekan sumber presensi dan tindak lanjut kelas.',
                'rows' => $lowClass,
            ];
        }

        return $findings;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $studentStats
     * @return array<string, mixed>
     */
    private function attendanceAcademicRelation(?string $schoolId, array $filters, Collection $studentStats): array
    {
        $bookIds = TeacherAdminBook::query()
            ->when($schoolId, fn ($q, $id) => $q->where('school_id', $id))
            ->where('academic_year_id', $filters['academic_year_id'])
            ->where('semester', $filters['semester'])
            ->when($filters['study_group_id'] ?? null, fn ($q, $id) => $q->where('study_group_id', $id))
            ->when($filters['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id))
            ->pluck('id');

        $scores = NilaiSumatif::query()
            ->whereIn('admin_book_id', $bookIds)
            ->get(['student_id', 'sts_murni', 'sas_murni'])
            ->groupBy('student_id')
            ->map(function (Collection $values) {
                $scores = $values->map(function (NilaiSumatif $row) {
                    if ($row->sas_murni !== null) {
                        return (float) $row->sas_murni;
                    }

                    return $row->sts_murni !== null ? (float) $row->sts_murni : null;
                })->filter(fn ($value) => $value !== null);

                return $scores->isEmpty() ? null : round($scores->avg(), 2);
            })
            ->filter(fn ($value) => $value !== null);

        $high = $studentStats->where('high_absence', true)->pluck('student_id');
        $low = $studentStats->where('high_absence', false)->pluck('student_id');
        $highScores = $high->map(fn ($id) => $scores->get($id))->filter(fn ($value) => $value !== null);
        $lowScores = $low->map(fn ($id) => $scores->get($id))->filter(fn ($value) => $value !== null);

        return [
            'high_count' => $highScores->count(),
            'low_count' => $lowScores->count(),
            'high_average' => $highScores->isEmpty() ? null : round($highScores->avg(), 2),
            'low_average' => $lowScores->isEmpty() ? null : round($lowScores->avg(), 2),
            'has_data' => $highScores->isNotEmpty() || $lowScores->isNotEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function withCompletionRates(array $summary): array
    {
        $total = (int) ($summary['total_rows'] ?? $summary['total'] ?? 0);
        $complete = (int) ($summary['complete'] ?? 0);
        $withKktp = (int) ($summary['with_kktp'] ?? 0);
        $below = (int) ($summary['below_kktp'] ?? 0);

        $summary['complete_rate'] = $total > 0 ? round($complete / $total * 100, 2) : null;
        $summary['missing_rate'] = $total > 0 ? round(($total - $complete) / $total * 100, 2) : null;
        $summary['mastery_rate'] = $withKktp > 0 ? round(($withKktp - $below) / $withKktp * 100, 2) : null;

        return $summary;
    }

    private function difference(?float $current, ?float $previous): ?float
    {
        return $current !== null && $previous !== null ? round($current - $previous, 2) : null;
    }
}
