<?php

namespace App\Http\Controllers;

use App\Models\AdminPresensiHarian;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\AcademicYear;
use App\Exports\AbsensiRecapExport;
use App\Exports\AbsensiDetailExport;
use App\Exports\AbsensiSemesterFullExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class AbsensiHarianController extends Controller
{
    // ── Helpers ───────────────────────────────────────────────────

    protected function getSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId');
    }

    protected function getActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('is_active', true)->first();
    }

    /**
     * Cek apakah user adalah wali kelas dari rombel tertentu.
     */
    protected function isWaliKelasOf(Request $request, string $studyGroupId): bool
    {
        $user = Auth::user();
        $studyGroup = StudyGroup::find($studyGroupId);
        return $studyGroup && $studyGroup->homeroom_teacher_id === $user->id;
    }

    /**
     * Cek apakah user adalah Admin TU atau Waka.
     */
    protected function isAdminOrWaka(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('Admin Tata Usaha') || $user->hasRole('Tata Usaha') || $user->hasRole('Wadir 1') || $user->hasRole('Wadir 2') || $user->hasRole('Wakil Kepala Sekolah') || $user->hasRole('Kepala Sekolah') || $user->hasRole('Kepala Sekolah Pondok'));
    }

    // ── INDEX — Dashboard Absensi Harian ────────────────────────

    public function index(Request $request, string $userId)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId($request);
        $activeYear = $this->getActiveAcademicYear();

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear?->semester ?? 'ganjil';

        // Admin TU / Waka: semua rombel semua sekolah. Wali Kelas: rombelnya sendiri.
        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        $studyGroups = StudyGroup::withoutGlobalScope('school_context')
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(!$isPrivileged, fn($q) => $q->where('homeroom_teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        // Stats per rombel untuk tanggal terpilih
        $rombelStats = [];
        foreach ($studyGroups as $sg) {
            $totalSiswa = \App\Models\StudentClassHistory::withoutGlobalScope('school_context')
                ->where('study_group_id', $sg->id)
                ->where('academic_year_id', $activeYear?->id)
                ->where('is_active', true)
                ->count();

            if ($totalSiswa === 0) {
                $rombelStats[$sg->id] = null;
                continue;
            }

            $stats = AdminPresensiHarian::where('study_group_id', $sg->id)
                ->where('academic_year_id', $activeYear?->id)
                ->where('semester', $selectedSemester)
                ->whereDate('attendance_date', $selectedDate)
                ->selectRaw("
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                    COUNT(DISTINCT student_id) as recorded
                ")
                ->first();

            $rombelStats[$sg->id] = [
                'total' => $totalSiswa,
                'hadir' => $stats->hadir ?? 0,
                'terlambat' => $stats->terlambat ?? 0,
                'izin' => $stats->izin ?? 0,
                'sakit' => $stats->sakit ?? 0,
                'alpa' => $stats->alpa ?? 0,
                'recorded' => $stats->recorded ?? 0,
            ];
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('absensi-harian.index', compact(
            'userId', 'studyGroups', 'rombelStats',
            'selectedDate', 'selectedSemester',
            'activeYear', 'academicYears',
        ));
    }

    // ── CREATE — Form Input Absensi ─────────────────────────────

    public function create(Request $request, string $userId)
    {
        $schoolId = $this->getSchoolId($request);
        $activeYear = $this->getActiveAcademicYear();

        if (!$activeYear) {
            return redirect()
                ->route('user.absensi.harian.index', ['userId' => $userId])
                ->with('error', 'Tidak ada tahun ajaran yang aktif.');
        }

        $user = Auth::user();
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();
        $selectedStudyGroupId = $request->filled('study_group_id') ? $request->study_group_id : null;
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear->semester;
        $inputMode = $request->input('mode', 'dropdown');

        // Admin TU / Waka: semua rombel semua sekolah. Wali Kelas: rombelnya sendiri.
        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        // Query StudyGroup langsung — bypass global scope
        $studyGroupQuery = \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name');

        if (!$isPrivileged) {
            $studyGroupQuery->where('homeroom_teacher_id', $user->id);
        }

        $studyGroups = $studyGroupQuery->get();

        // Validasi rombel yang dipilih
        if ($selectedStudyGroupId && !$studyGroups->contains('id', $selectedStudyGroupId)) {
            $selectedStudyGroupId = null;
        }

        $students = collect();
        $existingRecords = collect();

        if ($selectedStudyGroupId && $activeYear) {
            $histories = \App\Models\StudentClassHistory::withoutGlobalScope('school_context')
                ->with('student')
                ->where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->orderBy('attendance_number')
                ->get();

            $students = $histories->pluck('student');

            $existingRecords = \App\Models\AdminPresensiHarian::where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $activeYear->id)
                ->where('semester', $selectedSemester)
                ->whereDate('attendance_date', $selectedDate)
                ->get()
                ->keyBy('student_id');
        }

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('absensi-harian.create', compact(
            'userId', 'studyGroups', 'students', 'existingRecords',
            'selectedDate', 'selectedStudyGroupId', 'selectedSemester',
            'activeYear', 'academicYears', 'inputMode',
        ));
    }

    // ── STORE — Simpan Absensi ───────────────────────────────────

    public function store(Request $request, string $userId)
    {
        $schoolId = $this->getSchoolId($request);
        $activeYear = $this->getActiveAcademicYear();

        if (!$activeYear) {
            return redirect()
                ->route('user.absensi.harian.index', ['userId' => $userId])
                ->with('error', 'Tidak ada tahun ajaran aktif. Silakan aktifkan tahun ajaran terlebih dahulu.');
        }

        $validated = $request->validate([
            'study_group_id'   => 'required|exists:study_groups,id',
            'attendance_date'  => 'required|date',
            'semester'         => 'required|in:ganjil,genap',
            'records'          => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status'  => 'required|in:hadir,terlambat,izin,sakit,alpa',
            'records.*.notes'  => 'nullable|string|max:255',
        ]);

        // Otorisasi: hanya Admin TU / Waka / Wali Kelas rombel tsb
        if (!$this->isAdminOrWaka() && !$this->isWaliKelasOf($request, $validated['study_group_id'])) {
            abort(403, 'Anda bukan wali kelas rombel ini.');
        }

        $inputMode = $request->input('mode', 'dropdown');
        $date = Carbon::parse($validated['attendance_date']);
        $records = $validated['records'];

        DB::transaction(function () use ($records, $validated, $date, $activeYear, $schoolId) {
            foreach ($records as $record) {
                AdminPresensiHarian::updateOrCreate(
                    [
                        'study_group_id'   => $validated['study_group_id'],
                        'academic_year_id' => $activeYear->id,
                        'semester'         => $validated['semester'],
                        'attendance_date'  => $date->toDateString(),
                        'student_id'       => $record['student_id'],
                    ],
                    [
                        'status'  => $record['status'],
                        'notes'   => $record['notes'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('user.absensi.harian.create', [
                'userId'         => $userId,
                'study_group_id' => $validated['study_group_id'],
                'date'           => $date->toDateString(),
                'semester'       => $validated['semester'],
                'mode'           => $inputMode,
            ])
            ->with('success', 'Absensi berhasil disimpan.');
    }

    // ── RECAP BULANAN — Tabel dengan Kolom Tanggal 1-31 ─────────

    public function recapDetail(Request $request, string $userId)
    {
        $schoolId = $this->getSchoolId($request);
        $user = Auth::user();
        $activeYear = $this->getActiveAcademicYear();

        $selectedStudyGroupId = $request->filled('study_group_id') ? $request->study_group_id : null;
        $selectedMonth = $request->filled('month') ? (int) $request->month : (int) now()->month;
        $selectedYear  = $request->filled('year')  ? (int) $request->year  : (int) now()->year;
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear?->semester ?? 'ganjil';

        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        $studyGroups = StudyGroup::withoutGlobalScope('school_context')
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(!$isPrivileged, fn($q) => $q->where('homeroom_teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::orderByDesc('name')->get();

        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $studentRows = collect();
        $dateMap = []; // [student_id][date_string] => status

        if ($selectedStudyGroupId && $activeYear) {
            $histories = \App\Models\StudentClassHistory::withoutGlobalScope('school_context')
                ->with('student')
                ->where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->orderBy('attendance_number')
                ->get();

            // Ambil semua record absensi bulan tersebut dalam 1 query
            $allRecords = AdminPresensiHarian::where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $activeYear->id)
                ->where('semester', $selectedSemester)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            foreach ($allRecords as $rec) {
                $dateMap[$rec->student_id][$rec->attendance_date->toDateString()] = $rec;
            }

            foreach ($histories as $history) {
                $studentRows->push($history->student);
            }
        }

        $months = collect(range(1, 12))->map(fn($m) => [
            'value' => $m,
            'label' => Carbon::create(2024, $m, 1)->locale('id')->monthName,
        ]);

        // ── Export Excel
        if ($request->filled('export')) {
            $studyGroup = $studyGroups->firstWhere('id', $selectedStudyGroupId);
            $rombelName    = $studyGroup ? $studyGroup->full_name : 'Semua Rombel';
            $homeroomName  = $studyGroup?->homeroomTeacher?->name ?? '';
            $schoolName   = $studyGroup?->school?->name ?? '';

            $months = $selectedSemester === 'ganjil'
                ? [7, 8, 9, 10, 11, 12]
                : [1, 2, 3, 4, 5, 6];
            $year = $selectedYear;

            $groupedData = [];
            foreach ($months as $month) {
                $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
                $monthEnd   = $monthStart->copy()->endOfMonth();
                $monthRecords = AdminPresensiHarian::where('study_group_id', $selectedStudyGroupId)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('semester', $selectedSemester)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->get();
                foreach ($monthRecords as $rec) {
                    $groupedData[$month][$rec->student_id][$rec->attendance_date->toDateString()] = $rec;
                }
            }

            return Excel::download(
                new AbsensiSemesterFullExport(
                    $studentRows,
                    collect($groupedData),
                    $rombelName,
                    $homeroomName,
                    $schoolName,
                    $selectedSemester,
                    $activeYear?->name ?? '',
                    $year,
                ),
                "rekap-absensi-semester-{$rombelName}-" . ucfirst($selectedSemester) . ".xlsx"
            );
        }

        return view('absensi-harian.recap-detail', compact(
            'userId', 'studyGroups', 'studentRows', 'dateMap',
            'selectedStudyGroupId', 'selectedMonth', 'selectedYear', 'selectedSemester',
            'months', 'academicYears', 'startDate', 'endDate', 'daysInMonth',
        ));
    }

    /**
     * Build data untuk export detail (kolom tanggal).
     */
    protected function buildRecapDetailData($studentRows, $dateMap, Carbon $startDate, int $daysInMonth): \Illuminate\Support\Collection
    {
        $rows = collect();
        foreach ($studentRows as $student) {
            $row = collect([
                $student->nis ?? '-',
                $student->name,
                $student->gender,
            ]);

            $totalS = 0; $totalI = 0; $totalA = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = $startDate->copy()->day($d)->toDateString();
                $record = $dateMap[$student->id][$dateStr] ?? null;
                $symbol = match ($record?->status) {
                    'hadir'     => 'H',
                    'terlambat' => 'T',
                    'izin'      => 'I',
                    'sakit'     => 'S',
                    'alpa'      => 'A',
                    default     => '·',
                };
                if ($record?->status === 'sakit') $totalS++;
                elseif ($record?->status === 'izin') $totalI++;
                elseif ($record?->status === 'alpa') $totalA++;
                $row->push($symbol);
            }

            $row->push($totalS);
            $row->push($totalI);
            $row->push($totalA);

            $rows->push($row);
        }
        return $rows;
    }

    // ── RECAP BULANAN LAMA (summary Excel) — masih dipertahankan ──

    public function recap(Request $request, string $userId)
    {
        $schoolId = $this->getSchoolId($request);
        $user = Auth::user();
        $activeYear = $this->getActiveAcademicYear();

        $selectedStudyGroupId = $request->filled('study_group_id') ? $request->study_group_id : null;
        $selectedMonth = $request->filled('month') ? (int) $request->month : (int) now()->month;
        $selectedYear  = $request->filled('year')  ? (int) $request->year  : (int) now()->year;
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear?->semester ?? 'ganjil';

        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Tata Usaha') ||
            $user->hasRole('Wadir 1') ||
            $user->hasRole('Wadir 2') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        $studyGroups = StudyGroup::withoutGlobalScope('school_context')
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(!$isPrivileged, fn($q) => $q->where('homeroom_teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::orderByDesc('name')->get();

        // ── Build data rekap
        $rekapData = collect();
        if ($selectedStudyGroupId && $activeYear) {
            $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
            $endDate   = $startDate->copy()->endOfMonth();

            $histories = \App\Models\StudentClassHistory::withoutGlobalScope('school_context')
                ->with('student')
                ->where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->orderBy('attendance_number')
                ->get();

            foreach ($histories as $history) {
                $student = $history->student;
                $records = AdminPresensiHarian::where('student_id', $student->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->where('semester', $selectedSemester)
                    ->whereBetween('attendance_date', [$startDate, $endDate])
                    ->get();

                $rekapData->push([
                    'nis'         => $student->nis,
                    'name'        => $student->name,
                    'gender'      => $student->gender,
                    'hadir'       => $records->where('status', 'hadir')->count(),
                    'terlambat'   => $records->where('status', 'terlambat')->count(),
                    'izin'        => $records->where('status', 'izin')->count(),
                    'sakit'       => $records->where('status', 'sakit')->count(),
                    'alpa'        => $records->where('status', 'alpa')->count(),
                ]);
            }
        }

        // ── Export Excel jika diminta
        if ($request->filled('export')) {
            $studyGroup = $studyGroups->firstWhere('id', $selectedStudyGroupId);
            $monthName  = Carbon::create($selectedYear, $selectedMonth, 1)->locale('id')->monthName;
            $rombelName = $studyGroup ? $studyGroup->full_name : 'Semua Rombel';
            $monthYear  = "{$monthName} {$selectedYear}";

            return Excel::download(
                new AbsensiRecapExport($rekapData, $rombelName, $monthYear, ucfirst($selectedSemester)),
                "rekap-absensi-{$rombelName}-{$monthYear}.xlsx"
            );
        }

        $months = collect(range(1, 12))->map(fn($m) => [
            'value' => $m,
            'label' => Carbon::create(2024, $m, 1)->locale('id')->monthName,
        ]);

        return view('absensi-harian.recap', compact(
            'userId', 'studyGroups', 'rekapData',
            'selectedStudyGroupId', 'selectedMonth', 'selectedYear',
            'selectedSemester', 'months', 'academicYears',
        ));
    }

    // ── RECAP SEMESTER — Rekap Semester (Excel) ───────────────────

    public function recapSemester(Request $request, string $userId)
    {
        $schoolId = $this->getSchoolId($request);
        $user = Auth::user();
        $activeYear = $this->getActiveAcademicYear();

        $selectedStudyGroupId = $request->filled('study_group_id') ? $request->study_group_id : null;
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $activeYear?->id;
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear?->semester ?? 'ganjil';

        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Tata Usaha') ||
            $user->hasRole('Wadir 1') ||
            $user->hasRole('Wadir 2') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        $studyGroups = StudyGroup::withoutGlobalScope('school_context')
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when(!$isPrivileged, fn($q) => $q->where('homeroom_teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::orderByDesc('name')->get();

        $rekapData = collect();
        $studentRows = collect();
        $groupedData = [];
        if ($selectedStudyGroupId && $selectedAyId) {
            $selectedAy = $academicYears->firstWhere('id', $selectedAyId);

            $histories = \App\Models\StudentClassHistory::withoutGlobalScope('school_context')
                ->with('student')
                ->where('study_group_id', $selectedStudyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('is_active', true)
                ->orderBy('attendance_number')
                ->get();

            $months = $selectedSemester === 'ganjil'
                ? [7, 8, 9, 10, 11, 12]
                : [1, 2, 3, 4, 5, 6];
            $year = (int) substr($selectedAy?->name ?? '', 0, 4);
            $studentIds = $histories->pluck('student.id')->toArray();

            // Bulk fetch semua record absensi semester untuk semua siswa
            $allRecords = AdminPresensiHarian::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSemester)
                ->get();

            foreach ($histories as $history) {
                $student = $history->student;
                $studentRows->push($student);
                $sid = $student->id;

                foreach ($months as $month) {
                    $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
                    $monthEnd   = $monthStart->copy()->endOfMonth();
                    $groupedData[$month][$sid] = $allRecords
                        ->filter(fn($r) => $r->student_id === $sid && $r->attendance_date->between($monthStart, $monthEnd))
                        ->keyBy(fn($r) => $r->attendance_date->toDateString());
                }

                $rekapData->push([
                    'nis'         => $student->nis,
                    'name'        => $student->name,
                    'gender'      => $student->gender,
                    'hadir'       => $allRecords->where('student_id', $sid)->where('status', 'hadir')->count(),
                    'terlambat'   => $allRecords->where('student_id', $sid)->where('status', 'terlambat')->count(),
                    'izin'        => $allRecords->where('student_id', $sid)->where('status', 'izin')->count(),
                    'sakit'       => $allRecords->where('student_id', $sid)->where('status', 'sakit')->count(),
                    'alpa'        => $allRecords->where('student_id', $sid)->where('status', 'alpa')->count(),
                ]);
            }
        }

        // ── Export Excel
        if ($request->filled('export')) {
            $studyGroup = $studyGroups->firstWhere('id', $selectedStudyGroupId);
            $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
            $rombelName = $studyGroup ? $studyGroup->full_name : 'Semua Rombel';
            $schoolName = $studyGroup?->school?->name ?? '';
            $homeroomName = $studyGroup?->homeroomTeacher?->name ?? '';
            $academicYearName = $selectedAy?->name ?? '';

            return Excel::download(
                new AbsensiSemesterFullExport(
                    $studentRows,
                    collect($groupedData),
                    $rombelName,
                    $homeroomName,
                    $schoolName,
                    $selectedSemester,
                    $academicYearName,
                    $year,
                ),
                "rekap-absensi-semester-{$rombelName}-" . ucfirst($selectedSemester) . ".xlsx"
            );
        }

        return view('absensi-harian.recap-semester', compact(
            'userId', 'studyGroups', 'rekapData',
            'selectedStudyGroupId', 'selectedAyId', 'selectedSemester',
            'academicYears',
        ));
    }

    // ── SHOW — Detail Per Siswa ──────────────────────────────────

    public function show(Request $request, string $userId, string $studentUuid)
    {
        $schoolId = $this->getSchoolId($request);
        $activeYear = $this->getActiveAcademicYear();

        $student = Student::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studentUuid);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $activeYear?->id;
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : $activeYear?->semester ?? 'ganjil';
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : $activeYear?->start_date ?? Carbon::today()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::today();

        $records = AdminPresensiHarian::with('studyGroup')
            ->where('student_id', $studentUuid)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSemester)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date')
            ->paginate(31)
            ->withQueryString();

        // Summary stats
        $stats = AdminPresensiHarian::where('student_id', $studentUuid)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSemester)
            ->selectRaw("
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                COUNT(*) as total
            ")
            ->first();

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('absensi-harian.show', compact(
            'userId', 'student', 'records', 'stats',
            'selectedAyId', 'selectedSemester', 'startDate', 'endDate',
            'academicYears',
        ));
    }
}
