<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\Student;
use App\Models\AdminPresensiHarian;
use App\Models\StudentClassHistory;
use App\Models\SubjectKktp;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Models\School;
use App\Exports\LegerExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class NilaiKelasController extends Controller
{
    /**
     * Halaman STS per kelas — untuk Admin TU / Waka / Wali Kelas / Kepsek
     * Menampilkan wizard: [Leger] [Mapel 1] [Mapel 2] ...
     *
     * Param: studyGroupId
     * Default: TA aktif, semester ganjil
     */
    public function sts(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = User::find($userId);

        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok') ||
            $user->hasRole('Kepala Sekolah')
        );

        // Study group
        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        // Tahun ajaran aktif
        $academicYears = AcademicYear::where('is_active', true)->orderByDesc('name')->get();
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $academicYears->first()?->id;

        // Semester
        $selectedSemester = $request->filled('semester') ? $request->semester : 'ganjil';

        // Semua mapel di kelas ini (dari TeacherAdminBook) + sorting: Agama → Arab → Hadits → Umum
        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSemester)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->sortBy(fn($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

        // Admin book yang aktif (mapel pertama jadi default tab)
        $firstBook = TeacherAdminBook::with('subject')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSemester)
            ->where('is_active', true)
            ->orderBy(function ($q) {
                $q->select('name')
                    ->from('subjects')
                    ->whereColumn('subjects.id', 'teacher_admin_books.subject_id');
            })
            ->first();

        // Tab mapel aktif: dari request atau dari first book
        $selectedBookId = $request->filled('admin_book_id')
            ? $request->admin_book_id
            : $firstBook?->id;

        // AdminBook yang sedang aktif di tab
        $activeBook = $selectedBookId
            ? TeacherAdminBook::with('subject')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->find($selectedBookId)
            : $firstBook;

        // Siswa di kelas ini
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        // Nilai STS per siswa per mapel (dari semua admin books di kelas ini)
        $nilaiMap = [];
        if ($students->isNotEmpty() && $selectedAyId) {
            $studentIds = $students->pluck('student_id')->toArray();
            $subjectIds = $subjectMap->pluck('id')->toArray();

            $nilaiRows = NilaiSumatif::whereIn('admin_book_id',
                TeacherAdminBook::whereIn('subject_id', $subjectIds)
                    ->where('study_group_id', $studyGroupId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('semester', $selectedSemester)
                    ->where('is_active', true)
                    ->pluck('id')
            )
            ->whereIn('student_id', $studentIds)
            ->where('semester', $selectedSemester)
            ->get();

            foreach ($nilaiRows as $n) {
                $nilaiMap[$n->student_id][$n->admin_book_id] = $n;
            }
        }

        // Admin books map (subject_id → book)
        $bookMap = $subjectMap->mapWithKeys(fn($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSemester)
                ->where('is_active', true)
                ->first()
        ]);

        // PRESENSI: hitung S / I / A per siswa di semester ini
        $presensiMap = [];
        if ($selectedAyId) {
            $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
                ->where('study_group_id', $studyGroupId)
                ->where('semester', $selectedSemester)
                ->get()
                ->groupBy('student_id');

            foreach ($presensiRaw as $sid => $rows) {
                $presensiMap[$sid] = [
                    's' => $rows->where('status', 'S')->count(),
                    'i' => $rows->where('status', 'I')->count(),
                    'a' => $rows->where('status', 'A')->count(),
                ];
            }
        }

        // Peringkat & Predikat (per siswa): aggregate STS per mapel → rata-rata kelas
        $legerAggMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0; $count = 0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (!$book) continue;
                $n = $nilaiMap[$sid][$book->id] ?? null;
                if ($n && $n->sts !== null) {
                    $total += $n->sts;
                    $count++;
                }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
        }

        // Hitung peringkat berdasarkan rata-rata (nilai tertinggi = rank 1)
        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) {
            $rankMap[$sid] = $rank++;
        }

        return view('nilai-kelas.sts', compact(
            'userId', 'studyGroup', 'academicYears', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'selectedAyId', 'selectedSemester',
            'selectedBookId', 'activeBook', 'isPrivileged',
            'presensiMap', 'legerAggMap', 'rankMap'
        ));
    }

    /**
     * Simpan nilai STS per mapel (inline dari leger / dari tab mapel)
     */
    public function stsStore(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // ── TAB MAPEL: simpan per mapel (S1–S6 + STS) ──
        if ($request->tab === 'mapel' && $request->filled('admin_book_id')) {
            $bookId = is_numeric($request->admin_book_id) ? (int) $request->admin_book_id : $request->admin_book_id;
            $adminBook = TeacherAdminBook::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->findOrFail($bookId);

            $nilaiData = $request->input('nilai', []);
            $savedRows = [];
            foreach ($nilaiData as $studentId => $data) {
                $rs  = NilaiSumatif::calcRs($data);
                $rsa = NilaiSumatif::calcRsa($data['sts'] ?? null, null);

                $nilai = NilaiSumatif::updateOrCreate(
                    [
                        'admin_book_id' => $bookId,
                        'student_id'    => $studentId,
                        'semester'      => $adminBook->semester,
                    ],
                    [
                        'academic_year_id' => $adminBook->academic_year_id,
                        's1'  => $data['s1'] ?? null,
                        's2'  => $data['s2'] ?? null,
                        's3'  => $data['s3'] ?? null,
                        's4'  => $data['s4'] ?? null,
                        's5'  => $data['s5'] ?? null,
                        's6'  => $data['s6'] ?? null,
                        'rs'  => $rs,
                        'sts' => $data['sts'] ?? null,
                        'rsa' => $rsa,
                        'nr_murni' => NilaiSumatif::calcNrMurni($rs, $rsa),
                    ]
                );
                $savedRows[] = [
                    'student_id' => $studentId,
                    'book_id'   => $bookId,
                    'rs'         => $nilai->rs,
                    'sts'        => $nilai->sts,
                ];
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'saved_rows' => $savedRows, 'message' => 'Nilai mapel disimpan.']);
            }

            $backUrl = route('user.schools.nilai-kelas.sts', [
                'userId'       => $userId,
                'studyGroupId' => $studyGroupId,
                'academic_year_id' => $adminBook->academic_year_id,
                'semester'     => $adminBook->semester,
                'tab'          => 'mapel',
                'admin_book_id' => $bookId,
            ]);
            return redirect($backUrl)->with('success', 'Nilai STS berhasil disimpan.');
        }

        // ── TAB LEGER: simpan STS langsung ke semua mapel ──
        if ($request->tab === 'leger' && $request->filled('leger_sts')) {
            $selectedAyId  = $request->academic_year_id;
            $selectedSem   = $request->semester ?? 'ganjil';

            // Ambil semua admin books di kelas ini
            $allBooks = TeacherAdminBook::with('subject', 'studyGroup.gradeLevel', 'kktp')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // ── Simpan KKM inline (jika ada) ──
            if ($request->filled('leger_kkm')) {
                foreach ($request->leger_kkm as $bookId => $kkmVal) {
                    $book = $allBooks->get($bookId);
                    if (!$book || $kkmVal === null) continue;

                    $existing = SubjectKktp::where('subject_id', $book->subject_id)
                        ->where('school_id', $schoolId)
                        ->where('grade_level_id', $book->studyGroup->gradeLevel->id ?? null)
                        ->where('academic_year_id', $selectedAyId)
                        ->where('semester', $selectedSem)
                        ->first();

                    if ($existing) {
                        $existing->updateQuietly(['kkm_score' => $kkmVal]);
                        $kktpId = $existing->id;
                    } else {
                        $kktp = SubjectKktp::create([
                            'subject_id'       => $book->subject_id,
                            'school_id'       => $schoolId,
                            'grade_level_id'  => $book->studyGroup->gradeLevel->id ?? null,
                            'academic_year_id' => $selectedAyId,
                            'semester'        => $selectedSem,
                            'kkm_score'       => $kkmVal,
                            'created_by'      => $userId,
                        ]);
                        $kktpId = $kktp->id;
                    }

                    // Update kktp_id di book
                    $book->updateQuietly(['kktp_id' => $kktpId]);
                }
            }

            $savedRows = [];
            foreach ($request->leger_sts as $studentId => $books) {
                foreach ($books as $bookId => $fields) {
                    $book = $allBooks->get((string) $bookId);
                    if (!$book) continue;

                    $nilai = NilaiSumatif::updateOrCreate(
                        [
                            'admin_book_id' => (string) $bookId,
                            'student_id'    => $studentId,
                            'semester'      => $selectedSem,
                        ],
                        [
                            'academic_year_id' => $selectedAyId,
                            'sts' => $fields['sts'] ?? null,
                        ]
                    );
                    $savedRows[] = [
                        'student_id' => $studentId,
                        'book_id'    => $bookId,
                        'sts'        => $nilai->sts,
                    ];
                }
            }

            // ── AJAX: auto-save ──
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'    => true,
                    'saved_rows' => $savedRows,
                    'message'    => 'Leger disimpan.',
                ]);
            }

            $backUrl = route('user.schools.nilai-kelas.sts', [
                'userId'       => $userId,
                'studyGroupId' => $studyGroupId,
                'academic_year_id' => $selectedAyId,
                'semester'     => $selectedSem,
                'tab'          => 'leger',
            ]);
            return redirect($backUrl)->with('success', 'Leger berhasil disimpan.');
        }

        // Fallback
        return redirect()->back()->with('error', 'Data tidak valid.');
    }

    /**
     * Cetak Leger — print-ready view (tanpa input)
     */
    public function legerCetak(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);

        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->sortBy(fn($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

        $bookMap = $subjectMap->mapWithKeys(fn($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)
                ->first()
        ]);

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        $nilaiMap = [];
        if ($students->isNotEmpty() && $selectedAyId) {
            $studentIds = $students->pluck('student_id')->toArray();
            $subjectIds = $subjectMap->pluck('id')->toArray();
            $nilaiRows = NilaiSumatif::whereIn('admin_book_id',
                TeacherAdminBook::whereIn('subject_id', $subjectIds)
                    ->where('study_group_id', $studyGroupId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('semester', $selectedSem)
                    ->where('is_active', true)->pluck('id')
            )->whereIn('student_id', $studentIds)->where('semester', $selectedSem)->get();

            foreach ($nilaiRows as $n) {
                $nilaiMap[$n->student_id][$n->admin_book_id] = $n;
            }
        }

        $presensiMap = [];
        if ($selectedAyId) {
            $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
                ->where('study_group_id', $studyGroupId)
                ->where('semester', $selectedSem)->get()->groupBy('student_id');
            foreach ($presensiRaw as $sid => $rows) {
                $presensiMap[$sid] = [
                    's' => $rows->where('status', 'S')->count(),
                    'i' => $rows->where('status', 'I')->count(),
                    'a' => $rows->where('status', 'A')->count(),
                ];
            }
        }

        $legerAggMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0; $count = 0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (!$book) continue;
                $n = $nilaiMap[$sid][$book->id] ?? null;
                if ($n && $n->sts !== null) {
                    $total += $n->sts; $count++;
                }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
        }

        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) { $rankMap[$sid] = $rank++; }

        return view('nilai-kelas.leger-cetak', compact(
            'userId', 'studyGroup', 'academicYears', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'selectedAyId', 'selectedSem', 'selectedAy',
            'presensiMap', 'legerAggMap', 'rankMap',
        ));
    }

    /**
     * Download Leger dalam format Excel
     */
    public function legerDownload(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

        $selectedAy = AcademicYear::orderByDesc('name')->firstWhere('id', $selectedAyId);

        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->sortBy(fn($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

        $bookMap = $subjectMap->mapWithKeys(fn($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)->first()
        ]);

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $nilaiMap = [];
        if ($students->isNotEmpty() && $selectedAyId) {
            $studentIds = $students->pluck('student_id')->toArray();
            $subjectIds = $subjectMap->pluck('id')->toArray();
            $nilaiRows = NilaiSumatif::whereIn('admin_book_id',
                TeacherAdminBook::whereIn('subject_id', $subjectIds)
                    ->where('study_group_id', $studyGroupId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('semester', $selectedSem)
                    ->where('is_active', true)->pluck('id')
            )->whereIn('student_id', $studentIds)->where('semester', $selectedSem)->get();
            foreach ($nilaiRows as $n) {
                $nilaiMap[$n->student_id][$n->admin_book_id] = $n;
            }
        }

        $presensiMap = [];
        if ($selectedAyId) {
            $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
                ->where('study_group_id', $studyGroupId)
                ->where('semester', $selectedSem)->get()->groupBy('student_id');
            foreach ($presensiRaw as $sid => $rows) {
                $presensiMap[$sid] = [
                    's' => $rows->where('status', 'S')->count(),
                    'i' => $rows->where('status', 'I')->count(),
                    'a' => $rows->where('status', 'A')->count(),
                ];
            }
        }

        $legerAggMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0; $count = 0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (!$book) continue;
                $n = $nilaiMap[$sid][$book->id] ?? null;
                if ($n && $n->sts !== null) { $total += $n->sts; $count++; }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
        }

        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) { $rankMap[$sid] = $rank++; }

        $ayName = str_replace(['/', ' '], '-', $selectedAy?->name ?? '');
        $filename = 'Leger-STS-' . str_replace(' ', '-', $studyGroup->name)
            . '-' . strtoupper($selectedSem)
            . '-' . $ayName . '.xlsx';

        return Excel::download(new LegerExport(compact(
            'studyGroup', 'selectedAy', 'selectedSem', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'legerAggMap', 'rankMap', 'presensiMap'
        )), $filename);
    }

    /**
     * Daftar Santri — pilih untuk cetak rapor
     */
    public function rapor(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        return view('nilai-kelas.rapor-index', compact(
            'userId', 'studyGroup', 'academicYears', 'selectedAyId', 'selectedSem', 'selectedAy', 'students',
        ));
    }

    /**
     * Cetak Rapor per Santri — PDF download
     */
    public function raporCetak(Request $request, string $userId, string $studyGroupId, string $studentId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;
        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);

        $student = Student::findOrFail($studentId);

        // Semua mapel di kelas ini
        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->sortBy(fn($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

        $bookMap = $subjectMap->mapWithKeys(fn($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)->first()
        ]);

        // Nilai STS student
        $nilaiMap = [];
        $studentNilaiRows = NilaiSumatif::whereIn('admin_book_id', $bookMap->pluck('id'))
            ->where('student_id', $studentId)
            ->where('semester', $selectedSem)
            ->get();

        foreach ($studentNilaiRows as $n) {
            $nilaiMap[$n->admin_book_id] = $n;
        }

        // Presensi
        $presensiRaw = AdminPresensiHarian::where('academic_year_id', $selectedAyId)
            ->where('study_group_id', $studyGroupId)
            ->where('student_id', $studentId)
            ->where('semester', $selectedSem)->get();

        $sCount = $presensiRaw->where('status', 'S')->count();
        $iCount = $presensiRaw->where('status', 'I')->count();
        $aCount = $presensiRaw->where('status', 'A')->count();

        // Bangun data mapel baris per baris
        $mapelRows = [];
        $jumlahNilai = 0;
        $jumlahMapel = 0;

        foreach ($subjectMap as $idx => $subject) {
            $book = $bookMap[$subject->id] ?? null;
            $kkm = $book?->kktp?->kkm_score ?? 75;
            $n = $nilaiMap[$book->id] ?? null;
            $sts = $n?->sts ?? null;

            if ($sts !== null) {
                $jumlahNilai += $sts;
                $jumlahMapel++;
            }

            if ($sts !== null && is_numeric($sts)) {
                if ($sts > $kkm) {
                    $keterangan = 'Terlampaui';
                } elseif ($sts >= $kkm) {
                    $keterangan = 'Tercapai';
                } else {
                    $keterangan = 'Belum Tuntas';
                }
            } else {
                $keterangan = '';
            }

            $mapelRows[] = [
                'no'        => $idx + 1,
                'mapel'     => $subject->name,
                'kkm'       => $kkm,
                'nilai'     => $sts !== null ? number_format($sts, 1) : '',
                'keterangan'=> $keterangan,
            ];
        }

        $rata = $jumlahMapel > 0 ? round($jumlahNilai / $jumlahMapel, 1) : null;

        if ($rata === null) $predikat = '—';
        elseif ($rata >= 95) $predikat = "Mumtaz Murtafi'";
        elseif ($rata >= 90) $predikat = 'Mumtaz';
        elseif ($rata >= 85) $predikat = 'Jayyid Jiddan';
        elseif ($rata >= 80) $predikat = 'Jayyid';
        elseif ($rata >= 75) $predikat = 'Maqbul';
        else $predikat = 'Roosib';

        // Susun $santri array — format sesuai template
        $santri[$studentId] = [
            'Nama'        => $student->name,
            'NIS'         => $student->nis ?? '-',
            'NISN'        => $student->nisn ?? '-',
            'Kelas'       => $studyGroup->name,
            'Semester'    => ucfirst($selectedSem),
            'TahunAjaran' => $selectedAy?->name ?? '-',
            'Mapel'       => $mapelRows,
            'Jumlah'      => $jumlahMapel > 0 ? number_format($jumlahNilai, 1) : '-',
            'Rata'        => $rata !== null ? number_format($rata, 1) : '-',
            'Predikat'    => $predikat,
            'Sakit'       => $sCount,
            'Izin'        => $iCount,
            'Alpa'        => $aCount,
        ];

        // Baca kop surat dari storage (school->kop_path)
        $kopBase64 = null;
        $school = $studyGroup->school;
        if ($school && $school->kop_path) {
            $kopAbsolute = storage_path('app/public/' . $school->kop_path);
            if (file_exists($kopAbsolute)) {
                $ext = pathinfo($kopAbsolute, PATHINFO_EXTENSION) ?: 'png';
                $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';
                $kopBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($kopAbsolute));
            }
        }

        $html = view('nilai-kelas.rapor-cetak', compact(
            'santri', 'studyGroup', 'kopBase64',
        ))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Rapor-' . str_replace(' ', '-', $student->name)
            . '-' . str_replace(' ', '-', $studyGroup->name)
            . '-' . strtoupper($selectedSem)
            . '-' . str_replace(['/', ' '], '-', $selectedAy?->name ?? '') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Sort order mapel: Agama → Arab → Hadits → Umum
     */
    private function subjectSortOrder(string $name, string $category): array
    {
        $nameLower = strtolower($name);

        // Agensi: mapel agama
        $isAgama = preg_match('/(aqidah|adab|fiqih|tahfidz|hafalan hadits)/i', $nameLower);
        $isArab  = preg_match('/(bahasa arab|b\.? ?arab|qowaid|ta[\'\"]?bir|sharaf)/i', $nameLower);
        $isHadits = preg_match('/(hadits|hadist)/i', $nameLower);

        if ($isAgama) $group = 1;
        elseif ($isArab) $group = 2;
        elseif ($isHadits) $group = 3;
        else $group = 4;

        return [$group, $nameLower];
    }
}