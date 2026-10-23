<?php

namespace App\Http\Controllers;

use App\Exports\LegerExport;
use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PembiasaanPagi;
use App\Models\PenghargaanAkademik;
use App\Models\RaporDistributionSetting;
use App\Models\RaportRegistration;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\SubjectKktp;
use App\Models\TahfidzJuzProgress;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\Arsip\LegerPdfService;
use App\Services\Arsip\RaporPdfService;
use App\Services\KurikulumAccess;
use App\Services\NotificationUniversalService;
use App\Services\SumatifHarianService;
use App\Services\TahfidzRaporService;
use App\Support\AcademicNilai;
use App\Support\UserJabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // ARSIP PERIODE: seluruh tahun ajaran dapat dipilih — TA aktif menjadi default.
        // Data TA lama tetap dapat dibaca; input nilai TA lama diblokir di stsStore().
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAyId = $academicYears->firstWhere('is_active', true)?->id;
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : ($activeAyId ?? $academicYears->first()?->id);
        $isArchiveAy = $selectedAyId && $activeAyId
            && (string) $selectedAyId !== (string) $activeAyId;

        // Semester — default mengikuti semester TA terpilih (perilaku periode).
        $selectedSemester = $request->filled('semester')
            ? $request->semester
            : ($academicYears->firstWhere('id', $selectedAyId)?->semester === 'genap' ? 'genap' : 'ganjil');

        // Wizard leger: sheet STS (default) atau SAS.
        $legerMode = in_array($request->input('leger'), ['sts', 'sas'], true) ? $request->input('leger') : 'sts';

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
            ->sortBy(fn ($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

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
            ->orderBy('attendance_number')
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
        $legerCol = $legerMode === 'sas' ? 'sas' : 'sts';

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
                $n = $nilaiMap[$sid][$book->id] ?? null;
                $val = $nilaiTahfidzMap[$sid][$book->id] ?? null;
                if ($val === null && $n && $n->{$legerCol} !== null) {
                    $val = $n->{$legerCol};
                }
                if ($val !== null) {
                    $total += $val;
                    $count++;
                }
            }
            $legerAggMap[$sid] = $count > 0 ? $total / $count : null;
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

        return view('nilai-kelas.sts', compact(
            'userId', 'studyGroup', 'academicYears', 'subjectMap', 'bookMap',
            'students', 'nilaiMap', 'selectedAyId', 'selectedSemester',
            'selectedBookId', 'activeBook', 'isPrivileged',
            'presensiMap', 'legerAggMap', 'rankMap', 'columns', 'shMap', 'legerMode', 'formatifMap', 'penghargaanMap',
            'pembiasaanMap', 'tahfidzJuzMap', 'halaqohMap', 'jumlahJuzMap', 'nilaiTahfidzMap', 'schoolGroups', 'agamaCount', 'autoProvisionedBooks', 'rsDivisor',
            'isArchiveAy', 'activeAyId'
        ));
    }

    /**
     * Simpan nilai STS per mapel (inline dari leger / dari tab mapel)
     */
    public function stsStore(Request $request, string $userId, string $studyGroupId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $this->assertLegerAccess($userId, StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($studyGroupId));

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
            $policyErrors = [];
            foreach ((array) $nilaiData as $studentId => $row) {
                $existingRow = NilaiSumatif::where('admin_book_id', $bookId)
                    ->where('student_id', $studentId)
                    ->where('semester', $adminBook->semester)
                    ->first();

                $hasSts = isset($row['sts']) && $row['sts'] !== '' && $row['sts'] !== null;
                $hasSas = isset($row['sas']) && $row['sas'] !== '' && $row['sas'] !== null;
                $hasStsMurni = (isset($row['sts_murni']) && $row['sts_murni'] !== '' && $row['sts_murni'] !== null)
                    || ($existingRow?->sts_murni !== null);
                $hasSasMurni = (isset($row['sas_murni']) && $row['sas_murni'] !== '' && $row['sas_murni'] !== null)
                    || ($existingRow?->sas_murni !== null);

                if ($hasSts && ! $hasStsMurni) {
                    $policyErrors["nilai.{$studentId}.sts"] = 'Isi Nilai Murni STS (hasil ujian) terlebih dahulu sebelum mengisi kolom STS.';
                }
                if ($hasSas && ! $hasSasMurni) {
                    $policyErrors["nilai.{$studentId}.sas"] = 'Isi Nilai Murni SAS (hasil ujian) terlebih dahulu sebelum mengisi kolom SAS.';
                }
            }

            if (! empty($policyErrors)) {
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

                if (array_key_exists('sts_murni', $data)) {
                    $input['sts_murni'] = $data['sts_murni'];
                }
                if (array_key_exists('sas', $data)) {
                    $input['sas'] = $data['sas'];
                }
                if (array_key_exists('sas_murni', $data)) {
                    $input['sas_murni'] = $data['sas_murni'];
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
        if ($request->tab === 'leger' && ($request->filled('leger_sts') || $request->filled('leger_kkm'))) {
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

            // ── Simpan KKTP inline (jika ada) ──
            if ($request->filled('leger_kkm')) {
                $request->validate(['leger_kkm.*' => 'nullable|numeric|min:0|max:100']);
                foreach ($request->leger_kkm as $bookId => $kkmVal) {
                    $book = $allBooks->get($bookId);
                    if (! $book || $kkmVal === null) {
                        continue;
                    }
                    abort_if($book->isLocked(), 403, 'Buku nilai '.($book->subject?->name ?? '').' sudah dikunci.');
                    abort_if($book->isPublished(), 403, 'Nilai '.($book->subject?->name ?? 'mapel').' sudah dipublikasikan. Batalkan publikasi terlebih dahulu.');

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
                            'subject_id' => $book->subject_id,
                            'school_id' => $schoolId,
                            'grade_level_id' => $book->studyGroup->gradeLevel->id ?? null,
                            'academic_year_id' => $selectedAyId,
                            'semester' => $selectedSem,
                            'kkm_score' => $kkmVal,
                            'created_by' => $userId,
                        ]);
                        $kktpId = $kktp->id;
                    }

                    // Update kktp_id di book
                    $book->updateQuietly(['kktp_id' => $kktpId]);
                }
            }

            $savedRows = [];
            foreach (($request->leger_sts ?? []) as $studentId => $books) {
                foreach ($books as $bookId => $fields) {
                    $book = $allBooks->get((string) $bookId);
                    if (! $book) {
                        continue;
                    }
                    abort_if($book->isLocked(), 403, 'Buku nilai '.($book->subject?->name ?? '').' sudah dikunci.');
                    abort_if($book->isPublished(), 403, 'Nilai '.($book->subject?->name ?? 'mapel').' sudah dipublikasikan. Batalkan publikasi terlebih dahulu.');

                    $stsVal = $fields['sts'] ?? null;
                    $stsVal = ($stsVal === '' || $stsVal === null) ? null : $stsVal;

                    $nilai = NilaiSumatif::updateOrCreate(
                        [
                            'admin_book_id' => (string) $bookId,
                            'student_id' => $studentId,
                            'semester' => $selectedSem,
                        ],
                        [
                            'academic_year_id' => $selectedAyId,
                            'sts' => $stsVal,
                        ]
                    );

                    // Satu aturan kalkulasi: hitung ulang RS/RSA/NR dari kondisi baris.
                    $nilai = app(SumatifHarianService::class)->recalcDerived($nilai, $book);

                    $savedRows[] = [
                        'student_id' => $studentId,
                        'book_id' => $bookId,
                        'sts' => $nilai->sts !== null ? (float) $nilai->sts : null,
                    ];
                }
            }

            // ── AJAX: auto-save ──
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'saved_rows' => $savedRows,
                    'message' => $request->filled('leger_sts') ? 'Leger disimpan.' : 'KKTP disimpan.',
                ]);
            }

            $backUrl = route('user.schools.nilai-kelas.sts', [
                'userId' => $userId,
                'studyGroupId' => $studyGroupId,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
                'tab' => 'leger',
            ]);

            return redirect($backUrl)->with('success', 'Leger berhasil disimpan.');
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

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

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

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
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

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

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
            ->sortBy(fn ($s) => $this->subjectSortOrder($s->name, $s->category ?? ''))
            ->values();

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

        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

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
            ->orderBy('attendance_number')
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

        // Pengaturan tanggal pembagian rapor (per sekolah + TA + semester).
        $distributionSetting = RaporDistributionSetting::forContext($studyGroup->school_id, $selectedAyId, $selectedSem);

        return view('nilai-kelas.rapor-index', compact(
            'userId', 'studyGroup', 'academicYears', 'selectedAyId', 'selectedSem', 'selectedAy', 'students', 'registrations', 'stats', 'distributionSetting', 'canPublish',
        ));
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
                    'is_published' => true,
                    'finalized_at' => now(),
                    'finalized_by' => $request->user()?->id,
                    'published_at' => now(),
                    'published_by' => $request->user()?->id,
                ],
            );
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
        ]);

        return back()->with('success', 'Rapor dipublikasikan untuk '.$studentIds->count().' santri. Wali santri kini dapat melihat rapor.');
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

        return back()->with('success', 'Publikasi rapor dibatalkan. Wali santri tidak dapat melihat rapor ini.');
    }

    public function bookPublish(Request $request, string $userId, string $studyGroupId, string $adminBookId)
    {
        $book = $this->publishableBook($request, $userId, $studyGroupId, $adminBookId);
        $book->update(['published_at' => now(), 'published_by' => $request->user()?->id]);

        $studentIds = StudentClassHistory::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $book->academic_year_id)
            ->where('is_active', true)
            ->pluck('student_id');

        app(NotificationUniversalService::class)->notifyGuardiansForStudents($studentIds, [
            'module' => 'academic',
            'reference_type' => 'teacher_admin_book',
            'reference_id' => $book->id,
            'type' => 'info',
            'action' => 'nilai_published',
            'title' => 'Nilai '.($book->subject?->name ?? 'mapel').' dipublikasikan',
            'message' => 'Nilai '.($book->subject?->name ?? 'mapel').' untuk '.($book->studyGroup?->name ?? 'kelas').' sudah dapat dilihat.',
            'priority' => 'medium',
        ]);

        return back()->with('success', 'Nilai '.($book->subject?->name ?? 'mapel').' dipublikasikan ke wali santri.');
    }

    public function bookUnpublish(Request $request, string $userId, string $studyGroupId, string $adminBookId)
    {
        $book = $this->publishableBook($request, $userId, $studyGroupId, $adminBookId);
        $book->update(['published_at' => null, 'published_by' => null]);

        return back()->with('success', 'Publikasi nilai '.($book->subject?->name ?? 'mapel').' dibatalkan.');
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

        RaporDistributionSetting::updateOrCreate(
            [
                'school_id' => $studyGroup->school_id,
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
            ],
            [
                'distribution_date' => $validated['distribution_date'] ?? null,
                'city' => $validated['city'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()?->id,
            ]
        );

        return back()->with('success', 'Tanggal pembagian rapor berhasil disimpan.');
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
        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';

        $this->assertNotArchivedAcademicYear($selectedAyId);
        $this->assertStudentInGroup($studentId, $studyGroup->id, $selectedAyId);

        $validated = $request->validate([
            'homeroom_note' => 'nullable|string|max:500',
        ]);

        RaportRegistration::updateOrCreate(
            [
                'student_id' => $studentId,
                'study_group_id' => $studyGroup->id,
                'academic_year_id' => $selectedAyId,
                'semester' => $selectedSem,
            ],
            [
                'homeroom_note' => $validated['homeroom_note'] ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Catatan wali kelas berhasil disimpan.');
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

        // Jenis dokumen: STS (tengah semester) atau SAS (akhir semester / nilai akhir).
        $jenis = $request->input('jenis') === 'sas' ? 'sas' : 'sts';
        $bahasa = $request->input('bahasa') === 'ar' ? 'ar' : 'id';

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

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

        $filename = 'Rapor-'.($bahasa === 'ar' ? 'AR-' : '').str_replace(' ', '-', $student->name)
            .'-'.str_replace(' ', '-', $studyGroup->name)
            .'-'.strtoupper($selectedSem)
            .'-'.str_replace(['/', ' '], '-', $selectedAy?->name ?? '').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
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
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::where('is_active', true)->first()?->id;
        $selectedSem = $request->filled('semester') ? $request->semester : 'ganjil';
        $jenis = $request->input('jenis') === 'sts' ? 'sts' : 'sas';
        $bahasa = $request->input('bahasa') === 'ar' ? 'ar' : 'id';

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher', 'school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $this->assertLegerAccess($userId, $studyGroup);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $academicYears->firstWhere('id', $selectedAyId);
        $activeAyId = $academicYears->firstWhere('is_active', true)?->id;
        $isArchiveAy = $activeAyId && (string) $selectedAyId !== (string) $activeAyId;

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $selectedAyId)
            ->when(! $isArchiveAy, fn ($q) => $q->where('is_active', true))
            ->orderBy('attendance_number')
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

        $filename = 'Rapor-MASSAL-'.($bahasa === 'ar' ? 'AR-' : '').str_replace(' ', '-', $studyGroup->name)
            .'-'.strtoupper($selectedSem)
            .'-'.str_replace(['/', ' '], '-', $selectedAy?->name ?? '').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
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

    private function subjectSortOrder(string $name, string $category): array
    {
        return [$this->subjectGroup($name), strtolower($name)];
    }
}
