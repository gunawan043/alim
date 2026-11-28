<?php

namespace App\Http\Controllers;

use App\Exports\LegerExport;
use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\LegerPresensi;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PembiasaanPagi;
use App\Models\PenghargaanAkademik;
use App\Models\RaporDistributionSetting;
use App\Models\RaporPredicateNote;
use App\Models\RaportRegistration;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TahfidzJuzProgress;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Academic\MumtazMurtafiCandidateService;
use App\Services\AcademicPeriodService;
use App\Services\Arsip\LegerPdfService;
use App\Services\Arsip\RaporPdfService;
use App\Services\Documents\SchoolLeaderService;
use App\Services\KurikulumAccess;
use App\Services\NilaiPublishNotificationService;
use App\Services\NotificationUniversalService;
use App\Services\Pdf\DocumentPdfService;
use App\Services\SumatifHarianService;
use App\Services\SumatifKomponenService;
use App\Services\SubjectKktpService;
use App\Services\TahfidzLegerColumns;
use App\Services\TahfidzRaporService;
use App\Support\AcademicNilai;
use App\Support\NilaiMurniRule;
use App\Support\StudentNameFormatter;
use App\Support\UserJabatan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class NilaiKelasController extends Controller
{
    /**
     * Redirect ke STS rombel pertama user (Wali Kelas).
     * Menangani kasus akses langsung dari menu tanpa studyGroupId.
     */
    public function index(string $userId)
    {
        $request = request();
        $schoolId = $request->attributes->get('schoolContextId');

        // Wali kelas → langsung ke rombelnya (TA aktif saja).
        $activeAyId = AcademicYear::where('is_active', true)->value('id');

        $studyGroup = StudyGroup::where('homeroom_teacher_id', $userId)
            ->where('is_active', true)
            ->when($activeAyId, fn ($q) => $q->where('academic_year_id', $activeAyId))
            ->orderedByGrade()
            ->first();

        if ($studyGroup) {
            return redirect()->route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);
        }

        // KSP / Waka / Kurikulum / TU → rombel pertama TA aktif (kelas bisa diganti di halaman leger)
        if (self::legerAccessFor($userId) === 'full') {
            $ay = AcademicYear::where('is_active', true)->orderByDesc('name')->first()
                ?? AcademicYear::orderByDesc('name')->first();

            $group = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($ay, fn ($q) => $q->where('academic_year_id', $ay->id))
                ->where('is_active', true)
                ->orderedByGrade()
                ->first();

            if ($group) {
                return redirect()->route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $group->id]);
            }

            // Berhak penuh tetapi belum ada rombel: jangan 403 — beri jalan
            // keluar ke halaman rombel agar pengelola tahu langkah berikutnya.
            return redirect()
                ->route('user.schools.index', ['userId' => $userId])
                ->with('warning', 'Belum ada rombel aktif pada tahun ajaran ini. Tambahkan rombel terlebih dahulu agar leger dan rapor dapat dibuka.');
        }

        abort(403, 'Leger hanya untuk wali kelas, KSP, Waka, Kurikulum, dan TU.');
    }

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

        // Study group
        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        // Hak akses leger: full (KSP/Waka/Kurikulum/TU) atau wali kelas ybs; guru lain ditolak.
        $legerAccess = $this->assertLegerAccess($userId, $studyGroup);
        $isPrivileged = $legerAccess === 'full';

        // PERIODE AKTIF — satu sumber kebenaran.
        // TA aktif (2026/2027 ganjil) selalu menjadi default untuk rombel,
        // mapel, dan leger. Pilihan TA lain hanya terjadi bila pengguna
        // memilihnya secara eksplisit (mode arsip).
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true);
        $activeAyId = $activeAy?->id;
        $groupAyId = $studyGroup->academic_year_id;
        $requestedAyId = $request->filled('academic_year_id')
            ? (string) $request->academic_year_id
            : null;
        $requestedSemester = in_array($request->input('semester'), ['ganjil', 'genap'], true)
            ? $request->input('semester')
            : null;

        $selectedAyId = $requestedAyId
            ?: ($activeAyId ?? $groupAyId ?? $academicYears->first()?->id);

        $activeSemester = in_array($activeAy?->semester, ['ganjil', 'genap'], true)
            ? $activeAy->semester
            : 'ganjil';
        $selectedSemester = $requestedSemester ?: $activeSemester;

        $isArchiveAy = $selectedAyId && $activeAyId
            && (string) $selectedAyId !== (string) $activeAyId;

        // Pesan transparan: bila rombel masih tercatat pada TA lain, jelaskan
        // bahwa halaman tetap memakai TA aktif. Data arsip tetap dapat dibuka
        // dengan memilih TA tersebut pada filter.
        $periodNotice = null;
        if (! $requestedAyId && $groupAyId && $activeAyId
            && (string) $groupAyId !== (string) $activeAyId) {
            $periodNotice = 'Rombel ini masih tercatat pada tahun ajaran '
                .($academicYears->firstWhere('id', $groupAyId)?->name ?? '-')
                .', sedangkan halaman memakai tahun ajaran aktif '
                .($activeAy?->name ?? '-').' semester '.ucfirst($activeSemester).'. '
                .'Pilih tahun ajaran rombel pada filter untuk melihat arsipnya.';
        }

        // Wizard leger: sheet STS (default) atau SAS.
        $legerMode = in_array($request->input('leger'), ['sts', 'sas'], true) ? $request->input('leger') : 'sts';

        // Filter kelompok mapel pada leger. Mapel "Pondok + Dinas" tampil di
        // kedua filter, karena ia memang diajarkan di keduanya.
        $legerFilter = in_array($request->input('sumber'), ['pondok', 'dinas'], true)
            ? (string) $request->input('sumber')
            : 'semua';

        // Semester terpilih belum punya buku? → salin struktur dari semester lain (mis. genap dari ganjil).
        // Hanya untuk TA aktif — TA arsip tidak pernah dimaterialisasi ulang.
        $autoProvisionedBooks = $isArchiveAy
            ? 0
            : $this->ensureBooksForSemester($schoolId, $studyGroupId, $selectedAyId, $selectedSemester, $userId);

        // Semua mapel di kelas ini (dari TeacherAdminBook) + sorting: Agama → Arab → Hadits → Umum
        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSemester)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->values();

        // Susunan kolom mengikuti pengaturan TINGKAT di Peta Kurikulum:
        // blok Pondok lebih dulu, lalu Dinas, sesuai nomor urut.
        $orderService = app(\App\Services\RaporSubjectOrderService::class);
        $subjectMap = $orderService->sortSubjects($subjectMap, $studyGroup->grade_level_id, $selectedAyId);

        // FALLBACK MAPEL: buku admin belum dibuat, tetapi rombel sudah punya
        // assignment mapel (study_group_subjects). Tampilkan kolom mapel agar
        // leger tidak kosong; nilai tetap kosong sampai buku admin tersedia.
        $subjectSource = 'admin_book';
        if ($subjectMap->isEmpty()) {
            $subjectMap = \App\Models\StudyGroupSubject::with('subject')
                ->where('study_group_id', $studyGroupId)
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->where('is_active', true)
                ->get()
                ->pluck('subject')
                ->filter()
                ->values();

            $subjectMap = app(\App\Services\RaporSubjectOrderService::class)
                ->sortSubjects($subjectMap, $studyGroup->grade_level_id, $selectedAyId);

            if ($subjectMap->isNotEmpty()) {
                $subjectSource = 'rombel_assignment';
            }
        }

        // Jumlah mapel grup AGAMA (kategori 1–3: Agama/Arab/Hadits) untuk header grup di leger
        $agamaCount = $subjectMap->filter(fn ($s) => $this->subjectGroup((string) $s->name) <= 3)->count();

        // Admin book yang aktif (mapel pertama jadi default tab)
        $firstBook = TeacherAdminBook::with('subject')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
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
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->find($selectedBookId)
            : $firstBook;

        // Pembagi RS seragam per kelas (ditampilkan & dipakai hitungan live di tab Mapel).
        $rsDivisor = $activeBook ? app(SumatifHarianService::class)->rsDivisor($activeBook) : 1;

        // ARSIP ROMBEL: ambil SEMUA anggota rombel pada TA tersebut (termasuk yang
        // sudah pindah/naik kelas) agar data kelas lama tidak hilang. Input nilai
        // (tab Mapel) tetap hanya untuk anggota aktif.
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->orderByDesc('is_active')
            ->orderedByStudent()
            ->get();

        // Nilai STS per siswa per mapel (dari semua admin books di kelas ini)
        $nilaiMap = [];
        $studentIds = [];
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

        // Sheet per-mapel: data Formatif & Penghargaan (read-only) untuk mapel aktif
        $formatifMap = [];
        $penghargaanMap = [];
        if ($activeBook && ! empty($studentIds)) {
            $formatifMap = NilaiFormatif::where('admin_book_id', $activeBook->id)
                ->whereIn('student_id', $studentIds)
                ->where('semester', $selectedSemester)
                ->get()
                ->keyBy('student_id')
                ->all();

            $penghargaanMap = PenghargaanAkademik::where('admin_book_id', $activeBook->id)
                ->whereIn('student_id', $studentIds)
                ->where('semester', $selectedSemester)
                ->get()
                ->keyBy('student_id')
                ->all();
        }

        // Admin books map (subject_id → book)
        $bookMap = $subjectMap->mapWithKeys(fn ($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSemester)
                ->where('is_active', true)
                ->first(),
        ]);

        // Sheet Leger SAS: Pembiasaan Pagi + Tahfidz (Juz/Halaqoh) per santri
        $pembiasaanMap = [];
        $tahfidzJuzMap = [];
        $halaqohMap = [];
        if ($legerMode === 'sas' && ! empty($studentIds)) {
            $bookIds = $bookMap->pluck('id')->filter()->values()->all();

            if (! empty($bookIds)) {
                $pembiasaanMap = PembiasaanPagi::whereIn('admin_book_id', $bookIds)
                    ->whereIn('student_id', $studentIds)
                    ->where('semester', $selectedSemester)
                    ->orderBy('updated_at')
                    ->get()
                    ->keyBy('student_id');
            }

            if ($selectedAyId) {
                $tahfidzJuzMap = TahfidzJuzProgress::whereIn('student_id', $studentIds)
                    ->where('academic_year_id', $selectedAyId)
                    ->orderBy('juz_number')
                    ->get()
                    ->keyBy('student_id');

                $halaqohMap = DB::table('tahfidz_group_members as m')
                    ->join('tahfidz_groups as g', 'g.id', '=', 'm.tahfidz_group_id')
                    ->whereIn('m.student_id', $studentIds)
                    ->where('m.academic_year_id', $selectedAyId)
                    ->where('m.status', 'aktif')
                    ->orderBy('m.join_date')
                    ->select('m.student_id', 'g.name')
                    ->get()
                    ->keyBy('student_id');
            }
        }

        $jumlahJuzMap = [];
        if ($selectedAyId && ! empty($studentIds)) {
            $jumlahJuzMap = TahfidzJuzProgress::query()
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $selectedAyId)
                ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
                ->get()
                ->groupBy('student_id')
                ->map->count()
                ->all();
        }

        // Pemilih kelas untuk TU/struktural: daftar rombel sekolah pada TA terpilih
        $schoolGroups = collect();
        if ($isPrivileged) {
            $schoolGroups = StudyGroup::with('gradeLevel')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('academic_year_id', $selectedAyId)
                ->where('is_active', true)
                ->orderedByGrade()
                ->get();
        }

        // PRESENSI: hitung S / I / A per siswa di semester ini
        $presensiMap = $this->buildPresensiMap($studyGroupId, $selectedAyId, $selectedSemester);

        // Rekap presensi yang sudah diisi manual (override absensi harian).
        $presensiManualMap = ($selectedAyId && $selectedSemester)
            ? LegerPresensi::where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSemester)
                ->get()
                ->keyBy('student_id')
                ->all()
            : [];

        // Peringkat & Predikat (per siswa): aggregate STS per mapel → rata-rata kelas
        $legerCol = $legerMode === 'sas' ? 'sas' : 'sts';

        // Mapel yang masuk hitungan leger = hanya yang lolos filter sumber.
        // Rata-rata, jumlah, peringkat, dan predikat dihitung dari daftar ini.
        if ($legerFilter === 'semua') {
            $legerSubjectMap = $subjectMap;
        } else {
            $legerSourceMeta = app(\App\Services\RaporSubjectOrderService::class)
                ->structureMeta($studyGroup->grade_level_id, $selectedAyId, $subjectMap->pluck('id')->all());

            $legerSubjectMap = $subjectMap->filter(fn ($subject) => in_array(
                $legerSourceMeta->get($subject->id)?->subject_source,
                [$legerFilter, \App\Models\GradeLevelSubject::SOURCE_PONDOK_DINAS],
                true,
            ))->values();
        }
        $legerSubjectIds = $legerSubjectMap->pluck('id')->all();
        $legerAgamaCount = $legerSubjectMap
            ->filter(fn ($s) => $this->subjectGroup((string) $s->name) <= 3)
            ->count();

        $nilaiTahfidzMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book || ! app(TahfidzRaporService::class)->isTahfidzSubject($book->subject)) {
                    continue;
                }
                $nilaiTahfidzMap[$sid][$book->id] = app(TahfidzRaporService::class)->scoreFor(
                    $sid, $book, $selectedAyId, $selectedSemester, $legerMode, $nilaiMap[$sid][$book->id] ?? null
                );
            }
        }

        // Bobot per mapel (khusus SAS) — dibaca dari TINGKAT (Peta Kurikulum):
        // satu bobot untuk semua rombel. Kosong = JP ÷ 2.
        $bobotLevelMeta = app(\App\Services\RaporSubjectOrderService::class)
            ->structureMeta($studyGroup->grade_level_id, $selectedAyId, $subjectMap->pluck('id')->all());

        // KKTP yang ditampilkan adalah milik TINGKAT (satu untuk semua rombel).
        $kktpLevelMap = app(SubjectKktpService::class)
            ->mapForGradeLevel($schoolId, $studyGroup->grade_level_id, $selectedAyId, $selectedSemester);

        $bobotMap = [];
        $bobotManualMap = [];
        $bobotJpMap = [];
        foreach ($subjectMap as $subject) {
            $meta = $bobotLevelMeta->get($subject->id);

            $bobotMap[$subject->id] = $meta?->effectiveBobot() ?? 0.0;
            $bobotManualMap[$subject->id] = $meta?->bobot;
            $bobotJpMap[$subject->id] = $meta
                ? StudyGroupSubject::bobotDariJp($meta->allocation_hours)
                : null;
        }

        // Nilai yang tampil di leger ini — satu sumber untuk kolom mapel, Jumlah,
        // rata-rata, peringkat, dan predikat. Mode STS hanya memakai nilai STS
        // (tidak boleh terpengaruh NR Final / nilai SAS), begitu pula sebaliknya.
        $legerValueMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            foreach ($legerSubjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $val = app(TahfidzRaporService::class)->scoreFor(
                    $sid, $book, $selectedAyId, $selectedSemester, $legerMode, $nilaiMap[$sid][$book->id] ?? null
                );
                if ($val === null) {
                    $n = $nilaiMap[$sid][$book->id] ?? null;
                    $val = $n && $n->{$legerCol} !== null ? (float) $n->{$legerCol} : null;
                }
                $legerValueMap[$sid][$book->id] = $val;
            }
        }

        $legerAggMap = [];
        $legerJumlahMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0;
            $count = 0;
            $bobotTerisi = 0.0;
            $tertimbang = 0.0;
            foreach ($legerSubjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $val = $legerValueMap[$sid][$book->id] ?? null;
                if ($val !== null) {
                    $total += $val;
                    $count++;
                    // Bobot hanya berlaku untuk SAS. Leger STS memakai nilai
                    // kolom STS apa adanya, tanpa pengali.
                    if ($legerMode === 'sas') {
                        $bobot = (float) ($bobotMap[$subject->id] ?? 0);
                        $bobotTerisi += $bobot;
                        $tertimbang += $bobot * (float) $val;
                    }
                }
            }

            // STS: jumlah nilai apa adanya (tanpa bobot). SAS: Σ(bobot × nilai).
            $legerJumlahMap[$sid] = $legerMode === 'sas' ? $tertimbang : $total;

            if ($legerMode === 'sas' && $bobotTerisi > 0) {
                // Rata-rata SAS berbobot, sejalan dengan rapor: Σ(bobot×nilai) ÷ Σbobot.
                $legerAggMap[$sid] = round($tertimbang / $bobotTerisi, 1);
            } else {
                $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
            }
        }

        // Hitung peringkat berdasarkan rata-rata (nilai tertinggi = rank 1)
        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn ($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) {
            $rankMap[$sid] = $rank++;
        }

        // Kolom Sumatif Harian (legacy S1–S6 + dinamis) — aturan tunggal dari service.
        $sumatifService = app(SumatifHarianService::class);
        $columns = $sumatifService->columnsFor($activeBook);
        $shMap = collect();

        if ($activeBook) {
            foreach ($students as $history) {
                $row = $nilaiMap[$history->student_id][$activeBook->id] ?? null;
                $shMap[$history->student_id] = $sumatifService->valuesFor($row, $columns);
            }
        }

        // Mapel tertentu (Tahfidz, Hadits, Bahasa Inggris kelas 1–3 SD) tidak
        // mewajibkan Nilai Murni sebagai gerbang pengisian STS/SAS.
        $murniOptional = NilaiMurniRule::optionalFor(
            $activeBook?->subject?->name,
            $studyGroup->school?->school_level,
            $studyGroup->gradeLevel?->level ?? (int) $studyGroup->name,
        );

        // Catatan rapor per predikat untuk rombel ini (dipakai bila catatan wali kosong).
        $predicateNotes = RaporPredicateNote::mapFor(
            (string) $studyGroup->id,
            (string) $selectedAyId,
            (string) $selectedSemester,
        );
        $predicateNotesAr = RaporPredicateNote::mapForArabic(
            (string) $studyGroup->id,
            (string) $selectedAyId,
            (string) $selectedSemester,
        );

        // Mapel tahfidz memakai satu kolom nilai seperti mapel umum: NR Final
        // dari $nilaiTahfidzMap (TahfidzRaporService). Rincian kolom only
        // ditampilkan di tab Mapel.

        // Komponen STS/SAS buku aktif (mis. Praktik & Tulis). Kosong = fitur mati.
        $komponenService = app(SumatifKomponenService::class);
        $stsColumns = $activeBook ? $komponenService->columnsFor($activeBook, 'sts') : [];
        $sasColumns = $activeBook ? $komponenService->columnsFor($activeBook, 'sas') : [];
        $stsComponentMap = [];
        $sasComponentMap = [];

        if ($activeBook) {
            foreach ($students as $history) {
                $sid = $history->student_id;
                $row = $nilaiMap[$sid][$activeBook->id] ?? null;
                $stsComponentMap[$sid] = $komponenService->valuesFor($row, $stsColumns, 'sts');
                $sasComponentMap[$sid] = $komponenService->valuesFor($row, $sasColumns, 'sas');
            }
        }

        // TAB MAPEL — mapel tahfidz memakai format tahfidz (Ziyadah, Rabt, STS,
        // SAS), bukan baris Sumatif Harian S1–S6. Datanya buku nilai halaqah:
        // satu buku per halaqah, dan seorang santri bisa berada di halaqah lain
        // dari teman sekelasnya, jadi nilai dibaca per santri.
        $mapelIsTahfidz = $activeBook
            && app(TahfidzRaporService::class)->isTahfidzSubject($activeBook->subject);

        $tahfidzMapel = [
            'weights' => [],
            'rows' => [],
            'groups' => [],
            'members' => [],
            'book' => null,
        ];

        if ($mapelIsTahfidz) {
            $tahfidzMapel = $this->buildMapelTahfidzData(
                $activeBook,
                $students->pluck('student_id'),
                $selectedAyId,
                $selectedSemester,
            );
        }

        return view('nilai-kelas.sts', compact(
            'userId', 'studyGroup', 'academicYears', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'selectedAyId', 'selectedSemester',
            'selectedBookId', 'activeBook', 'isPrivileged',
            'presensiMap', 'presensiManualMap', 'legerAggMap', 'rankMap', 'columns', 'shMap', 'legerMode', 'formatifMap', 'penghargaanMap',
            'pembiasaanMap', 'tahfidzJuzMap', 'halaqohMap', 'jumlahJuzMap', 'nilaiTahfidzMap', 'schoolGroups', 'agamaCount', 'autoProvisionedBooks', 'rsDivisor',
            'bobotMap', 'legerJumlahMap', 'legerValueMap', 'bobotManualMap', 'bobotJpMap', 'kktpLevelMap', 'predicateNotes', 'predicateNotesAr',
            'legerFilter', 'legerSubjectMap', 'legerSubjectIds', 'legerAgamaCount',
            'isArchiveAy', 'activeAyId', 'subjectSource', 'periodNotice', 'groupAyId', 'murniOptional',
            'stsColumns', 'sasColumns', 'stsComponentMap', 'sasComponentMap',
            'mapelIsTahfidz', 'tahfidzMapel'
        ));
    }

    /**
     * Data tab Mapel untuk mapel tahfidz: baris nilai buku halaqah, bobot,
     * serta daftar kelompok (halaqah) beserta anggotanya agar guru bisa
     * mengisi nilai santri yang belum punya baris.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $studentIds
     * @return array<string, mixed>
     */
    private function buildMapelTahfidzData(
        TeacherAdminBook $book,
        $studentIds,
        ?string $academicYearId,
        ?string $semester,
    ): array {
        $bookIds = \App\Models\TahfidzGradebook::query()
            ->where('subject_id', $book->subject_id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->pluck('id');

        $scores = \App\Models\TahfidzGradebookScore::query()
            ->with('gradebook:id,tahfidz_group_id,subject_id,semester,is_active')
            ->whereIn('tahfidz_gradebook_id', $bookIds)
            ->whereIn('student_id', $studentIds)
            ->get();

        $rows = $scores->keyBy('student_id')->map(fn ($score) => [
            'npt' => $score->npt,
            'ziyadah' => $score->ziyadah,
            'rabt' => $score->rabt,
            'tikrar' => $score->tikrar,
            'sts' => $score->sts,
            'sas' => $score->sas,
            'nr_final' => $score->nr_final,
            'ket' => $score->ket,
            'group_id' => $score->gradebook?->tahfidz_group_id,
        ])->all();

        // NPT dan Tikrar punya sumber otomatis (target halaman & setoran tikrar).
        // Nilai itu hanya jadi ANGKA AWAL selama guru belum mengisi kolomnya,
        // agar guru tetap bisa mengoreksi.
        $autoColumns = app(TahfidzLegerColumns::class);
        if ($academicYearId) {
            $autoColumns->forPeriod(\App\Models\AcademicYear::find($academicYearId), (string) $semester);
        }

        foreach ($studentIds as $studentId) {
            $row = $rows[$studentId] ?? [];
            if (($row['npt'] ?? null) === null) {
                $npt = $autoColumns->npt($studentId);
                if ($npt !== null) {
                    $rows[$studentId]['npt'] = $npt;
                }
            }
            if (($row['tikrar'] ?? null) === null) {
                $groupId = $row['group_id'] ?? null;
                $tikrar = $autoColumns->tikrarAverage($studentId, $groupId);
                if ($tikrar !== null) {
                    $rows[$studentId]['tikrar'] = $tikrar;
                }
            }
        }

        // Bobot milik DEPARTEMEN, bukan buku halaqah — satu angka untuk
        // seluruh halaqah di sekolah ini pada periode yang sama. Buku lama
        // disalin bobotnya di sini agar NR Final-nya ikut angka terbaru.
        $weightService = app(\App\Services\TahfidzWeightService::class);
        $weights = $weightService->forSchool($book->school_id, $academicYearId, $semester);
        foreach (\App\Models\TahfidzGradebook::query()
            ->where('school_id', $book->school_id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get() as $gradebook) {
            $weightService->applyToBook($gradebook);
        }

        // Halaqah tidak terikat mapel; semua halaqah pada periode ini dipakai
        // agar guru tetap bisa mengisi santri yang belum punya baris nilai.
        $groups = \App\Models\TahfidzGroup::query()
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->all();

        $members = \App\Models\TahfidzGroupMember::query()
            ->whereIn('tahfidz_group_id', array_column($groups, 'id'))
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['aktif', 'active'])
            ->get(['tahfidz_group_id', 'student_id'])
            ->groupBy('tahfidz_group_id')
            ->map(fn ($rows) => $rows->pluck('student_id')->all())
            ->all();

        return [
            'weights' => $weights,
            'rows' => $rows,
            'groups' => $groups,
            'members' => $members,
            'book' => $scores->first()?->tahfidz_gradebook_id,
        ];
    }

    /**
     * Rekap presensi leger: S = hadir + terlambat, I = izin + sakit, A = alpa.
     *
     * Angka rekap yang sudah diisi manual di leger (admin_leger_presensi) selalu
     * menang; absensi harian hanya menjadi nilai awal sebelum rekap diisi.
     * Kunci hasil = student_id, nilai = ['s' => int, 'i' => int, 'a' => int].
     */
    private function buildPresensiMap(?string $studyGroupId, ?string $academicYearId, ?string $semester): array
    {
        if (! $studyGroupId || ! $academicYearId || ! $semester) {
            return [];
        }

        $map = [];

        $presensiRaw = AdminPresensiHarian::where('academic_year_id', $academicYearId)
            ->where('study_group_id', $studyGroupId)
            ->where('semester', $semester)
            ->get()
            ->groupBy('student_id');

        foreach ($presensiRaw as $sid => $rows) {
            $map[$sid] = [
                's' => $rows->filter(fn ($r) => in_array($r->status, ['hadir', 'terlambat'], true))->count(),
                'i' => $rows->filter(fn ($r) => in_array($r->status, ['izin', 'sakit'], true))->count(),
                'a' => $rows->where('status', 'alpa')->count(),
            ];
        }

        $manual = LegerPresensi::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get();

        foreach ($manual as $row) {
            $base = $map[$row->student_id] ?? ['s' => 0, 'i' => 0, 'a' => 0];
            $map[$row->student_id] = [
                's' => $row->s ?? $base['s'],
                'i' => $row->i ?? $base['i'],
                'a' => $row->a ?? $base['a'],
            ];
        }

        return $map;
    }

    /**
     * Simpan rekap presensi S/I/A dari leger (STS maupun SAS).
     *
     * Sel yang dikosongkan menghapus override manual sehingga angka kembali
     * mengikuti absensi harian.
     */
    public function presensiStore(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);
        $this->assertLegerAccess($userId, $studyGroup);

        // Kolom kosong dikirim sebagai "" dari form; ubah ke null agar lolos aturan nullable|integer.
        $presensiInput = collect($request->input('presensi', []))->map(function ($values) {
            return collect(is_array($values) ? $values : [])->map(function ($value) {
                return is_string($value) && trim($value) === '' ? null : $value;
            })->all();
        })->all();

        $validated = validator(
            [
                'academic_year_id' => $request->input('academic_year_id'),
                'semester' => $request->input('semester'),
                'presensi' => $presensiInput,
            ],
            [
                'academic_year_id' => 'required|string|exists:academic_years,id',
                'semester' => 'required|in:ganjil,genap',
                'presensi' => 'required|array|min:1',
                'presensi.*.s' => 'nullable|integer|min:0|max:999',
                'presensi.*.i' => 'nullable|integer|min:0|max:999',
                'presensi.*.a' => 'nullable|integer|min:0|max:999',
            ],
            [
                'presensi.*.integer' => 'Presensi harus berupa angka bulat.',
                'presensi.*.max' => 'Presensi maksimal 999.',
            ]
        )->validate();

        $this->assertNotArchivedAcademicYear($validated['academic_year_id']);

        $studentIds = collect($validated['presensi'])->keys();

        $validStudentIds = StudentClassHistory::where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        $invalid = $studentIds->diff($validStudentIds);
        abort_if($invalid->isNotEmpty(), 422, 'Terdapat santri yang bukan anggota rombel ini.');

        $saved = [];

        foreach ($validated['presensi'] as $studentId => $values) {
            $payload = [
                's' => $values['s'] ?? null,
                'i' => $values['i'] ?? null,
                'a' => $values['a'] ?? null,
            ];

            if ($payload['s'] === null && $payload['i'] === null && $payload['a'] === null) {
                LegerPresensi::where('study_group_id', $studyGroup->id)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->where('semester', $validated['semester'])
                    ->where('student_id', $studentId)
                    ->delete();
            } else {
                LegerPresensi::updateOrCreate(
                    [
                        'study_group_id' => $studyGroup->id,
                        'academic_year_id' => $validated['academic_year_id'],
                        'semester' => $validated['semester'],
                        'student_id' => $studentId,
                    ],
                    $payload
                );
            }

            $saved[] = ['student_id' => $studentId] + $payload;
        }

        $presensiMap = $this->buildPresensiMap(
            $studyGroup->id,
            $validated['academic_year_id'],
            $validated['semester']
        );

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'presensi' => $presensiMap,
            'message' => 'Presensi disimpan.',
        ]);
    }

    /**
     * Simpan nilai tahfidz dari tab Mapel ke buku nilai halaqah.
     *
     * Buku dipilih per santri: halaqah tempat ia aktif. Santri yang belum
     * masuk halaqah tidak bisa dinilai di sini — pesannya menyebutkan halaqah
     * sebagai tempat perbaikannya, bukan sekadar menolak.
     */
    private function stsStoreTahfidz(
        Request $request,
        string $userId,
        string $studyGroupId,
        ?string $schoolId,
        StudyGroup $studyGroup,
    ) {
        $adminBook = TeacherAdminBook::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($request->admin_book_id);

        $this->assertNotArchivedAcademicYear($adminBook->academic_year_id);
        abort_if($adminBook->isLocked(), 403, 'Buku nilai '.($adminBook->subject?->name ?? '').' sudah dikunci.');

        $academicYearId = $adminBook->academic_year_id;
        $semester = $request->input('semester', $adminBook->semester);

        $books = \App\Models\TahfidzGradebook::query()
            ->where('subject_id', $adminBook->subject_id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->get()
            ->keyBy('tahfidz_group_id');

        if ($books->isEmpty()) {
            return $this->tahfidzStoreResponse($request, false,
                'Belum ada halaqah yang punya buku nilai tahfidz. Buat halaqah dan tarik santri ke dalamnya terlebih dahulu.', 422);
        }

        $statusByGroup = \App\Models\TahfidzGroupMember::query()
            ->whereIn('tahfidz_group_id', $books->keys())
            ->whereIn('status', ['aktif', 'active'])
            ->get(['tahfidz_group_id', 'student_id'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('tahfidz_group_id')->all());

        $service = app(\App\Services\TahfidzGradebookService::class);
        $nilaiData = (array) $request->input('nilai', []);
        $saved = 0;
        $tanpaHalaqah = [];

        foreach ($nilaiData as $studentId => $row) {
            $groupIds = $statusByGroup[$studentId] ?? [];
            $groupBook = null;
            foreach ($groupIds as $groupId) {
                if ($books->has($groupId)) {
                    $groupBook = $books->get($groupId);
                    break;
                }
            }

            if (! $groupBook) {
                $tanpaHalaqah[] = $studentId;
                continue;
            }

            $members = $service->activeMembers(
                \App\Models\TahfidzGroup::find($groupBook->tahfidz_group_id),
                $academicYearId,
            );

            $saved += $service->saveScores($groupBook, [$studentId => $row], $members);
        }

        // Bobot tahfidz milik DEPARTEMEN (TahfidzWeightService); kiriman
        // `weights` dari halaman guru sengaja diabaikan agar satu sekolah
        // memakai angka yang sama di semua halaqah.

        if ($saved === 0 && $tanpaHalaqah !== []) {
            return $this->tahfidzStoreResponse($request, false,
                'Nilai tidak tersimpan: santri ini belum terdaftar di halaqah mana pun. Tarik santri ke halaqah lewat menu Halaqah Tahfidz, lalu isi nilainya lagi.', 422);
        }

        $message = $saved.' baris nilai tahfidz tersimpan.';
        if ($tanpaHalaqah !== []) {
            $names = Student::whereIn('id', $tanpaHalaqah)->pluck('name')->implode(', ');
            $message .= ' Belum masuk halaqah (tidak dinilai): '.$names.'.';
        }

        return $this->tahfidzStoreResponse($request, true, $message, 200);
    }

    private function tahfidzStoreResponse(Request $request, bool $success, string $message, int $status)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $status);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Simpan nilai STS per mapel (inline dari leger / dari tab mapel)
     */
    public function stsStore(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId);
        $this->assertLegerAccess($userId, $studyGroup);

        // ── TAB MAPEL TAHFIDZ: simpan ke buku nilai halaqah ──
        if ($request->tab === 'mapel_tahfidz' && $request->filled('admin_book_id')) {
            return $this->stsStoreTahfidz($request, $userId, $studyGroupId, $schoolId, $studyGroup);
        }

        // ── TAB MAPEL: simpan per mapel (SH dinamis + STS) ──
        if ($request->tab === 'mapel' && $request->filled('admin_book_id')) {
            $bookId = is_numeric($request->admin_book_id) ? (int) $request->admin_book_id : $request->admin_book_id;
            $adminBook = TeacherAdminBook::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($bookId);

            $this->assertNotArchivedAcademicYear($adminBook->academic_year_id);
            abort_if($adminBook->isLocked(), 403, 'Buku nilai '.($adminBook->subject?->name ?? '').' sudah dikunci.');
            abort_if($adminBook->isPublished(), 403, 'Nilai mapel ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

            $nilaiData = $request->input('nilai', []);
            $savedRows = [];
            $service = app(SumatifHarianService::class);

            // Aturan: Nilai Murni STS/SAS (hasil ujian) WAJIB diisi sebelum kolom STS/SAS diisi.
            // Pengecualian: Tahfidz, Hadits, dan Bahasa Inggris kelas 1–3 SD.
            $murniOptional = NilaiMurniRule::optionalForAdminBook($adminBook);

            $policyErrors = [];
            $studentNames = Student::whereIn('id', array_keys((array) $nilaiData))
                ->pluck('name', 'id');
            $subjectName = $adminBook->subject?->name ?? 'mapel ini';

            foreach ((array) $nilaiData as $studentId => $row) {
                $existingRow = NilaiSumatif::where('admin_book_id', $bookId)
                    ->where('student_id', $studentId)
                    ->where('semester', $adminBook->semester)
                    ->first();

                $hasSts = isset($row['sts']) && $row['sts'] !== '' && $row['sts'] !== null;
                $hasSas = isset($row['sas']) && $row['sas'] !== '' && $row['sas'] !== null;

                foreach ((array) ($row['sts_components'] ?? []) as $value) {
                    if ($value !== '' && $value !== null) {
                        $hasSts = true;
                        break;
                    }
                }
                foreach ((array) ($row['sas_components'] ?? []) as $value) {
                    if ($value !== '' && $value !== null) {
                        $hasSas = true;
                        break;
                    }
                }

                $hasStsMurni = (isset($row['sts_murni']) && $row['sts_murni'] !== '' && $row['sts_murni'] !== null)
                    || ($existingRow?->sts_murni !== null);
                $hasSasMurni = (isset($row['sas_murni']) && $row['sas_murni'] !== '' && $row['sas_murni'] !== null)
                    || ($existingRow?->sas_murni !== null);

                $nama = $studentNames[$studentId] ?? 'santri ini';

                if (! $murniOptional && $hasSts && ! $hasStsMurni) {
                    $policyErrors["nilai.{$studentId}.sts"] = "Baris {$nama}: kolom STS belum bisa disimpan karena Nilai Murni STS kosong. Isi dulu kolom N. Murni STS pada baris {$nama} ({$subjectName}), lalu simpan lagi.";
                }
                if (! $murniOptional && $hasSas && ! $hasSasMurni) {
                    $policyErrors["nilai.{$studentId}.sas"] = "Baris {$nama}: kolom SAS belum bisa disimpan karena Nilai Murni SAS kosong. Isi dulu kolom N. Murni SAS pada baris {$nama} ({$subjectName}), lalu simpan lagi.";
                }
            }

            if (! empty($policyErrors)) {
                // Auto-save memakai AJAX: kirim pesan yang bisa dibaca langsung,
                // bukan halaman 422 tanpa keterangan.
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => reset($policyErrors),
                        'errors' => $policyErrors,
                    ], 422);
                }

                throw ValidationException::withMessages($policyErrors);
            }

            foreach ($nilaiData as $studentId => $data) {
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

                if (array_key_exists('sts_components', $data) && is_array($data['sts_components'])) {
                    $input['sts_components'] = $data['sts_components'];
                }
                if (array_key_exists('sas_components', $data) && is_array($data['sas_components'])) {
                    $input['sas_components'] = $data['sas_components'];
                }

                if (array_key_exists('sts_murni', $data)) {
                    $input['sts_murni'] = $data['sts_murni'];
                }
                if (array_key_exists('sas', $data)) {
                    $input['sas'] = $data['sas'];
                }
                if (array_key_exists('sas_murni', $data)) {
                    $input['sas_murni'] = $data['sas_murni'];
                }
                // RSA manual (boleh dikosongkan → kembali otomatis).
                if (array_key_exists('rsa', $data)) {
                    $input['rsa'] = $data['rsa'];
                }
                // NR Murni manual (boleh dikosongkan → kembali otomatis).
                if (array_key_exists('nr_murni', $data)) {
                    $input['nr_murni'] = $data['nr_murni'];
                }

                // S6 otomatis = rata-rata Penilaian Asesmen Formatif (bila sudah ada).
                $formatifRow = NilaiFormatif::where('admin_book_id', $bookId)
                    ->where('student_id', $studentId)
                    ->where('semester', $adminBook->semester)
                    ->first();
                if ($formatifRow && $formatifRow->nr_final !== null) {
                    $fmtS6 = (float) $formatifRow->nr_final;
                    $input['s6'] = $fmtS6;
                    if (isset($input['sh']) && is_array($input['sh'])) {
                        $input['sh']['s6'] = $fmtS6;
                    }
                } else {
                    // Tidak ada formatif → jangan hapus S6 yang sudah tersimpan.
                    $existingS6 = NilaiSumatif::where('admin_book_id', $bookId)
                        ->where('student_id', $studentId)
                        ->where('semester', $adminBook->semester)
                        ->value('s6');
                    if ($existingS6 !== null) {
                        $input['s6'] = (float) $existingS6;
                        if (isset($input['sh']) && is_array($input['sh'])) {
                            $input['sh']['s6'] = (float) $existingS6;
                        }
                    }
                }

                $row = $service->upsertSumatif($adminBook, (string) $studentId, $input);

                $savedRows[] = [
                    'student_id' => (string) $studentId,
                    'book_id' => $bookId,
                    'rs' => $row->rs !== null ? (float) $row->rs : null,
                    'sts' => $row->sts !== null ? (float) $row->sts : null,
                    'sas' => $row->sas !== null ? (float) $row->sas : null,
                    'rsa' => $row->rsa !== null ? (float) $row->rsa : null,
                    'nr_murni' => $row->nr_murni !== null ? (float) $row->nr_murni : null,
                    'nr_final' => $row->nr_final !== null ? (float) $row->nr_final : null,
                ];
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'saved_rows' => $savedRows, 'message' => 'Nilai mapel disimpan.']);
            }

            $backUrl = route('user.schools.nilai-kelas.sts', [
                'userId' => $userId,
                'studyGroupId' => $studyGroupId,
                'academic_year_id' => $adminBook->academic_year_id,
                'semester' => $adminBook->semester,
                'tab' => 'mapel',
                'admin_book_id' => $bookId,
            ]);

            return redirect($backUrl)->with('success', 'Nilai STS berhasil disimpan.');
        }

        // ── TAB LEGER: simpan STS langsung ke semua mapel ──
        if ($request->tab === 'leger' && ($request->filled('leger_sts') || $request->filled('leger_kkm') || $request->filled('leger_bobot') || $request->has('predicate_notes'))) {
            $selectedAyId = $request->academic_year_id;
            $selectedSem = $request->semester ?? 'ganjil';

            $this->assertNotArchivedAcademicYear($selectedAyId);

            // Ambil semua admin books di kelas ini
            $allBooks = TeacherAdminBook::with('subject', 'studyGroup.gradeLevel', 'kktp')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // ── Simpan KKTP per TINGKAT (bukan per rombel) ──
            // KKTP satu untuk seluruh rombel di tingkat itu, dan Peta Kurikulum
            // adalah sumbernya. Nilai dari leger ditulis ke baris tingkat sekaligus
            // diselaraskan ke semua buku nilai, sehingga Peta langsung berubah.
            if ($request->filled('leger_kkm')) {
                $request->validate(['leger_kkm.*' => 'nullable|numeric|min:0|max:100']);

                $kktpByGrade = [];

                foreach ($request->leger_kkm as $bookId => $kkmVal) {
                    $book = $allBooks->get($bookId);
                    if (! $book || $kkmVal === null) {
                        continue;
                    }
                    abort_if($book->isLocked(), 403, 'Buku nilai '.($book->subject?->name ?? '').' sudah dikunci.');
                    abort_if($book->isPublished(), 403, 'Nilai '.($book->subject?->name ?? 'mapel').' sudah dipublikasikan. Batalkan publikasi terlebih dahulu.');

                    $gradeLevelId = $book->studyGroup?->gradeLevel?->id;
                    if (! $gradeLevelId) {
                        continue;
                    }

                    $kktpByGrade[$gradeLevelId][$book->subject_id] = $kkmVal;
                }

                $kktpService = app(SubjectKktpService::class);
                foreach ($kktpByGrade as $gradeLevelId => $subjects) {
                    $kktpService->saveForGradeLevel(
                        $schoolId,
                        (string) $gradeLevelId,
                        $selectedAyId,
                        $selectedSem,
                        $subjects,
                        $userId,
                    );
                }
            }

            // ── Simpan bobot rapor SAS per TINGKAT (kosong = kembali ke JP ÷ 2) ──
            if ($request->filled('leger_bobot')) {
                // Koma desimal ("3,5") dinormalkan dulu: TU mengisi angkanya manual.
                $bobotRows = collect($request->leger_bobot)
                    ->map(fn ($v) => is_string($v) ? str_replace(',', '.', trim($v)) : $v)
                    ->all();

                $request->merge(['leger_bobot' => $bobotRows]);
                $request->validate(['leger_bobot.*' => 'nullable|numeric|min:0|max:99']);

                $subjectIds = $allBooks->pluck('subject_id')->filter()->unique()->all();
                $bobotInput = collect($bobotRows)
                    ->filter(fn ($v, $subjectId) => in_array($subjectId, $subjectIds, true))
                    ->all();

                $orderService = app(\App\Services\RaporSubjectOrderService::class);
                $orderService->saveBobotGradeLevel(
                    (string) $studyGroup->grade_level_id,
                    $selectedAyId,
                    $bobotInput,
                );
            }

            // ── Simpan catatan rapor per predikat (per rombel) ──
            if ($request->has('predicate_notes')) {
                $request->validate([
                    'predicate_notes' => 'nullable|array',
                    'predicate_notes.*' => 'nullable|string|max:500',
                    'predicate_notes_ar' => 'nullable|array',
                    'predicate_notes_ar.*' => 'nullable|string|max:500',
                ]);

                foreach (RaporPredicateNote::PREDICATES as $predicate) {
                    $note = trim((string) ($request->input('predicate_notes.'.$predicate) ?? ''));
                    $noteAr = trim((string) ($request->input('predicate_notes_ar.'.$predicate) ?? ''));

                    RaporPredicateNote::updateOrCreate(
                        [
                            'study_group_id' => (string) $studyGroup->id,
                            'academic_year_id' => $selectedAyId,
                            'semester' => $selectedSem,
                            'predicate' => $predicate,
                        ],
                        [
                            'school_id' => $studyGroup->school_id,
                            'note' => $note !== '' ? $note : null,
                            'note_ar' => $noteAr !== '' ? $noteAr : null,
                            'updated_by' => $userId,
                        ]
                    );
                }
            }

            $savedRows = [];
            $legerMode = $request->input('leger_mode') === 'sas' ? 'sas' : 'sts';

            // Preload baris yang sudah ada agar auto-save tidak menulis ulang
            // ratusan sel yang nilainya tidak berubah.
            $existingRows = NilaiSumatif::whereIn('admin_book_id', $allBooks->keys()->all())
                ->where('semester', $selectedSem)
                ->get()
                ->keyBy(fn ($r) => (string) $r->admin_book_id.'|'.(string) $r->student_id);

            foreach (($request->leger_sts ?? []) as $studentId => $books) {
                foreach ($books as $bookId => $fields) {
                    $book = $allBooks->get((string) $bookId);
                    if (! $book) {
                        continue;
                    }

                    // Bila mapel memakai komponen (Praktik/Tulis), nilai akhir
                    // dihitung dari komponen — jangan ditimpa input tunggal.
                    if (app(SumatifKomponenService::class)->hasColumns($book, $legerMode)) {
                        continue;
                    }

                    $raw = $fields[$legerMode] ?? null;
                    $newVal = ($raw === '' || $raw === null || ! is_numeric($raw)) ? null : (float) $raw;

                    $existing = $existingRows->get((string) $bookId.'|'.(string) $studentId);
                    $currentVal = $existing?->{$legerMode};
                    $currentVal = $currentVal !== null ? (float) $currentVal : null;

                    $needMurni = $legerMode === 'sas' && $newVal !== null && $existing?->sas_murni === null;

                    if ($currentVal === $newVal && ! $needMurni) {
                        continue;
                    }

                    abort_if($book->isLocked(), 403, 'Buku nilai '.($book->subject?->name ?? '').' sudah dikunci.');
                    abort_if($book->isPublished(), 403, 'Nilai '.($book->subject?->name ?? 'mapel').' sudah dipublikasikan. Batalkan publikasi terlebih dahulu.');

                    $payload = [
                        'academic_year_id' => $selectedAyId,
                        $legerMode => $newVal,
                    ];

                    // Leger SAS: bila Nilai Murni SAS belum ada, samakan dengan
                    // nilai yang diketik agar rapor langsung punya nilai akhir
                    // (murni = hasil ujian, tanpa komponen lain).
                    if ($needMurni) {
                        $payload['sas_murni'] = $newVal;
                    }

                    $nilai = NilaiSumatif::updateOrCreate(
                        [
                            'admin_book_id' => (string) $bookId,
                            'student_id' => $studentId,
                            'semester' => $selectedSem,
                        ],
                        $payload
                    );

                    // Satu aturan kalkulasi: hitung ulang RS/RSA/NR dari kondisi baris.
                    $nilai = app(SumatifHarianService::class)->recalcDerived($nilai, $book);

                    $savedRows[] = [
                        'student_id' => $studentId,
                        'book_id' => $bookId,
                        'sts' => $nilai->sts !== null ? (float) $nilai->sts : null,
                        'sas' => $nilai->sas !== null ? (float) $nilai->sas : null,
                        'rsa' => $nilai->rsa !== null ? (float) $nilai->rsa : null,
                        'nr_final' => $nilai->nr_final !== null ? (float) $nilai->nr_final : null,
                    ];
                }
            }

            // ── AJAX: auto-save ──
            if ($request->ajax() || $request->wantsJson()) {
                $message = match (true) {
                    $request->filled('leger_sts') => 'Leger disimpan.',
                    $request->filled('leger_kkm') => 'KKTP disimpan untuk seluruh rombel di tingkat ini.',
                    $request->filled('leger_bobot') => 'Bobot disimpan untuk seluruh rombel di tingkat ini.',
                    default => 'Catatan predikat disimpan.',
                };

                return response()->json([
                    'success' => true,
                    'saved_rows' => $savedRows,
                    'message' => $message,
                ]);
            }

            $backUrl = route('user.schools.nilai-kelas.sts', [
                'userId' => $userId,
                'studyGroupId' => $studyGroupId,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
                'tab' => 'leger',
            ]);

            $pesan = $request->has('predicate_notes')
                && ! $request->filled('leger_sts')
                && ! $request->filled('leger_kkm')
                && ! $request->filled('leger_bobot')
                    ? 'Catatan predikat berhasil disimpan.'
                    : ($request->filled('leger_kkm') || $request->filled('leger_bobot')
                        ? 'Leger berhasil disimpan. KKTP/bobot berlaku untuk seluruh rombel di tingkat ini.'
                        : 'Leger berhasil disimpan.');

            return redirect($backUrl)->with('success', $pesan);
        }

        // Fallback
        return redirect()->back()->with('error', 'Data tidak valid.');
    }

    /**
     * Kelola kolom Sumatif Harian dari halaman Leger/Mapel (tambah/rename/hapus/urut).
     */
    public function stsColumns(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $this->assertLegerAccess($userId, StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId));

        $book = TeacherAdminBook::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->findOrFail($request->input('admin_book_id'));

        $this->assertNotArchivedAcademicYear($book->academic_year_id);
        abort_if($book->isLocked(), 403, 'Buku nilai sudah dikunci.');
        abort_if($book->isPublished(), 403, 'Nilai mapel sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $validated = $request->validate([
            'columns' => 'required|array|min:1',
            'columns.*.id' => 'required|string|max:64',
            'columns.*.label' => 'required|string|max:60',
        ]);

        $columns = app(SumatifHarianService::class)->saveColumns($book, $validated['columns']);

        return response()->json([
            'success' => true,
            'columns' => $columns,
            'message' => 'Kolom sumatif disimpan.',
        ]);
    }

    /**
     * Kelola komponen STS/SAS dari halaman Leger/Mapel (mis. Praktik & Tulis).
     */
    public function stsKomponen(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $this->assertLegerAccess($userId, StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId));

        $book = TeacherAdminBook::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->findOrFail($request->input('admin_book_id'));

        $this->assertNotArchivedAcademicYear($book->academic_year_id);
        abort_if($book->isLocked(), 403, 'Buku nilai sudah dikunci.');
        abort_if($book->isPublished(), 403, 'Nilai mapel sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $validated = $request->validate([
            'jenis' => 'required|in:sts,sas',
            'columns' => 'nullable|array',
            'columns.*.id' => 'required|string|max:64',
            'columns.*.label' => 'required|string|max:60',
        ]);

        $columns = app(SumatifKomponenService::class)->saveColumns(
            $book,
            $validated['jenis'],
            $validated['columns'] ?? [],
        );

        return response()->json([
            'success' => true,
            'jenis' => $validated['jenis'],
            'columns' => $columns,
            'message' => $columns === []
                ? 'Komponen dimatikan; kolom tunggal '.strtoupper($validated['jenis']).' dipakai kembali.'
                : 'Komponen '.strtoupper($validated['jenis']).' disimpan.',
        ]);
    }

    /**
     * Cetak Leger — print-ready view (tanpa input)
     */
    public function legerCetak(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $this->assertLegerAccess($userId, $studyGroup);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');

        // Jenis dokumen: STS (tengah semester) atau SAS (akhir semester / nilai akhir).
        $jenis = $request->input('jenis') === 'sas' ? 'sas' : 'sts';

        $selectedAy = AcademicYear::orderByDesc('name')->firstWhere('id', $selectedAyId);

        $data = app(LegerPdfService::class)->buildData(
            $schoolId,
            $studyGroup,
            $selectedAy,
            (string) $selectedAyId,
            $selectedSem,
            $jenis,
        );

        return app(DocumentPdfService::class)->fromView(
            'nilai-kelas.leger-cetak',
            $data + ['userId' => $userId],
            'Leger-'.strtoupper($jenis).'-'.$studyGroup->name.'-'.$selectedSem,
            'landscape',
        );
    }

    /**
     * Download Leger dalam format Excel
     */
    public function legerDownload(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $this->assertLegerAccess($userId, $studyGroup);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');

        // Jenis dokumen: STS (tengah semester) atau SAS (akhir semester / nilai akhir).
        $jenis = $request->input('jenis') === 'sas' ? 'sas' : 'sts';

        $selectedAy = AcademicYear::orderByDesc('name')->firstWhere('id', $selectedAyId);

        $subjectMap = TeacherAdminBook::with('subject')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->where('is_active', true)
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($books, $subjectId) => $books->first())
            ->values()
            ->pluck('subject')
            ->filter()
            ->values();

        // Susunan kolom mengikuti pengaturan TINGKAT di Peta Kurikulum:
        // blok Pondok lebih dulu, lalu Dinas, sesuai nomor urut.
        $subjectMap = app(\App\Services\RaporSubjectOrderService::class)
            ->sortSubjects($subjectMap, $studyGroup->grade_level_id, $selectedAyId);

        $bookMap = $subjectMap->mapWithKeys(fn ($s) => [
            $s->id => TeacherAdminBook::with('subject', 'kktp')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('subject_id', $s->id)
                ->where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $selectedAyId)
                ->where('semester', $selectedSem)
                ->where('is_active', true)->first(),
        ]);

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('is_active', true)
            ->orderedByStudent()->get();

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

        $presensiMap = $this->buildPresensiMap($studyGroupId, $selectedAyId, $selectedSem);

        // Nilai tampil mengikuti jenis dokumen (STS → sts; SAS → NR Final fallback SAS).
        $nilaiResolved = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $nilaiResolved[$sid][$book->id] = app(TahfidzRaporService::class)->scoreFor(
                    $sid, $book, $selectedAyId, $selectedSem, $jenis, $nilaiMap[$sid][$book->id] ?? null
                );
            }
        }

        $jumlahJuzMap = TahfidzJuzProgress::query()
            ->whereIn('student_id', $students->pluck('student_id'))
            ->where('academic_year_id', $selectedAyId)
            ->whereIn('status', ['selesai_ziyadah', 'sudah_tasmi'])
            ->get()
            ->groupBy('student_id')
            ->map->count()
            ->all();

        $legerAggMap = [];
        foreach ($students as $history) {
            $sid = $history->student_id;
            $total = 0;
            $count = 0;
            foreach ($subjectMap as $subject) {
                $book = $bookMap[$subject->id] ?? null;
                if (! $book) {
                    continue;
                }
                $val = $nilaiResolved[$sid][$book->id] ?? null;
                if ($val !== null) {
                    $total += $val;
                    $count++;
                }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
        }

        $rankMap = [];
        $sorted = collect($legerAggMap)->filter(fn ($v) => $v !== null)->sortDesc();
        $rank = 1;
        foreach ($sorted as $sid => $val) {
            $rankMap[$sid] = $rank++;
        }

        $ayName = str_replace(['/', ' '], '-', $selectedAy?->name ?? '');
        $filename = 'Leger-'.strtoupper($jenis).'-'.str_replace(' ', '-', $studyGroup->name)
            .'-'.strtoupper($selectedSem)
            .'-'.$ayName.'.xlsx';

        return Excel::download(new LegerExport(compact(
            'studyGroup', 'selectedAy', 'selectedSem', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'legerAggMap', 'rankMap', 'presensiMap', 'jenis', 'nilaiResolved', 'jumlahJuzMap'
        )), $filename);
    }

    /**
     * Daftar Santri — pilih untuk cetak rapor
     */
    public function rapor(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $access = $this->assertLegerAccess($userId, $studyGroup);
        $canPublish = $access === 'full';

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;

        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
        $activeAyId = $academicYears->firstWhere('is_active', true)?->id;
        $isArchiveAy = $activeAyId && (string) $selectedAyId !== (string) $activeAyId;

        // TA aktif hanya menampilkan penghuni yang masih aktif. Untuk TA arsip,
        // riwayat kelas tetap ditampilkan meskipun is_active sudah berubah
        // setelah kenaikan kelas — tanpa ini rapor TA lama menjadi kosong/404.
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->when(! $isArchiveAy, fn ($q) => $q->where('is_active', true))
            ->orderedByStudent()
            ->get();

        // Catatan wali kelas per santri (kolom homeroom_note — data existing).
        $registrations = RaportRegistration::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->get()
            ->keyBy('student_id');

        $stats = [
            'total_santri' => $students->count(),
            'catatan_terisi' => $registrations->filter(fn ($r) => filled($r->homeroom_note))->count(),
            'nilai_final' => $registrations->whereNotNull('final_score')->count(),
            'published' => $registrations->filter(fn ($r) => $r->isPublished())->count(),
        ];

        // Pengaturan tanggal pembagian, tata letak PDF, dan mapel rapor
        // (per sekolah + TA + semester; berlaku untuk STS dan SAS).
        $distributionSetting = RaporDistributionSetting::forContext($studyGroup->school_id, $selectedAyId, $selectedSem);
        // Varian dokumen yang sedang dipratinjau. Query string menang; bila
        // tidak ada (mis. redirect setelah simpan), pakai varian terakhir
        // di session agar preview tidak berubah sendiri.
        $docJenis = in_array($request->input('doc_jenis'), ['sts', 'sas'], true)
            ? $request->input('doc_jenis')
            : (in_array(session('rapor_doc_jenis'), ['sts', 'sas'], true) ? session('rapor_doc_jenis') : 'sts');
        $docBahasa = in_array($request->input('doc_bahasa'), ['id', 'ar', 'id-ar'], true)
            ? $request->input('doc_bahasa')
            : (in_array(session('rapor_doc_bahasa'), ['id', 'ar', 'id-ar'], true) ? session('rapor_doc_bahasa') : 'id');
        session(['rapor_doc_jenis' => $docJenis, 'rapor_doc_bahasa' => $docBahasa]);
        $layout = $distributionSetting?->variantLayoutSettings($docJenis, $docBahasa) ?? RaporDistributionSetting::defaultLayout();

        $variantLayouts = [];
        foreach (['sts', 'sas'] as $variantJenis) {
            foreach (['id', 'ar', 'id-ar'] as $variantBahasa) {
                $variantLayouts[$variantJenis.':'.$variantBahasa] = $distributionSetting?->variantLayoutSettings($variantJenis, $variantBahasa)
                    ?? RaporDistributionSetting::defaultLayout();
            }
        }

        // Susunan & daftar mapel rapor berasal dari TINGKAT (Peta Kurikulum),
        // bukan dari pengaturan rombel — semua rombel di tingkat yang sama
        // mencetak mapel yang sama dengan urutan yang sama.
        $raporPdf = app(RaporPdfService::class);
        $availableSubjects = $selectedAyId
            ? $raporPdf->availableSubjects($schoolId, $studyGroup, (string) $selectedAyId, (string) $selectedSem)
            : collect();
        $selectedSubjectIds = $availableSubjects->pluck('id')->all();

        // Nama Arab untuk form: master mapel dulu, lalu kamus otomatis.
        // Sumbernya sama dengan yang dipakai saat mencetak rapor.
        $subjectArabicMap = $availableSubjects
            ->mapWithKeys(fn ($subject) => [
                $subject->id => app(\App\Services\ArabicName\ArabicNameResolver::class)->subjectName($subject),
            ])
            ->all();

        $summaries = $selectedAyId && $students->isNotEmpty()
            ? $raporPdf->summariesForStudents(
                $schoolId,
                $studyGroup,
                (string) $selectedAyId,
                (string) $selectedSem,
                'sas',
                $students->pluck('student')->filter(),
            )
            : [];

        // Bobot efektif tiap mapel — dibaca dari TINGKAT (Peta Kurikulum):
        // override tingkat bila ada, atau JP tingkat ÷ 2 sebagai default.
        $bobotLevelMeta = app(\App\Services\RaporSubjectOrderService::class)
            ->structureMeta($studyGroup->grade_level_id, $selectedAyId, $selectedSubjectIds);

        $subjectBobotMap = collect($selectedSubjectIds)
            ->mapWithKeys(function ($subjectId) use ($bobotLevelMeta) {
                $meta = $bobotLevelMeta->get($subjectId);

                return [$subjectId => [
                    'bobot' => $meta?->bobot,
                    'jp' => $meta?->allocation_hours,
                    'default' => StudyGroupSubject::bobotDariJp($meta?->allocation_hours),
                    'efektif' => $meta?->effectiveBobot() ?? 0.0,
                ]];
            })
            ->all();

        // KKTP efektif tiap mapel diambil dari buku ajar (subject_kktp) agar
        // nilai yang tampil di rapor bisa diubah langsung dari tabel ini.
        // KKTP: pakai yang tertaut ke buku admin; bila belum tertaut, ambil
        // dari peta kurikulum (SubjectKktp) pada tingkat/TA/semester yang sama.
        $kktpBySubject = SubjectKktp::query()
            ->where('school_id', $studyGroup->school_id)
            ->where('grade_level_id', $studyGroup->grade_level_id)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->whereIn('subject_id', $selectedSubjectIds)
            ->get()
            ->keyBy('subject_id');

        $subjectKktpMap = TeacherAdminBook::query()
            ->with('kktp')
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $selectedAyId)
            ->where('semester', $selectedSem)
            ->whereIn('subject_id', $selectedSubjectIds)
            ->get()
            ->mapWithKeys(function ($book) use ($kktpBySubject) {
                // KKTP tingkat adalah sumber tunggal; tautan buku hanya cadangan.
                $levelKktp = $kktpBySubject->get($book->subject_id);

                return [$book->subject_id => [
                    'kktp_id' => $levelKktp?->id ?? $book->kktp_id,
                    'score' => $levelKktp?->kkm_score ?? $book->kktp?->kkm_score,
                ]];
            })
            ->all();

        return view('nilai-kelas.rapor-index', compact(
            'userId', 'studyGroup', 'academicYears', 'selectedAyId', 'selectedSem', 'selectedAy',
            'students', 'registrations', 'stats', 'distributionSetting', 'layout', 'variantLayouts', 'docJenis', 'docBahasa',
            'availableSubjects', 'selectedSubjectIds', 'summaries', 'canPublish', 'subjectBobotMap', 'subjectKktpMap', 'subjectArabicMap',
        ) + [
            // Urutan, daftar mapel, dan KKTP diatur di Peta Kurikulum untuk
            // tingkat rombel ini — bukan per rombel.
            'raporSettingUrl' => $studyGroup->grade_level_id
                ? route('user.kurikulum.index', [
                    'userId' => $userId,
                    'academic_year_id' => $selectedAyId,
                    'semester' => $selectedSem,
                    'grade_level_id' => $studyGroup->grade_level_id,
                ])
                : null,
        ]);
    }

    public function raporFinalize(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId);

        abort_unless($this->assertLegerAccess($userId, $studyGroup) === 'full', 403, 'Finalisasi rapor hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
        ]);

        $this->assertNotArchivedAcademicYear($validated['academic_year_id']);

        $studentIds = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('is_active', true)
            ->pluck('student_id');

        foreach ($studentIds as $studentId) {
            RaportRegistration::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'study_group_id' => $studyGroupId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester' => $validated['semester'],
                ],
                [
                    'status' => 'finalized',
                    'finalized_at' => now(),
                    'finalized_by' => $request->user()?->id,
                ],
            );
        }

        return back()->with('success', 'Rapor difinalisasi untuk '.$studentIds->count().' santri.');
    }

    public function raporPublish(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId);

        abort_unless($this->assertLegerAccess($userId, $studyGroup) === 'full', 403, 'Publikasi rapor hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis' => 'nullable|in:sts,sas',
        ]);

        $jenis = $validated['jenis'] ?? 'sas';
        $this->assertNotArchivedAcademicYear($validated['academic_year_id']);

        $studentIds = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('is_active', true)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return back()->with('error', 'Belum ada santri aktif pada rombel/tahun ajaran ini.');
        }

        $activeBooks = $this->activeLegerBooks(
            $studyGroup,
            (string) $validated['academic_year_id'],
            (string) $validated['semester'],
        );
        if ($activeBooks->isEmpty()) {
            return back()->with('error', 'Publikasi dibatalkan: belum ada mapel/buku nilai aktif pada rombel dan semester ini.');
        }

        $belowKktp = [];
        $incomplete = $this->incompleteLegerSubjects(
            $studyGroup,
            (string) $validated['academic_year_id'],
            (string) $validated['semester'],
            null,
            $jenis,
            $belowKktp,
        );
        if ($incomplete !== []) {
            return back()->with('error', $this->legerIncompleteMessage($incomplete, $jenis));
        }

        foreach ($studentIds as $studentId) {
            RaportRegistration::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'study_group_id' => $studyGroupId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester' => $validated['semester'],
                ],
                [
                    'status' => 'finalized',
                    'is_published' => true,
                    'finalized_at' => now(),
                    'finalized_by' => $request->user()?->id,
                    'published_at' => now(),
                    'published_by' => $request->user()?->id,
                ],
            );
        }

        if ($jenis === 'sas') {
            try {
                app(MumtazMurtafiCandidateService::class)->syncForPublishedRapor(
                    $studyGroup,
                    (string) $validated['academic_year_id'],
                    (string) $validated['semester'],
                    $request->user()?->id,
                );
            } catch (\Throwable $e) {
                Log::warning('Gagal menyiapkan kandidat Mumtaz Murtafi: '.$e->getMessage());
            }
        }

        $academicYear = AcademicYear::find($validated['academic_year_id']);

        app(NotificationUniversalService::class)->notifyGuardiansForStudents($studentIds, [
            'module' => 'academic',
            'reference_type' => 'raport_registration',
            'reference_id' => $studyGroup->id,
            'type' => 'info',
            'action' => 'rapor_published',
            'title' => 'Rapor dipublikasikan',
            'message' => 'Rapor '.$studyGroup->name.' '.($academicYear?->name ?? '').' semester '.$validated['semester'].' sudah dapat dilihat.',
            'priority' => 'high',
            'send_push' => true,
            'data' => [
                'screen' => 'report',
                'study_group_id' => $studyGroup->id,
                'semester' => $validated['semester'],
                'academic_year_id' => $validated['academic_year_id'],
                'jenis' => $jenis,
            ],
        ]);

        $response = back()->with('success', 'Rapor '.strtoupper($jenis).' dipublikasikan untuk '.$studentIds->count().' santri. Wali santri kini dapat melihat rapor.');

        if ($belowKktp !== []) {
            $response->with('warning', $this->belowKktpMessage($belowKktp, $jenis));
        }

        return $response;
    }

    public function raporUnpublish(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId);

        abort_unless($this->assertLegerAccess($userId, $studyGroup) === 'full', 403, 'Publikasi rapor hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
        ]);

        $this->assertNotArchivedAcademicYear($validated['academic_year_id']);

        RaportRegistration::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->update([
                'status' => 'finalized',
                'is_published' => false,
                'published_at' => null,
                'published_by' => null,
            ]);

        try {
            app(MumtazMurtafiCandidateService::class)->dismissForUnpublishedRapor(
                $studyGroup,
                (string) $validated['academic_year_id'],
                (string) $validated['semester'],
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal membatalkan kandidat Mumtaz Murtafi: '.$e->getMessage());
        }

        return back()->with('success', 'Publikasi rapor dibatalkan. Wali santri tidak dapat melihat rapor ini.');
    }

    public function bookPublish(Request $request, string $userId, string $studyGroupId, string $adminBookId)
    {
        $book = $this->publishableBook($request, $userId, $studyGroupId, $adminBookId);
        $jenis = in_array($request->input('jenis'), ['sts', 'sas'], true) ? $request->input('jenis') : 'sas';

        $studentCount = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('is_active', true)
            ->count();
        if ($studentCount === 0) {
            return back()->with('error', 'Publikasi dibatalkan: belum ada santri aktif pada rombel dan tahun ajaran ini.');
        }

        $belowKktp = [];
        $incomplete = $this->incompleteLegerSubjects(
            $book->studyGroup,
            (string) $book->academic_year_id,
            (string) $book->semester,
            $book,
            $jenis,
            $belowKktp,
        );
        if ($incomplete !== []) {
            return back()->with('error', $this->legerIncompleteMessage($incomplete, $jenis));
        }

        $book->update(['published_at' => now(), 'published_by' => $request->user()?->id]);

        $studentIds = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('is_active', true)
            ->pluck('student_id');

        app(NilaiPublishNotificationService::class)->notifyBookPublished($book, $jenis);

        $response = back()->with('success', 'Nilai '.($book->subject?->name ?? 'mapel').' '.strtoupper($jenis).' dipublikasikan ke wali santri.');

        if ($belowKktp !== []) {
            $response->with('warning', $this->belowKktpMessage($belowKktp, $jenis));
        }

        return $response;
    }

    public function bookUnpublish(Request $request, string $userId, string $studyGroupId, string $adminBookId)
    {
        $book = $this->publishableBook($request, $userId, $studyGroupId, $adminBookId);
        $book->update(['published_at' => null, 'published_by' => null]);

        return back()->with('success', 'Publikasi nilai '.($book->subject?->name ?? 'mapel').' dibatalkan.');
    }

    /**
     * Buku nilai aktif yang relevan untuk leger/rapor.
     *
     * @return Collection
     */
    private function activeLegerBooks(
        StudyGroup $studyGroup,
        string $academicYearId,
        string $semester,
        ?TeacherAdminBook $onlyBook = null,
    ) {
        $books = TeacherAdminBook::with('subject')
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('is_active', true)
            ->when($onlyBook, fn ($query) => $query->whereKey($onlyBook->id))
            ->get();

        if (! $onlyBook) {
            // Daftar mapel rapor kini ditetapkan per TINGKAT di Peta Kurikulum.
            $subjectIds = app(\App\Services\RaporSubjectOrderService::class)->reportSubjectIds(
                $studyGroup->school_id,
                $academicYearId,
                $semester,
                $studyGroup->grade_level_id,
            );

            if ($subjectIds !== null) {
                $books = $books->whereIn('subject_id', $subjectIds);
            }
        }

        return $books;
    }

    /**
     * Cari mapel yang leger-nya belum lengkap (STS/SAS).
     * Mapel Liqo dikecualikan karena merupakan kegiatan rutin, bukan mapel.
     *
     * @return array<int, array{subject: string, missing: int}>
     */
    private function incompleteLegerSubjects(
        StudyGroup $studyGroup,
        string $academicYearId,
        string $semester,
        ?TeacherAdminBook $onlyBook = null,
        string $jenis = 'sas',
        array &$belowKktp = [],
    ): array {
        $books = $this->activeLegerBooks($studyGroup, $academicYearId, $semester, $onlyBook);

        if ($books->isEmpty()) {
            return [];
        }

        $studentIds = StudentClassHistory::query()
            ->where('study_group_id', $studyGroup->id)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->pluck('student_id')
            ->values();

        if ($studentIds->isEmpty()) {
            return [];
        }

        $nilaiRows = NilaiSumatif::query()
            ->whereIn('admin_book_id', $books->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->where('semester', $semester)
            ->get()
            ->keyBy(fn ($row) => $row->admin_book_id.'|'.$row->student_id);

        $tahfidz = app(TahfidzRaporService::class);
        $incomplete = [];
        $belowKktp = [];

        foreach ($books as $book) {
            $subjectName = (string) ($book->subject?->name ?? '');
            $subjectCode = (string) ($book->subject?->code ?? '');

            if (preg_match('/liqo/i', $subjectName.' '.$subjectCode)) {
                continue;
            }

            $missing = [];

            foreach ($studentIds as $studentId) {
                $row = $nilaiRows->get($book->id.'|'.$studentId);

                if ($tahfidz->isTahfidzSubject($book->subject)) {
                    $score = $tahfidz->scoreFor($studentId, $book, $academicYearId, $semester, $jenis, $row);
                } elseif ($jenis === 'sts') {
                    $score = $row?->sts;
                } else {
                    $score = $row?->nr_final ?? $row?->sas;
                }

                if ($score === null) {
                    $missing[] = $studentId;
                } elseif ($score < AcademicNilai::kkm($book)) {
                    $belowKktp[$book->id] = $subjectName !== '' ? $subjectName : 'Tanpa nama mapel';
                }
            }

            if ($missing !== []) {
                $incomplete[] = [
                    'subject' => $subjectName !== '' ? $subjectName : 'Tanpa nama mapel',
                    'missing' => count($missing),
                ];
            }
        }

        $belowKktp = array_values($belowKktp);

        return $incomplete;
    }

    /**
     * @param  array<int, string>  $belowKktp
     */
    private function belowKktpMessage(array $belowKktp, string $jenis = 'sas'): string
    {
        $details = implode(', ', array_slice($belowKktp, 0, 5));

        if (count($belowKktp) > 5) {
            $details .= ', dan '.(count($belowKktp) - 5).' mapel lainnya';
        }

        return 'Rapor '.strtoupper($jenis).' sudah dipublikasikan, tetapi ada nilai di bawah KKTP pada: '.$details.'.';
    }

    /**
     * @param  array<int, array{subject: string, missing: int}>  $incomplete
     */
    private function legerIncompleteMessage(array $incomplete, string $jenis = 'sas'): string
    {
        $details = collect($incomplete)
            ->take(5)
            ->map(fn ($item) => $item['subject'].' ('.$item['missing'].' santri)')
            ->implode(', ');

        if (count($incomplete) > 5) {
            $details .= ', dan '.(count($incomplete) - 5).' mapel lainnya';
        }

        return 'Publikasi dibatalkan: leger belum terisi lengkap pada '.$details
            .'. Lengkapi nilai '.strtoupper($jenis).' terlebih dahulu. Mapel Liqo dikecualikan.';
    }

    private function publishableBook(Request $request, string $userId, string $studyGroupId, string $adminBookId): TeacherAdminBook
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $book = TeacherAdminBook::with('subject')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->findOrFail($adminBookId);

        $access = self::legerAccessFor($userId, $book->studyGroup);
        $isOwner = (string) $book->teacher_id === (string) $request->user()?->id;

        abort_unless($access === 'full' || $isOwner, 403, 'Hanya guru pengampu atau TU/Kurikulum yang dapat mempublikasikan nilai ini.');

        $this->assertNotArchivedAcademicYear($book->academic_year_id);

        return $book;
    }

    /**
     * Simpan pengaturan tanggal pembagian rapor (dipakai cetak & arsip).
     */
    public function raporSettings(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $access = $this->assertLegerAccess($userId, $studyGroup);
        abort_unless($access === 'full', 403, 'Pengaturan rapor hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'distribution_date' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payload = [
            'distribution_date' => $validated['distribution_date'] ?? null,
            'city' => $validated['city'] ?? null,
            'updated_by' => $request->user()?->id,
        ];

        // Catatan umum punya form sendiri; jangan hapus bila form tanggal yang dikirim.
        if ($request->has('notes')) {
            $payload['notes'] = $validated['notes'] ?? null;
        }

        RaporDistributionSetting::updateOrCreate(
            [
                'school_id' => $studyGroup->school_id,
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
            ],
            $payload
        );

        $pesan = $request->has('notes')
            ? 'Catatan umum rapor berhasil disimpan.'
            : 'Tanggal pembagian rapor berhasil disimpan.';

        return back()->with('success', $pesan);
    }

    /**
     * Simpan pengaturan tata letak PDF rapor (margin, font, ukuran kertas).
     * Pengaturan ini dipakai oleh cetak per-santri, massal, dan preview PDF.
     */
    public function raporLayoutSettings(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $access = $this->assertLegerAccess($userId, $studyGroup);
        abort_unless($access === 'full', 403, 'Pengaturan tata letak rapor hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'font_size' => 'required|numeric|min:6|max:24',
            'title_font_size' => 'required|numeric|min:8|max:30',
            'table_font_size' => 'required|numeric|min:6|max:20',
            'info_font_size' => 'required|numeric|min:6|max:20',
            'footer_font_size' => 'required|numeric|min:5|max:16',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'table_line_height' => 'nullable|numeric|min:1|max:2.5',
            'table_row_padding' => 'nullable|numeric|min:0|max:3',
            'table_border_width' => 'nullable|numeric|min:0|max:2',
            'bold_table_header' => 'nullable|boolean',
            'bold_subject' => 'nullable|boolean',
            'title_text' => 'nullable|string|max:255',
            'title_ar_text' => 'nullable|string|max:255',
            'title_id_text' => 'nullable|string|max:255',
            'title_gap' => 'nullable|numeric|min:0|max:20',
            'title_line_height' => 'nullable|numeric|min:0.8|max:3',
            'title_margin_top' => 'nullable|numeric|min:0|max:30',
            'title_margin_bottom' => 'nullable|numeric|min:0|max:30',
            'ident_line_height' => 'nullable|numeric|min:0.8|max:3',
            'ident_row_padding' => 'nullable|numeric|min:0|max:5',
            'footer_border' => 'nullable|boolean',
            'footer_padding' => 'nullable|numeric|min:0|max:20',
            'footer_left' => 'nullable|string|max:120',
            'footer_center' => 'nullable|string|max:120',
            'paper_size' => 'required|in:a4,folio,letter',
            'doc_jenis' => 'nullable|in:sts,sas',
            'doc_bahasa' => 'nullable|in:id,ar,id-ar',
            'show_kop' => 'nullable|boolean',
            'show_ttd_ksp' => 'nullable|boolean',
            'show_ttd_wali' => 'nullable|boolean',
            'show_stamp' => 'nullable|boolean',
            'show_qr' => 'nullable|boolean',
            'kop_height' => 'nullable|numeric|min:5|max:80',
            'ttd_ksp_width' => 'nullable|numeric|min:5|max:100',
            'ttd_ksp_height' => 'nullable|numeric|min:5|max:60',
            'ttd_ksp_margin' => 'nullable|numeric|min:0|max:30',
            'ttd_wali_width' => 'nullable|numeric|min:5|max:100',
            'ttd_wali_height' => 'nullable|numeric|min:5|max:60',
            'ttd_wali_margin' => 'nullable|numeric|min:0|max:30',
            'stamp_width' => 'nullable|numeric|min:5|max:100',
            'stamp_height' => 'nullable|numeric|min:5|max:100',
            'stamp_margin' => 'nullable|numeric|min:0|max:30',
            'document_leader_name' => 'nullable|string|max:255',
            'document_leader_nupy' => 'nullable|string|max:50',
            // Format nama santri: as_is | upper | title.
            'student_name_case' => 'nullable|in:as_is,upper,title',
        ]);

        $context = [
            'school_id' => $studyGroup->school_id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
        ];

        $setting = RaporDistributionSetting::forContext(
            $context['school_id'], $context['academic_year_id'], $context['semester']
        );
        $variantKey = RaporDistributionSetting::variantKey(
            $validated['doc_jenis'] ?? null,
            $validated['doc_bahasa'] ?? null,
        );
        $values = $setting?->variantLayoutSettings(
            $validated['doc_jenis'] ?? null,
            $validated['doc_bahasa'] ?? null,
        ) ?? RaporDistributionSetting::defaultLayout();

        foreach ([
            'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
            'font_size', 'title_font_size', 'table_font_size', 'info_font_size',
            'footer_font_size', 'line_height', 'table_line_height', 'table_row_padding',
            'table_border_width', 'paper_size',
            'title_gap', 'title_line_height', 'title_margin_top', 'title_margin_bottom',
            'ident_line_height', 'ident_row_padding', 'footer_padding',
            'kop_height', 'ttd_ksp_width', 'ttd_ksp_height', 'ttd_ksp_margin',
            'ttd_wali_width', 'ttd_wali_height', 'stamp_width', 'stamp_height',
            'ttd_ksp_margin', 'ttd_wali_margin', 'stamp_margin',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }

        foreach (['footer_left', 'footer_center', 'title_text', 'title_ar_text', 'title_id_text'] as $key) {
            if (array_key_exists($key, $validated)) {
                $value = trim((string) $validated[$key]);
                $values[$key] = $value !== '' ? $value : null;
            }
        }

        foreach (['show_kop', 'show_ttd_ksp', 'show_ttd_wali', 'show_stamp', 'show_qr', 'bold_table_header', 'bold_subject', 'footer_border'] as $key) {
            if ($request->has($key)) {
                $values[$key] = $request->boolean($key);
            }
        }

        $update = $values + ['updated_by' => $request->user()?->id];

        if ($variantKey) {
            $variants = is_array($setting?->variant_layouts) ? $setting->variant_layouts : [];
            $variants[$variantKey] = $values;

            if ($setting) {
                // Jangan ubah nilai global saat menyimpan varian; varian lain
                // yang belum diatur tetap memakai fallback global.
                $update = ['variant_layouts' => $variants, 'updated_by' => $request->user()?->id];
            } else {
                // Baris baru: global tetap default agar varian yang belum
                // diatur tidak ikut berubah.
                $update = RaporDistributionSetting::defaultLayout() + [
                    'variant_layouts' => $variants,
                    'updated_by' => $request->user()?->id,
                ];
            }
        }

        // Format nama berlaku untuk SELURUH varian dokumen sekolah ini, jadi
        // ditulis ke kolom dasar — bukan ke dalam blob varian.
        if (! empty($validated['student_name_case'])) {
            $update['student_name_case'] = StudentNameFormatter::normalize($validated['student_name_case']);
        }
        RaporDistributionSetting::updateOrCreate($context, $update);
        $this->saveDocumentLeaderOverride($studyGroup->school, $request);

        return redirect()->route('user.schools.nilai-kelas.rapor', [
            'userId' => $userId,
            'studyGroupId' => $studyGroup->id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'doc_jenis' => $validated['doc_jenis'] ?? 'sts',
            'doc_bahasa' => $validated['doc_bahasa'] ?? 'id',
        ])->with('success', 'Pengaturan tata letak rapor berhasil disimpan. Preview PDF sudah memakai pengaturan baru.');
    }

    /**
     * Simpan pengaturan tampil & ukuran kop/TTD/stempel dari modal.
     */
    public function raporMediaSettings(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $access = $this->assertLegerAccess($userId, $studyGroup);
        abort_unless($access === 'full', 403, 'Pengaturan kop, TTD, dan stempel hanya untuk TU/Kurikulum/Kepala.');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'doc_jenis' => 'nullable|in:sts,sas',
            'doc_bahasa' => 'nullable|in:id,ar,id-ar',
            'show_kop' => 'nullable|boolean',
            'show_ttd_ksp' => 'nullable|boolean',
            'show_ttd_wali' => 'nullable|boolean',
            'show_stamp' => 'nullable|boolean',
            'show_qr' => 'nullable|boolean',
            'kop_height' => 'required|numeric|min:5|max:80',
            'ttd_ksp_width' => 'required|numeric|min:5|max:100',
            'ttd_ksp_height' => 'required|numeric|min:5|max:60',
            'ttd_ksp_margin' => 'nullable|numeric|min:0|max:30',
            'ttd_wali_width' => 'required|numeric|min:5|max:100',
            'ttd_wali_height' => 'required|numeric|min:5|max:60',
            'ttd_wali_margin' => 'nullable|numeric|min:0|max:30',
            'stamp_width' => 'required|numeric|min:5|max:100',
            'stamp_height' => 'required|numeric|min:5|max:100',
            'stamp_margin' => 'nullable|numeric|min:0|max:30',
            'document_leader_name' => 'nullable|string|max:255',
            'document_leader_nupy' => 'nullable|string|max:50',
        ]);

        $context = [
            'school_id' => $studyGroup->school_id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
        ];

        $setting = RaporDistributionSetting::forContext(
            $context['school_id'], $context['academic_year_id'], $context['semester']
        );
        $variantKey = RaporDistributionSetting::variantKey(
            $validated['doc_jenis'] ?? null,
            $validated['doc_bahasa'] ?? null,
        );
        $values = $setting?->variantLayoutSettings(
            $validated['doc_jenis'] ?? null,
            $validated['doc_bahasa'] ?? null,
        ) ?? RaporDistributionSetting::defaultLayout();

        foreach ([
            'kop_height', 'ttd_ksp_width', 'ttd_ksp_height', 'ttd_ksp_margin',
            'ttd_wali_width', 'ttd_wali_height', 'stamp_width', 'stamp_height',
            'ttd_wali_margin', 'stamp_margin',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }
        foreach (['show_kop', 'show_ttd_ksp', 'show_ttd_wali', 'show_stamp', 'show_qr'] as $key) {
            $values[$key] = $request->boolean($key);
        }

        $update = $values + ['updated_by' => $request->user()?->id];

        if ($variantKey) {
            $variants = is_array($setting?->variant_layouts) ? $setting->variant_layouts : [];
            $variants[$variantKey] = $values;

            if ($setting) {
                $update = ['variant_layouts' => $variants, 'updated_by' => $request->user()?->id];
            } else {
                $update = RaporDistributionSetting::defaultLayout() + [
                    'variant_layouts' => $variants,
                    'updated_by' => $request->user()?->id,
                ];
            }
        }

        RaporDistributionSetting::updateOrCreate($context, $update);
        $this->saveDocumentLeaderOverride($studyGroup->school, $request);

        return redirect()->route('user.schools.nilai-kelas.rapor', [
            'userId' => $userId,
            'studyGroupId' => $studyGroup->id,
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'doc_jenis' => $validated['doc_jenis'] ?? 'sts',
            'doc_bahasa' => $validated['doc_bahasa'] ?? 'id',
        ])->with('success', 'Pengaturan kop, TTD, dan stempel berhasil disimpan.');
    }

    /**
     * Upload/ganti TTD wali kelas. Bisa dilakukan TU/Kurikulum atau wali kelas ybs.
     */
    public function raporWaliTtd(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $user = $request->user();
        $access = self::legerAccessFor($userId, $studyGroup);
        $isWali = (string) ($studyGroup->homeroom_teacher_id ?? '') === (string) $user?->id;

        abort_unless(
            $access === 'full' || $isWali,
            403,
            'TTD wali kelas hanya dapat diunggah oleh wali kelas, TU, Kurikulum, atau Kepala.'
        );

        $request->validate([
            'homeroom_name' => 'nullable|string|max:150',
            'ttd_wali_file' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'remove_ttd_wali' => 'nullable|boolean',
        ]);

        $teacher = $studyGroup->homeroomTeacher;

        // Nama wali khusus rombel (boleh dengan gelar) — hanya untuk dokumen
        // rombel ini, tidak mengubah data pribadi GTK.
        if ($request->has('homeroom_name')) {
            $studyGroup->update([
                'homeroom_name' => filled($request->input('homeroom_name'))
                    ? trim((string) $request->input('homeroom_name'))
                    : null,
            ]);

            if (! $request->hasFile('ttd_wali_file') && ! $request->boolean('remove_ttd_wali')) {
                return back()->with('success', 'Nama wali kelas untuk rapor disimpan.');
            }
        }

        if (! $teacher) {
            return back()->with('error', 'Rombel ini belum memiliki wali kelas.');
        }

        if ($request->boolean('remove_ttd_wali')) {
            if ($teacher->ttd_path) {
                Storage::disk('public')->delete($teacher->ttd_path);
            }
            $teacher->update(['ttd_path' => null]);

            return back()->with('success', 'TTD wali kelas berhasil dihapus.');
        }

        if (! $request->hasFile('ttd_wali_file')) {
            return back()->with('error', 'Pilih file TTD terlebih dahulu.');
        }

        if ($teacher->ttd_path) {
            Storage::disk('public')->delete($teacher->ttd_path);
        }

        $path = $request->file('ttd_wali_file')->store('users/ttd', 'public');
        $teacher->update(['ttd_path' => $path]);

        return back()->with('success', 'TTD wali kelas berhasil diunggah.');
    }

    /**
     * Simpan mapel yang masuk rapor beserta urutannya.
     */
    public function raporSubjectSettings(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $access = $this->assertLegerAccess($userId, $studyGroup);
        abort_unless($access === 'full', 403, 'Pengaturan mapel rapor hanya untuk TU/Kurikulum/Kepala.');

        // Daftar mapel, urutan, dan KKTP rapor TIDAK lagi diatur per rombel:
        // semuanya ditetapkan sekali per tingkat di Peta Kurikulum agar rapor
        // satu tingkat memakai template yang sama. Di sini hanya bobot SAS dan
        // nama mapel (Latin/Arab) yang disimpan.
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'subject_bobot' => 'nullable|array',
            'subject_name' => 'nullable|array',
            'subject_name_ar' => 'nullable|array',
        ]);

        $available = app(RaporPdfService::class)
            ->availableSubjects($schoolId, $studyGroup, (string) $validated['academic_year_id'], (string) $validated['semester'])
            ->pluck('id')
            ->all();

        // Bobot rapor SAS per mapel. Bobot berlaku untuk TINGKAT (semua rombel),
        // jadi nilai dari halaman ini ditulis ke Peta Kurikulum. Kosong = JP ÷ 2.
        // Menerima koma desimal (mis. "2,5") karena diisi manual oleh TU.
        $bobotInput = collect((array) $request->input('subject_bobot', []))
            ->filter(fn ($raw, $subjectId) => in_array($subjectId, $available, true))
            ->all();

        app(\App\Services\RaporSubjectOrderService::class)->saveBobotGradeLevel(
            (string) $studyGroup->grade_level_id,
            (string) $validated['academic_year_id'],
            $bobotInput,
        );

        // Nama Latin & Arab disimpan di master mapel (berlaku untuk semua rombel).
        $nameInput = (array) $request->input('subject_name', []);
        $nameArInput = (array) $request->input('subject_name_ar', []);

        foreach ($available as $subjectId) {
            $subject = Subject::find($subjectId);
            if (! $subject) {
                continue;
            }

            $name = trim((string) ($nameInput[$subjectId] ?? ''));
            if ($name !== '' && $name !== $subject->name) {
                $subject->name = mb_substr($name, 0, 255);
            }

            if (array_key_exists($subjectId, $nameArInput)) {
                $nameAr = trim((string) $nameArInput[$subjectId]);
                $subject->name_ar = $nameAr !== '' ? mb_substr($nameAr, 0, 255) : null;
            }

            if ($subject->isDirty()) {
                $subject->save();
            }
        }

        // KKTP sengaja tidak diubah dari sini: nilainya satu untuk seluruh
        // rombel di tingkat itu dan diatur di Peta Kurikulum.

        return back()->with('success', 'Bobot dan nama mapel rapor tersimpan. Bobot berlaku untuk seluruh rombel di tingkat ini; urutan, daftar mapel, dan KKTP diatur di Peta Kurikulum.');
    }

    /**
     * Simpan catatan wali kelas untuk satu santri (Rapor).
     */
    public function raporNote(Request $request, string $userId, string $studyGroupId, string $studentId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $this->assertLegerAccess($userId, $studyGroup);

        // Kebijakan: catatan rapor hanya wali kelas atau tim kurikulum.
        $user = $request->user();
        $isWali = (string) ($studyGroup->homeroom_teacher_id ?? '') === (string) $user?->id;

        if (! $isWali && ! app(KurikulumAccess::class)->isKurikulumTeam($user)) {
            abort(403, 'Catatan rapor hanya dapat diisi wali kelas atau tim kurikulum.');
        }

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;
        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');

        $this->assertNotArchivedAcademicYear($selectedAyId);
        $this->assertStudentInGroup($studentId, $studyGroup->id, $selectedAyId);

        $validated = $request->validate([
            'homeroom_note' => 'nullable|string|max:500',
            'homeroom_note_ar' => 'nullable|string|max:500',
        ]);

        // Hanya kolom yang dikirim yang diubah, agar simpan cepat dari tabel
        // (hanya catatan Indonesia) tidak menghapus catatan Arab.
        $payload = [];
        if ($request->has('homeroom_note')) {
            $payload['homeroom_note'] = $validated['homeroom_note'] ?? null;
        }
        if ($request->has('homeroom_note_ar')) {
            $payload['homeroom_note_ar'] = $validated['homeroom_note_ar'] ?? null;
        }

        RaportRegistration::updateOrCreate(
            [
                'student_id' => $studentId,
                'study_group_id' => $studyGroup->id,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
            ],
            $payload
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan wali kelas berhasil disimpan.',
                'homeroom_note' => $validated['homeroom_note'] ?? null,
                'homeroom_note_ar' => $validated['homeroom_note_ar'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'Catatan wali kelas berhasil disimpan.');
    }

    /**
     * Cetak Rapor per Santri — PDF download
     */
    public function raporCetak(Request $request, string $userId, string $studyGroupId, string $studentId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;
        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');

        // Jenis dokumen: STS (tengah semester) atau SAS (akhir semester / nilai akhir).
        $jenis = $request->input('jenis') === 'sas' ? 'sas' : 'sts';
        $bahasa = RaporPdfService::normalizeBahasa($request->input('bahasa'));

        $this->assertLegerAccess($userId, $studyGroup);

        $selectedAy = AcademicYear::orderByDesc('name')->get()->firstWhere('id', $selectedAyId);

        $student = Student::findOrFail($studentId);
        $history = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->where('study_group_id', $studyGroup->id)
            ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
            ->first();

        // Tautan dari halaman santri kadang tidak membawa academic_year_id.
        // Jika TA aktif tidak cocok, pakai riwayat kelas terakhir santri di rombel ini.
        if (! $history && ! $request->filled('academic_year_id')) {
            $history = StudentClassHistory::query()
                ->where('student_id', $studentId)
                ->where('study_group_id', $studyGroup->id)
                ->orderByDesc('academic_year_id')
                ->first();

            if ($history) {
                $selectedAyId = (string) $history->academic_year_id;
                $selectedAy = AcademicYear::find($selectedAyId);
            }
        }

        if (! $history) {
            return redirect()->route('user.schools.nilai-kelas.rapor', [
                'userId' => $userId,
                'studyGroupId' => $studyGroup->id,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
            ])->with('error', 'Santri ini tidak tercatat pada rombel/TA yang dipilih. Pilih tahun ajaran yang sesuai lalu cetak kembali.');
        }

        $pdf = app(RaporPdfService::class)->renderSingle(
            $schoolId,
            $studyGroup,
            $selectedAy,
            (string) $selectedAyId,
            $selectedSem,
            $jenis,
            $student,
            $bahasa,
        );

        $filename = 'Rapor-'.match ($bahasa) {
            'ar' => 'AR-',
            'id-ar' => 'BILINGUAL-',
            default => '',
        }.str_replace(' ', '-', $student->name)
            .'-'.str_replace(' ', '-', $studyGroup->name)
            .'-'.strtoupper($selectedSem)
            .'-'.str_replace(['/', ' '], '-', $selectedAy?->name ?? '').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            // Preview memakai URL yang sama dengan parameter berbeda; jangan
            // biarkan browser memakai PDF lama dari cache.
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Pastikan buku nilai untuk semester terpilih tersedia pada rombel+TA ini.
     * Jika kosong (mis. semester genap belum di-set), struktur disalin dari semester lain.
     * Idempoten — hanya menambah buku yang belum ada.
     */
    private function ensureBooksForSemester(?string $schoolId, ?string $studyGroupId, ?string $ayId, string $semester, string $userId): int
    {
        if (! $studyGroupId || ! $ayId) {
            return 0;
        }

        // Buku nilai hanya boleh dibuat untuk rombel pada TA yang sama.
        $groupPeriodValid = StudyGroup::query()
            ->whereKey($studyGroupId)
            ->where('academic_year_id', $ayId)
            ->exists();

        if (! $groupPeriodValid) {
            return 0;
        }

        $base = TeacherAdminBook::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $ayId);

        if ((clone $base)->where('semester', $semester)->exists()) {
            return 0;
        }

        $sources = (clone $base)->where('semester', '!=', $semester)->where('is_active', true)->get();
        if ($sources->isEmpty()) {
            return 0;
        }

        $created = 0;
        foreach ($sources as $src) {
            $exists = TeacherAdminBook::where('subject_id', $src->subject_id)
                ->where('study_group_id', $src->study_group_id)
                ->where('academic_year_id', $src->academic_year_id)
                ->where('semester', $semester)
                ->exists();

            if ($exists) {
                continue;
            }

            TeacherAdminBook::create([
                'teacher_id' => $src->teacher_id,
                'subject_id' => $src->subject_id,
                'study_group_id' => $src->study_group_id,
                'school_id' => $src->school_id,
                'academic_year_id' => $src->academic_year_id,
                'semester' => $semester,
                'kktp_id' => $src->kktp_id,
                'is_active' => true,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Cetak Rapor Massal — satu PDF berisi seluruh santri rombel (halaman terpisah per santri).
     */
    public function raporCetakMassal(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;
        $selectedSem = $request->filled('semester') ? $request->semester : (AcademicYear::where('is_active', true)->first()?->semester === 'genap' ? 'genap' : 'ganjil');
        $jenis = $request->input('jenis') === 'sts' ? 'sts' : 'sas';
        $bahasa = RaporPdfService::normalizeBahasa($request->input('bahasa'));

        $this->assertLegerAccess($userId, $studyGroup);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
        $activeAyId = $academicYears->firstWhere('is_active', true)?->id;
        $isArchiveAy = $activeAyId && (string) $selectedAyId !== (string) $activeAyId;

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->when(! $isArchiveAy, fn ($q) => $q->where('is_active', true))
            ->orderedByStudent()
            ->get();

        if ($students->isEmpty()) {
            return redirect()->route('user.schools.nilai-kelas.rapor', [
                'userId' => $userId,
                'studyGroupId' => $studyGroup->id,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
            ])->with('error', 'Belum ada santri pada rombel/TA yang dipilih. Pilih tahun ajaran yang memiliki riwayat kelas lalu cetak kembali.');
        }

        $pdf = app(RaporPdfService::class)->renderMassal(
            $schoolId,
            $studyGroup,
            $selectedAy,
            (string) $selectedAyId,
            $selectedSem,
            $jenis,
            $students->pluck('student')->filter(),
            $bahasa,
        );

        $filename = 'Rapor-MASSAL-'.match ($bahasa) {
            'ar' => 'AR-',
            'id-ar' => 'BILINGUAL-',
            default => '',
        }.str_replace(' ', '-', $studyGroup->name)
            .'-'.strtoupper($selectedSem)
            .'-'.str_replace(['/', ' '], '-', $selectedAy?->name ?? '').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            // Preview memakai URL yang sama dengan parameter berbeda; jangan
            // biarkan browser memakai PDF lama dari cache.
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Nilai yang ditampilkan pada dokumen Leger/Rapor:
     *  - STS (tengah semester) → nilai STS
     *  - SAS (akhir semester)  → Nilai Akhir (NR Final), fallback nilai SAS
     */
    private function resolveNilaiTampil(string $jenis, ?NilaiSumatif $row): ?float
    {
        return AcademicNilai::resolveTampil($jenis, $row);
    }

    /**
     * Sort order mapel: Agama → Arab → Hadits → Umum
     */
    /**
     * Hak akses modul Leger & Rapor:
     *  - 'full' : KSP, Waka, Kurikulum (koor/tim), TU — semua kelas.
     *  - 'wali' : wali kelas ybs — hanya kelasnya.
     *  - 'none' : guru non-wali dsb — tidak boleh membuka leger.
     */
    public static function legerAccessFor(string $userId, ?StudyGroup $studyGroup = null): string
    {
        $user = User::find($userId);
        if (! $user) {
            return 'none';
        }

        if ((method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin())
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return 'full';
        }

        if (canPermission('nilaikelas-admin-tu')
            || canPermission('nilaikelas-wakil-kepala-sekolah')
            || canPermission('nilaikelas-kepala-sekolah-pondok')
            || canPermission('nilaikelas-kepala-sekolah')) {
            return 'full';
        }

        // Jabatan dibaca dari semua sumber (employment utama, semua employments, relasi jabatan)
        // + varian nama TU agar akun TU tidak salah ditolak 403.
        if (UserJabatan::hasAny($user, [
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
            return 'full';
        }

        $tugas = method_exists($user, 'tugasTambahan')
            ? $user->tugasTambahan->pluck('nama')->all()
            : [];
        if (in_array('Tim Kurikulum', $tugas, true) || in_array('Koordinator Kurikulum', $tugas, true)) {
            return 'full';
        }

        // Sidebar menampilkan "Nilai & Rapor" untuk tim kurikulum, koordinator
        // kesiswaan, dan koordinator mapel. Hak akses harus mengikuti menu
        // yang tampil, bukan hanya jabatan struktural.
        if (UserJabatan::hasAnyLoose($user, [
            'tim kurikulum',
            'koordinator kesiswaan',
            'koordinator guru umum',
            'koordinator guru agama',
            'koordinator guru hadits',
            'koordinator guru bahasa arab',
            'koordinator guru tahfidz',
        ])) {
            return 'full';
        }

        if ($studyGroup && (string) $studyGroup->homeroom_teacher_id === $userId) {
            return 'wali';
        }

        return 'none';
    }

    private function assertStudentInGroup(string $studentId, string $studyGroupId, ?string $academicYearId): void
    {
        $exists = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->where('study_group_id', $studyGroupId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->exists();

        abort_unless($exists, 404, 'Santri bukan anggota rombel ini pada tahun ajaran yang dipilih.');
    }

    private function assertLegerAccess(string $userId, StudyGroup $studyGroup): string
    {
        $access = self::legerAccessFor($userId, $studyGroup);
        abort_unless(
            in_array($access, ['full', 'wali'], true),
            403,
            'Leger hanya untuk wali kelas, KSP, Waka, Kurikulum, dan TU.'
        );

        return $access;
    }

    /**
     * ARSIP PERIODE: tolak perubahan nilai pada TA yang bukan TA aktif atau
     * periode yang sudah ditutup (terkunci). Data TA lama tetap dapat dibaca.
     */
    private function assertNotArchivedAcademicYear(?string $academicYearId): void
    {
        app(AcademicPeriodService::class)->assertWritable($academicYearId, 'Nilai');
    }

    /**
     * Kelompok mapel: 1–3 = Agama/B.Arab/Hadits (grup AGAMA), 4 = Umum.
     */
    private function subjectGroup(string $name): int
    {
        return AcademicNilai::subjectGroup($name);
    }

    private function saveDocumentLeaderOverride(?School $school, Request $request): void
    {
        if (! $school) {
            return;
        }

        $visual = is_array($school->document_visual_settings) ? $school->document_visual_settings : [];
        $leaderName = trim((string) $request->input('document_leader_name', ''));
        $leaderNupy = trim((string) $request->input('document_leader_nupy', ''));
        $auto = app(SchoolLeaderService::class)->autoForSchool($school);
        $visual['leader'] = ($leaderName === $auto['display_name'] && $leaderNupy === $auto['nupy'])
            ? ['name' => '', 'nupy' => '']
            : ['name' => $leaderName, 'nupy' => $leaderNupy];
        $school->document_visual_settings = $visual;
        $school->save();
    }
}
