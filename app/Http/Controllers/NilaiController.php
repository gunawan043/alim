<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\NilaiFormatif;
use App\Models\PenghargaanAkademik;
use App\Models\PembiasaanPagi;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Http\Request;


class NilaiController extends Controller
{
    /**
     * Halaman awal: pilih tahun ajaran, semester, tingkat
     * Menampilkan list KELAS, bukan list mapel
     * Hanya menampilkan tahun ajaran & semester yang aktif (is_active = true)
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = User::find($userId);

        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        // Hanya tahun ajaran yang aktif
        $academicYears = AcademicYear::where('is_active', true)->orderByDesc('name')->get();

        // Auto-select: TA aktif yang pertama
        $selectedAcademicYearId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $academicYears->first()?->id;

        // Auto-select semester dari request, default ganjil
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : 'ganjil';

        // Base query: TeacherAdminBook — filter semester juga
        $baseQuery = TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($selectedAcademicYearId, fn($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->when($selectedSemester, fn($q) => $q->where('semester', $selectedSemester))
            ->when(!$isPrivileged, fn($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true);

        // Dropdown tingkat (hanya dari data yang sesuai filter)
        $gradeLevelIds = (clone $baseQuery)
            ->get()
            ->pluck('studyGroup.gradeLevel')
            ->filter()
            ->unique('id')
            ->sortBy('level')
            ->values();

        $selectedGradeLevelId = $request->filled('grade_level_id') ? $request->grade_level_id : null;

        // Group by study_group (satu baris per rombel)
        $rawBooks = (clone $baseQuery)->get();

        $kelasList = $rawBooks
            ->groupBy('study_group_id')
            ->map(fn($books, $key) => [
                'study_group'   => $books->first()->studyGroup,
                'academic_year' => $books->first()->academicYear,
                'semester'      => $books->first()->semester,
                'total_mapel'   => $books->pluck('subject_id')->unique()->count(),
                'total_siswa'   => StudentClassHistory::where('study_group_id', $books->first()->study_group_id)
                                    ->where('academic_year_id', $selectedAcademicYearId)
                                    ->where('is_active', true)->count(),
                'first_book'    => $books->first(),
            ])
            ->when($selectedGradeLevelId, fn($col) => $col->filter(fn($k) =>
                $k['study_group']->gradeLevel?->id === $selectedGradeLevelId
            ))
            ->sortBy(fn($k) => $k['study_group']->gradeLevel?->level ?? 99)
            ->sortBy(fn($k) => $k['study_group']->name)
            ->values();

        return view('nilai.index', compact(
            'academicYears', 'kelasList', 'gradeLevelIds',
            'userId', 'isPrivileged', 'selectedAcademicYearId', 'selectedSemester'
        ));
    }

    /**
     * Input Nilai STS (s1-s6 + STS)
     */
    public function sts(Request $request, string $userId, string $adminBookId)
    {
        $user = User::find($userId);
        $schoolId = $request->attributes->get('schoolContextId');
        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        // adminBookId bisa integer atau UUID — cast ke integer jika numeric
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'academicYear', 'teacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isPrivileged, fn($q) => $q->where('teacher_id', $userId))
            ->where('id', $bookId)
            ->firstOrFail();

        // Siswa di kelas ini (tahun ajaran aktif)
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $adminBook->study_group_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        // Nilai sumatif yang sudah ada
        $nilaiMap = NilaiSumatif::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->pluck('rs', 'student_id');

        return view('nilai.sts', compact('userId', 'adminBook', 'students', 'nilaiMap', 'isPrivileged'));
    }

    /**
     * Simpan / Update Nilai STS (s1-s6 + STS)
     */
    public function storeSts(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*.s1' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s2' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s3' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s4' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s5' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s6' => 'nullable|numeric|min:0|max:100',
            'nilai.*.sts' => 'nullable|numeric|min:0|max:100',
        ]);

        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;
        $adminBook = TeacherAdminBook::findOrFail($bookId);

        foreach ($request->nilai as $studentId => $data) {
            $rs  = NilaiSumatif::calcRs($data);
            $rsa = NilaiSumatif::calcRsa($data['sts'] ?? null, null); // SAS belum ada
            $nrMurni = NilaiSumatif::calcNrMurni($rs, $rsa);

            NilaiSumatif::updateOrCreate(
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
                    'nr_murni' => $nrMurni,
                ]
            );
        }

        return redirect()->back()->with('success', 'Nilai STS berhasil disimpan.');
    }

    /**
     * Input Nilai SAS (SAS + NR Final + Keterangan + Formatif + Penghargaan + Pembiasaan)
     */
    public function sas(Request $request, string $userId, string $adminBookId)
    {
        $user = User::find($userId);
        $schoolId = $request->attributes->get('schoolContextId');
        $isPrivileged = $user && (
            $user->hasRole('Admin Tata Usaha') ||
            $user->hasRole('Wakil Kepala Sekolah') ||
            $user->hasRole('Kepala Sekolah Pondok')
        );

        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'academicYear', 'teacher'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isPrivileged, fn($q) => $q->where('teacher_id', $userId))
            ->where('id', $bookId)
            ->firstOrFail();

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $adminBook->study_group_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        // Data yang sudah ada
        $sumatifMap = NilaiSumatif::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->get()
            ->keyBy('student_id');

        $formatifMap = NilaiFormatif::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->get()
            ->keyBy('student_id');

        $penghargaanMap = PenghargaanAkademik::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->get()
            ->keyBy('student_id');

        $pembiasaanMap = PembiasaanPagi::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->get()
            ->keyBy('student_id');

        return view('nilai.sas', compact(
            'userId', 'adminBook', 'students',
            'sumatifMap', 'formatifMap', 'penghargaanMap', 'pembiasaanMap',
            'isPrivileged'
        ));
    }

    /**
     * Simpan / Update Nilai SAS + Formatif + Penghargaan + Pembiasaan
     */
    public function storeSas(Request $request, string $userId, string $adminBookId)
    {
        // admin_book_id uses integer type in DB
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;
        $adminBook = TeacherAdminBook::findOrFail($bookId);

        $request->validate([
            'sumatif'   => 'required|array',
            'formatif'  => 'required|array',
            'penghargaan' => 'required|array',
            'pembiasaan'  => 'nullable|array',
        ]);

        foreach ($request->sumatif as $studentId => $data) {
            // ── Nilai Sumatif (update SAS, recalculate RSA & NR) ──
            $existing = NilaiSumatif::where('admin_book_id', $bookId)
                ->where('student_id', $studentId)
                ->where('semester', $adminBook->semester)
                ->first();

            $sas    = $data['sas'] ?? null;
            $sts    = $existing?->sts;
            $rs     = $existing?->rs;
            $rsa    = NilaiSumatif::calcRsa($sts, $sas);
            $nrMurni = NilaiSumatif::calcNrMurni($rs, $rsa);

            NilaiSumatif::updateOrCreate(
                [
                    'admin_book_id' => $bookId,
                    'student_id'   => $studentId,
                    'semester'     => $adminBook->semester,
                ],
                [
                    'academic_year_id' => $adminBook->academic_year_id,
                    'sas'      => $sas,
                    'rsa'      => $rsa,
                    'nr_murni' => $nrMurni,
                    'nr_final' => $data['nr_final'] ?? null,
                    'ket'      => $data['ket'] ?? null,
                ]
            );

            // ── Nilai Formatif ──
            NilaiFormatif::updateOrCreate(
                [
                    'admin_book_id' => $bookId,
                    'student_id'   => $studentId,
                    'semester'     => $adminBook->semester,
                ],
                [
                    'academic_year_id'  => $adminBook->academic_year_id,
                    'skor_lkpd'      => $request->formatif[$studentId]['skor_lkpd'] ?? null,
                    'skor_diskusi'   => $request->formatif[$studentId]['skor_diskusi'] ?? null,
                    'skor_kuis'      => $request->formatif[$studentId]['skor_kuis'] ?? null,
                    'skor_antarteman' => $request->formatif[$studentId]['skor_antarteman'] ?? null,
                ]
            );

            // ── Penghargaan Akademik ──
            $phg = $request->penghargaan[$studentId] ?? [];
            PenghargaanAkademik::updateOrCreate(
                [
                    'admin_book_id' => $bookId,
                    'student_id'   => $studentId,
                    'semester'     => $adminBook->semester,
                ],
                [
                    'academic_year_id' => $adminBook->academic_year_id,
                    'jujur'     => $phg['jujur'] ?? null,
                    'disiplin'  => $phg['disiplin'] ?? null,
                    'peduli'    => $phg['peduli'] ?? null,
                    'adab'      => $phg['adab'] ?? null,
                    'kehadiran' => $phg['kehadiran'] ?? null,
                    'keaktifan' => $phg['keaktifan'] ?? null,
                ]
            );

            // ── Pembiasaan Pagi ──
            if ($request->has('pembiasaan')) {
                $pmb = $request->pembiasaan[$studentId] ?? [];
                PembiasaanPagi::updateOrCreate(
                    [
                        'admin_book_id' => $bookId,
                        'student_id'   => $studentId,
                        'semester'     => $adminBook->semester,
                    ],
                    [
                        'academic_year_id' => $adminBook->academic_year_id,
                        'skor_doa'         => $pmb['skor_doa'] ?? null,
                        'skor_hiwar'       => $pmb['skor_hiwar'] ?? null,
                        'skor_conversation' => $pmb['skor_conversation'] ?? null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Nilai SAS berhasil disimpan.');
    }
}
