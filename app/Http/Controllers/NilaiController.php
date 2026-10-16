<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Models\AcademicYear;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PembiasaanPagi;
use App\Models\PenghargaanAkademik;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Services\KurikulumAccess;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use App\Services\SumatifHarianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NilaiController extends Controller
{
    use AuthorizesAcademicScope;

    /**
     * Halaman awal: pilih tahun ajaran, semester, tingkat
     * Menampilkan list KELAS, bukan list mapel
     * Hanya menampilkan tahun ajaran & semester yang aktif (is_active = true)
     */
    /**
     * Kewenangan penuh Data Nilai: KSP, Waka, Kurikulum, TU (atau permission/super admin).
     * Guru mapel & wali kelas tetap dibatasi pada penugasannya.
     */
    protected function isNilaiPrivileged(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        if (canPermission('nilai-admin-tu')
            || canPermission('nilai-wakil-kepala-sekolah')
            || canPermission('nilai-kepala-sekolah-pondok')
            || canPermission('nilai-kepala-sekolah')
            || canPermission('nilai-koordinator-kurikulum')) {
            return true;
        }

        // Jabatan dibaca dari semua sumber + varian nama TU (selaras dengan leger).
        if (\App\Support\UserJabatan::hasAny($user, [
            'kepala satuan pendidikan',
            'wakil kepala satuan pendidikan',
            'kepala tata usaha',
            'tata usaha',
            'staf tata usaha',
            'koordinator kurikulum',
            'kepala tu sekolah',
            'tu sekolah',
            'staf tu sekolah',
            'tata usaha sekolah',
        ])) {
            return true;
        }

        $tugas = method_exists($user, 'tugasTambahan') ? $user->tugasTambahan->pluck('nama')->all() : [];

        return in_array('Tim Kurikulum', $tugas, true);
    }

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = User::find($userId);

        $isPrivileged = $this->isNilaiPrivileged($user);

        // Wali kelas → langsung ke Leger kelasnya (bukan halaman Data Nilai umum).
        // Guru mapel → Buku Nilai Saya. Data Nilai umum hanya untuk KSP/Waka/Kurikulum/TU.
        if (! $isPrivileged) {
            $activeAyId = AcademicYear::where('is_active', true)->value('id');

            $waliGroup = StudyGroup::where('homeroom_teacher_id', $user?->id)
                ->where('is_active', true)
                ->when($activeAyId, fn ($q) => $q->where('academic_year_id', $activeAyId))
                ->orderedByGrade()
                ->first();

            if ($waliGroup) {
                return redirect()->route('user.schools.nilai-kelas.sts', [
                    'userId' => $userId,
                    'studyGroupId' => $waliGroup->id,
                ]);
            }

            if ($user && TeacherAdminBook::where('teacher_id', $user->id)->where('is_active', true)->exists()) {
                return redirect()->route('user.schools.guru-mapel.index', ['userId' => $userId]);
            }

            abort(403, 'Data Nilai hanya untuk KSP, Waka, Kurikulum, dan TU. Guru mapel menggunakan Buku Nilai Saya.');
        }

        // ARSIP PERIODE: seluruh tahun ajaran dapat dipilih (termasuk TA lama).
        // TA aktif tetap menjadi default; TA lama bersifat arsip (tidak dapat diubah).
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAcademicYearId = $academicYears->firstWhere('is_active', true)?->id;

        // Auto-select: TA aktif bila ada, jika tidak TA terbaru.
        $selectedAcademicYearId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : ($activeAcademicYearId ?? $academicYears->first()?->id);

        $isArchiveAy = $selectedAcademicYearId && $activeAcademicYearId
            && (string) $selectedAcademicYearId !== (string) $activeAcademicYearId;

        // Auto-select semester dari request, default ganjil
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : 'ganjil';

        // Base query: TeacherAdminBook — filter semester juga
        $baseQuery = TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->when($selectedSemester, fn ($q) => $q->where('semester', $selectedSemester))
            ->when(! $isPrivileged, fn ($q) => $q->where('teacher_id', $userId))
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
            ->map(fn ($books, $key) => [
                'study_group' => $books->first()->studyGroup,
                'academic_year' => $books->first()->academicYear,
                'semester' => $books->first()->semester,
                'total_mapel' => $books->pluck('subject_id')->unique()->count(),
                'total_siswa' => StudentClassHistory::where('study_group_id', $books->first()->study_group_id)
                    ->where('academic_year_id', $selectedAcademicYearId)
                    ->when(
                        $selectedAcademicYearId && (string) $selectedAcademicYearId === (string) $activeAcademicYearId,
                        fn ($q) => $q->where('is_active', true)
                    )->count(),
                'first_book' => $books->first(),
            ])
            ->when($selectedGradeLevelId, fn ($col) => $col->filter(fn ($k) => $k['study_group']->gradeLevel?->id === $selectedGradeLevelId
            ))
            ->sortBy(fn ($k) => $k['study_group']->gradeLevel?->level ?? 99)
            ->sortBy(fn ($k) => $k['study_group']->name)
            ->values();

        return view('nilai.index', compact(
            'academicYears', 'kelasList', 'gradeLevelIds',
            'userId', 'isPrivileged', 'selectedAcademicYearId', 'selectedSemester',
            'activeAcademicYearId', 'isArchiveAy'
        ));
    }

    /**
     * Input Nilai STS (s1-s6 + STS)
     */
    public function sts(Request $request, string $userId, string $adminBookId)
    {
        $user = User::find($userId);
        $schoolId = $request->attributes->get('schoolContextId');
        $isPrivileged = $this->isNilaiPrivileged($user);

        // adminBookId bisa integer atau UUID — cast ke integer jika numeric
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'academicYear', 'teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isPrivileged, fn ($q) => $q->where('teacher_id', $userId))
            ->where('id', $bookId)
            ->firstOrFail();

        // Halaman lama (per buku) dialihkan ke alur baru:
        //  - Wali kelas / KSP-Waka-Kurikulum-TU → sheet per-mapel di Leger baru
        //  - Guru non-wali                       → daftar Buku Nilai Saya
        $bookGroup = $adminBook->studyGroup;
        $legerAccess = $bookGroup ? NilaiKelasController::legerAccessFor($userId, $bookGroup) : 'none';

        if (in_array($legerAccess, ['full', 'wali'], true)) {
            return redirect()->route('user.schools.nilai-kelas.sts', [
                'userId' => $userId,
                'studyGroupId' => $adminBook->study_group_id,
                'academic_year_id' => $adminBook->academic_year_id,
                'semester' => $adminBook->semester,
                'tab' => 'mapel',
                'admin_book_id' => $adminBook->id,
            ]);
        }

        return redirect()->route('user.schools.guru-mapel.index', ['userId' => $userId]);

        // Siswa di kelas ini (tahun ajaran aktif)
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $adminBook->study_group_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')
            ->get();

        // Nilai sumatif yang sudah ada
        $sumatifRows = NilaiSumatif::where('admin_book_id', $bookId)
            ->where('semester', $adminBook->semester)
            ->get()
            ->keyBy('student_id');

        // View lama memakai $nilaiMap sebagai model per siswa.
        $nilaiMap = $sumatifRows;

        // Kolom SH dinamis (legacy S1–S6 + kolom baru) — aturan tunggal.
        $sumatifService = app(SumatifHarianService::class);
        $columns = $sumatifService->columnsFor($adminBook);
        $shMap = $sumatifRows->map(fn (NilaiSumatif $row) => $sumatifService->valuesFor($row, $columns));

        return view('nilai.sts', compact(
            'userId', 'adminBook', 'students', 'nilaiMap', 'isPrivileged', 'columns', 'shMap', 'sumatifRows'
        ));
    }

    /**
     * Simpan / Update Nilai STS (s1-s6 + STS)
     */
    public function storeSts(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*.sh' => 'nullable|array',
            'nilai.*.sh.*' => 'nullable|numeric|min:0|max:100',
            'nilai.*.sts' => 'nullable|numeric|min:0|max:100',
            // Kompatibilitas form lama
            'nilai.*.s1' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s2' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s3' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s4' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s5' => 'nullable|numeric|min:0|max:100',
            'nilai.*.s6' => 'nullable|numeric|min:0|max:100',
        ]);

        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;
        $adminBook = TeacherAdminBook::findOrFail($bookId);

        $this->authorizeAdminBookScope($request, $adminBook);

        if ($adminBook->isLocked()) {
            abort(403, 'Buku nilai sudah dikunci final — hubungi tim kurikulum untuk membuka.');
        }

        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        if ($activeYearId && (string) $adminBook->academic_year_id !== (string) $activeYearId) {
            abort(403, 'Buku tahun ajaran lalu bersifat arsip — data tidak dapat diubah.');
        }

        $service = app(SumatifHarianService::class);

        foreach ($request->nilai as $studentId => $data) {
            $input = [
                'sts' => array_key_exists('sts', $data) ? $data['sts'] : null,
                's1' => $data['s1'] ?? null,
                's2' => $data['s2'] ?? null,
                's3' => $data['s3'] ?? null,
                's4' => $data['s4'] ?? null,
                's5' => $data['s5'] ?? null,
                's6' => $data['s6'] ?? null,
            ];

            if (array_key_exists('sh', $data) && is_array($data['sh'])) {
                $input['sh'] = $data['sh'];
            }

            $service->upsertSumatif($adminBook, (string) $studentId, $input);
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
        $isPrivileged = $this->isNilaiPrivileged($user);

        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'academicYear', 'teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isPrivileged, fn ($q) => $q->where('teacher_id', $userId))
            ->where('id', $bookId)
            ->firstOrFail();

        // Halaman lama (per buku) dialihkan ke alur baru:
        //  - Wali kelas / KSP-Waka-Kurikulum-TU → sheet per-mapel di Leger baru
        //  - Guru non-wali                       → daftar Buku Nilai Saya
        $bookGroup = $adminBook->studyGroup;
        $legerAccess = $bookGroup ? NilaiKelasController::legerAccessFor($userId, $bookGroup) : 'none';

        if (in_array($legerAccess, ['full', 'wali'], true)) {
            return redirect()->route('user.schools.nilai-kelas.sts', [
                'userId' => $userId,
                'studyGroupId' => $adminBook->study_group_id,
                'academic_year_id' => $adminBook->academic_year_id,
                'semester' => $adminBook->semester,
                'tab' => 'mapel',
                'admin_book_id' => $adminBook->id,
            ]);
        }

        return redirect()->route('user.schools.guru-mapel.index', ['userId' => $userId]);

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

        $this->authorizeAdminBookScope($request, $adminBook);

        if ($adminBook->isLocked()) {
            abort(403, 'Buku nilai sudah dikunci final — hubungi tim kurikulum untuk membuka.');
        }

        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        if ($activeYearId && (string) $adminBook->academic_year_id !== (string) $activeYearId) {
            abort(403, 'Buku tahun ajaran lalu bersifat arsip — data tidak dapat diubah.');
        }

        $request->validate([
            'sumatif' => 'required|array',
            'formatif' => 'required|array',
            'penghargaan' => 'required|array',
            'pembiasaan' => 'nullable|array',
        ]);

        foreach ($request->sumatif as $studentId => $data) {
            // ── Nilai Sumatif: update SAS (parsial) — aturan kalkulasi tunggal ──
            app(SumatifHarianService::class)->upsertSumatif($adminBook, (string) $studentId, [
                'sas' => array_key_exists('sas', $data) ? $data['sas'] : null,
                'ket' => $data['ket'] ?? null,
            ]);

            // ── Nilai Formatif ──
            NilaiFormatif::updateOrCreate(
                [
                    'admin_book_id' => $bookId,
                    'student_id' => $studentId,
                    'semester' => $adminBook->semester,
                ],
                [
                    'academic_year_id' => $adminBook->academic_year_id,
                    'skor_lkpd' => $request->formatif[$studentId]['skor_lkpd'] ?? null,
                    'skor_diskusi' => $request->formatif[$studentId]['skor_diskusi'] ?? null,
                    'skor_kuis' => $request->formatif[$studentId]['skor_kuis'] ?? null,
                    'skor_antarteman' => $request->formatif[$studentId]['skor_antarteman'] ?? null,
                ]
            );

            // ── Penghargaan Akademik ──
            $phg = $request->penghargaan[$studentId] ?? [];
            PenghargaanAkademik::updateOrCreate(
                [
                    'admin_book_id' => $bookId,
                    'student_id' => $studentId,
                    'semester' => $adminBook->semester,
                ],
                [
                    'academic_year_id' => $adminBook->academic_year_id,
                    'jujur' => $phg['jujur'] ?? null,
                    'disiplin' => $phg['disiplin'] ?? null,
                    'peduli' => $phg['peduli'] ?? null,
                    'adab' => $phg['adab'] ?? null,
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
                        'student_id' => $studentId,
                        'semester' => $adminBook->semester,
                    ],
                    [
                        'academic_year_id' => $adminBook->academic_year_id,
                        'skor_doa' => $pmb['skor_doa'] ?? null,
                        'skor_hiwar' => $pmb['skor_hiwar'] ?? null,
                        'skor_conversation' => $pmb['skor_conversation'] ?? null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Nilai SAS berhasil disimpan.');
    }

    /**
     * Kunci nilai final (wali kelas rombel atau tim kurikulum), ber-audit.
     */
    public function lockBook(Request $request, string $userId, string $adminBookId)
    {
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;
        $adminBook = TeacherAdminBook::findOrFail($bookId);

        $this->ensureSchoolScope($request, $adminBook->school_id, 'Buku nilai tidak ditemukan pada satuan pendidikan Anda.');

        $user = $request->user();
        $studyGroup = StudyGroup::find($adminBook->study_group_id);
        $isWali = $studyGroup && (string) $studyGroup->homeroom_teacher_id === (string) $user?->id;

        if (! $isWali && ! app(KurikulumAccess::class)->isKurikulumTeam($user)) {
            abort(403, 'Hanya wali kelas atau tim kurikulum yang dapat mengunci nilai final.');
        }

        if ($adminBook->isLocked()) {
            return back()->with('error', 'Buku nilai sudah terkunci.');
        }

        $adminBook->forceFill(['locked_at' => now(), 'locked_by' => $user?->id])->save();

        app(ReviewWorkflowService::class)->audit($adminBook, 'book_locked', $user);

        // Notifikasi WA: nilai final & rapor siap ditinjau (mode terpusat/wali).
        try {
            app(GuardianWhatsAppNotifier::class)->notifyRaporReady($adminBook);
        } catch (\Throwable $e) {
            Log::warning('WA notif rapor gagal: '.$e->getMessage());
        }

        return back()->with('success', 'Nilai final dikunci. Perubahan hanya setelah dibuka kurikulum.');
    }

    /**
     * Buka kunci nilai (hanya tim kurikulum), ber-audit.
     */
    public function unlockBook(Request $request, string $userId, string $adminBookId)
    {
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;
        $adminBook = TeacherAdminBook::findOrFail($bookId);

        $this->ensureSchoolScope($request, $adminBook->school_id, 'Buku nilai tidak ditemukan pada satuan pendidikan Anda.');

        if (! app(KurikulumAccess::class)->isKurikulumTeam($request->user())) {
            abort(403, 'Pembukaan kunci nilai hanya oleh tim kurikulum.');
        }

        if (! $adminBook->isLocked()) {
            return back()->with('error', 'Buku nilai belum terkunci.');
        }

        $adminBook->forceFill(['locked_at' => null, 'locked_by' => null])->save();

        app(ReviewWorkflowService::class)->audit($adminBook, 'book_unlocked', $request->user());

        return back()->with('success', 'Kunci nilai dibuka (tercatat pada audit).');
    }
}
