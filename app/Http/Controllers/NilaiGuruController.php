<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdminCatatanGuru;
use App\Models\AdminJurnalPembelajaran;
use App\Models\AdminPresensiMapel;
use App\Models\AdminPresensiSiswa;
use App\Models\AlurTujuanPembelajaran;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PaketSoal;
use App\Models\PekanEfektif;
use App\Models\PenghargaanAkademik;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\ProsemItem;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\KurikulumAccess;
use App\Services\GuardianGradeColumnService;
use App\Services\SumatifHarianService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NilaiGuruController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // MENU UTAMA — index guru mapel
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $authUser = auth()->user();
        $isPrivileged = (bool) ($authUser?->hasAnyRole(['Kepala Sekolah'])
            || app(KurikulumAccess::class)->isKurikulumTeam($authUser));

        // Daftar mapel yang diampu guru ini
        $baseQuery = $this->applyTeacherBookScope(
            TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('is_active', true)
                ->orderBy('semester'),
            $isPrivileged
        );

        $adminBooks = $this->dedupeBooks((clone $baseQuery)->get())->groupBy('semester');

        $subjects = $adminBooks->flatten()
            ->pluck('subject_id')->unique()
            ->map(fn ($id) => Subject::find($id))
            ->filter()->sortBy('name')->values();

        return view('nilai-guru.index', compact(
            'userId', 'isPrivileged', 'adminBooks', 'subjects'
        ));
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD HUB — halaman utama per buku-admin (buka ke wizard mana saja)
    // ══════════════════════════════════════════════════════════════════

    public function wizard(Request $request, string $userId, string $adminBookId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // Auto-redirect to first book if no valid book selected
        if ($adminBookId === 'none' || ! is_string($adminBookId)) {
            $firstBook = $this->dedupeBooks(
                $this->applyTeacherBookScope(
                    TeacherAdminBook::with(['subject', 'studyGroup'])
                        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                        ->where('is_active', true),
                    false
                )->orderBy('semester')->get()
            )->first();

            if ($firstBook) {
                return redirect()->route('user.schools.guru-mapel.w1', [
                    'userId' => $userId, 'adminBookId' => $firstBook->id,
                ], 301);
            }
            abort(404, 'Belum ada Buku Admin Guru.');
        }

        return redirect()->route('user.schools.guru-mapel.w1', [
            'userId' => $userId, 'adminBookId' => $adminBookId,
        ], 301);
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 1 — PRESENSI SISWA (per pertemuan)
    // ══════════════════════════════════════════════════════════════════

    public function wizard1(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        // Admin book selector (untuk switch mapel/kelas)
        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        // Students di kelas ini
        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        // Filter tanggal dari request (untuk load data existing saat pilih tanggal)
        $selectedDate = $request->input('attendance_date', now()->toDateString());

        // Semua pertemuan
        $meetingsQuery = AdminPresensiMapel::where('admin_book_id', $book['adminBook']->id)
            ->orderBy('attendance_date', 'desc');

        // Filter berdasarkan tanggal jika ada
        if ($request->has('search_date') && $request->search_date) {
            $meetingsQuery->where('attendance_date', $request->search_date);
        }

        $meetings = $meetingsQuery->get();

        // Ambil meeting yang sesuai tanggal yang dipilih (untuk pre-select radio button)
        $currentMeeting = AdminPresensiMapel::where('admin_book_id', $book['adminBook']->id)
            ->where('attendance_date', $selectedDate)
            ->first();

        // Data presensi siswa — filter hanya untuk meeting yang aktif/dipilih
        if ($currentMeeting) {
            $presensiMap = AdminPresensiSiswa::where('presensi_mapel_id', $currentMeeting->id)
                ->get()
                ->keyBy('student_id');
        } else {
            $presensiMap = collect();
        }

        // Rekap per bulan (ringkasan, tetap ada)
        $recapPerMonth = AdminPresensiMapel::where('admin_book_id', $book['adminBook']->id)
            ->with(['presensiSiswa'])
            ->get()
            ->groupBy(fn ($m) => Carbon::parse($m->attendance_date)->format('Y-m'))
            ->map(fn ($group, $key) => [
                'month' => $key,
                'label' => Carbon::parse($key.'-01')->translatedFormat('F Y'),
                'total_meetings' => $group->count(),
                'hadir' => $group->flatMap(fn ($m) => $m->presensiSiswa)->where('status', 'hadir')->count(),
                'izin' => $group->flatMap(fn ($m) => $m->presensiSiswa)->where('status', 'izin')->count(),
                'sakit' => $group->flatMap(fn ($m) => $m->presensiSiswa)->where('status', 'sakit')->count(),
                'alpa' => $group->flatMap(fn ($m) => $m->presensiSiswa)->where('status', 'alpa')->count(),
            ])
            ->sortByDesc('month')
            ->values();

        // ── Rekap per siswa per bulan (untuk tab rekap bulanan) ──────────────
        $rekapSelectedMonth = null;
        $rekapStudentData = collect();

        $rekapMonth = $request->input('rekap_month', $recapPerMonth->first()['month'] ?? null);

        if ($rekapMonth) {
            $rekapMonthObj = Carbon::parse($rekapMonth.'-01');

            // Semua meeting di bulan tsb, diurutkan berdasarkan tanggal
            $rekapMeetings = AdminPresensiMapel::where('admin_book_id', $book['adminBook']->id)
                ->whereYear('attendance_date', $rekapMonthObj->year)
                ->whereMonth('attendance_date', $rekapMonthObj->month)
                ->orderBy('attendance_date')
                ->with('presensiSiswa')
                ->get();

            // Map: presensi_mapel_id -> {student_id -> status}
            $statusMap = [];
            foreach ($rekapMeetings as $meeting) {
                foreach ($meeting->presensiSiswa as $ps) {
                    $statusMap[$meeting->id][$ps->student_id] = $ps->status;
                }
            }

            // Bangun data per siswa
            $rekapStudentData = $students->map(function ($s) use ($rekapMeetings, $statusMap) {
                $hadir = 0;
                $izin = 0;
                $sakit = 0;
                $alpa = 0;
                $perMeeting = [];

                foreach ($rekapMeetings as $meeting) {
                    $st = $statusMap[$meeting->id][$s->student_id] ?? null;
                    $short = match ($st) {
                        'hadir' => 'H',
                        'izin' => 'I',
                        'sakit' => 'S',
                        'alpa' => 'A',
                        default => '–',
                    };
                    $perMeeting[] = [
                        'label' => 'P-'.$meeting->attendance_date->format('d'),
                        'status' => $short,
                        'raw' => $st,
                    ];
                    if ($st === 'hadir') {
                        $hadir++;
                    } elseif ($st === 'izin') {
                        $izin++;
                    } elseif ($st === 'sakit') {
                        $sakit++;
                    } elseif ($st === 'alpa') {
                        $alpa++;
                    }
                }

                $totalMeetings = $rekapMeetings->count();
                $kehadiran = $totalMeetings > 0
                    ? round((($hadir + $izin + $sakit) / $totalMeetings) * 100, 1)
                    : 0;

                return (object) [
                    'student' => $s->student,
                    'attendance_number' => $s->attendance_number,
                    'per_meeting' => $perMeeting,
                    'total_meetings' => $totalMeetings,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alpa' => $alpa,
                    'kehadiran' => $kehadiran,
                ];
            });

            $rekapSelectedMonth = [
                'value' => $rekapMonth,
                'label' => $rekapMonthObj->translatedFormat('F Y'),
            ];
        }

        return view('nilai-guru.wizard1', compact(
            'userId', 'book', 'books', 'students', 'meetings', 'presensiMap',
            'selectedDate', 'recapPerMonth', 'rekapStudentData', 'rekapSelectedMonth',
            'rekapMonth'
        ));
    }

    public function wizard1Store(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'attendance_date' => 'required|date',
            'status' => 'required|array',
            'status.*' => 'in:hadir,terlambat,izin,sakit,alpa',
        ]);

        $book = $this->loadAdminBook($userId, $adminBookId, true);
        $semester = $book['adminBook']->semester;

        DB::transaction(function () use ($request, $book, $semester) {
            // Create/update presensi_mapel (meeting header)
            $presensiMapel = AdminPresensiMapel::updateOrCreate(
                [
                    'admin_book_id' => $book['adminBook']->id,
                    'attendance_date' => $request->attendance_date,
                    'semester' => $semester,
                ],
                [
                    'academic_year_id' => $book['adminBook']->academic_year_id,
                    'status' => 'hadir',
                ]
            );

            // Save presensi per siswa
            foreach ($request->status as $studentId => $status) {
                AdminPresensiSiswa::updateOrCreate(
                    [
                        'presensi_mapel_id' => $presensiMapel->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $status,
                        'notes' => $request->notes[$studentId] ?? null,
                    ]
                );
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'saved' => true,
                'message' => 'Presensi tersimpan.',
            ]);
        }

        return redirect()->back()->with('success', 'Presensi berhasil disimpan.')
            ->with('_tab', 'input');
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 2 — JURNAL KEGIATAN PEMBELAJARAN
    // ══════════════════════════════════════════════════════════════════

    public function wizard2(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');
        $adminBook = $book['adminBook'];

        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        $journals = AdminJurnalPembelajaran::with([
            'tujuanPembelajaran:id,kode_tp,deskripsi',
            'perangkat:id,judul',
            'prosemItem:id,mulai_minggu_ke,selesai_minggu_ke',
        ])
            ->where('admin_book_id', $adminBook->id)
            ->orderBy('meeting_number', 'asc')
            ->get();

        // Konteks rencana: ATP/TP, PROSEM, RPM, pekan efektif (satu sumber).
        $plan = $this->planContextForBook($adminBook);

        $realizedTpIds = $journals->pluck('tujuan_pembelajaran_id')->filter()->unique()->values();
        $plannedTpCount = $plan['tps']->count();
        $realizedCount = $realizedTpIds->count();
        $progress = $plannedTpCount > 0 ? (int) round($realizedCount / $plannedTpCount * 100) : 0;

        $formatifCount = NilaiFormatif::where('admin_book_id', $adminBook->id)->count();
        $sumatifCount = NilaiSumatif::where('admin_book_id', $adminBook->id)->count();

        return view('nilai-guru.wizard2', compact(
            'userId',
            'book',
            'books',
            'journals',
            'plan',
            'progress',
            'realizedCount',
            'plannedTpCount',
            'formatifCount',
            'sumatifCount'
        ));
    }

    public function wizard2Store(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'meeting_number' => 'required|integer|min:1',
            'meeting_date' => 'required|date',
            'time_in' => 'nullable',
            'time_out' => 'nullable',
            'material' => 'nullable|string|max:5000',
            'prosem_item_id' => 'nullable|exists:prosem_items,id',
            'perangkat_pembelajaran_id' => 'nullable|exists:perangkat_pembelajaran,id',
            'tujuan_pembelajaran_id' => 'nullable|exists:tujuan_pembelajaran,id',
        ]);

        $book = $this->loadAdminBook($userId, $adminBookId, true);
        $adminBook = $book['adminBook'];
        $semester = $adminBook->semester;

        $tpId = $request->tujuan_pembelajaran_id;
        $prosemItemId = $request->prosem_item_id;

        // Konsistensi rencana dengan buku administrasi (mapel/TA/semester).
        if ($prosemItemId) {
            $prosemItem = ProsemItem::with('prosem')->find($prosemItemId);

            if (! $prosemItem || ! $prosemItem->prosem
                || $prosemItem->prosem->subject_id !== $adminBook->subject_id
                || $prosemItem->prosem->academic_year_id !== $adminBook->academic_year_id
                || $prosemItem->prosem->semester !== $semester) {
                return back()->withInput()->with('error', 'Rencana PROSEM yang dipilih bukan untuk mapel/tahun ajaran ini.');
            }

            $tpId = $tpId ?: $prosemItem->tujuan_pembelajaran_id;
        }

        if ($tpId) {
            $tp = TujuanPembelajaran::find($tpId);

            if (! $tp
                || $tp->subject_id !== $adminBook->subject_id
                || $tp->academic_year_id !== $adminBook->academic_year_id
                || $tp->semester !== $semester) {
                return back()->withInput()->with('error', 'TP yang dipilih bukan untuk mapel/tahun ajaran/semester ini.');
            }
        }

        if ($request->perangkat_pembelajaran_id) {
            $rpm = PerangkatPembelajaran::find($request->perangkat_pembelajaran_id);

            if (! $rpm
                || $rpm->subject_id !== $adminBook->subject_id
                || $rpm->academic_year_id !== $adminBook->academic_year_id
                || $rpm->semester !== $semester) {
                return back()->withInput()->with('error', 'RPM yang dipilih bukan untuk mapel/tahun ajaran/semester ini.');
            }
        }

        AdminJurnalPembelajaran::updateOrCreate(
            [
                'admin_book_id' => $adminBook->id,
                'meeting_number' => (int) $request->meeting_number,
                'semester' => $semester,
            ],
            [
                'academic_year_id' => $adminBook->academic_year_id,
                'meeting_date' => $request->meeting_date,
                'time_in' => $request->time_in ?? null,
                'time_out' => $request->time_out ?? null,
                'material' => $request->material ?? null,
                'prosem_item_id' => $prosemItemId ?: null,
                'perangkat_pembelajaran_id' => $request->perangkat_pembelajaran_id ?: null,
                'tujuan_pembelajaran_id' => $tpId ?: null,
                'teacher_signature' => $request->teacher_signature ? 'Terverifikasi' : null,
            ]
        );

        return redirect()->back()->with('success', 'Jurnal berhasil disimpan.');
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 3 — INPUT NILAI SUMATIF
    // ══════════════════════════════════════════════════════════════════

    public function wizard3(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $sumatifMap = NilaiSumatif::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        // Kolom Sumatif Harian (legacy S1–S6 + kolom dinamis) — satu sumber aturan.
        $sumatifService = app(SumatifHarianService::class);
        $columns = $sumatifService->columnsFor($book['adminBook']);

        $shMap = $sumatifMap->map(
            fn (NilaiSumatif $row) => $sumatifService->valuesFor($row, $columns)
        );

        // Paket soal final (approved + dipublikasikan) untuk asesmen sumatif final.
        $paketOptions = PaketSoal::query()
            ->where('is_published', true)
            ->whereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED])
            ->whereHas('kisiKisi', function ($q) use ($book) {
                $q->where('subject_id', $book['adminBook']->subject_id)
                    ->where('academic_year_id', $book['adminBook']->academic_year_id)
                    ->where('semester', $book['adminBook']->semester);
            })
            ->orderBy('kode_paket')
            ->get(['id', 'judul', 'kode_paket']);

        $selectedPaketId = NilaiSumatif::query()
            ->where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->whereNotNull('paket_soal_id')
            ->value('paket_soal_id');

        $formatifMap = NilaiFormatif::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        $guardianColumns = GuardianGradeColumnService::options();
        $guardianVisibleColumns = $book['adminBook']->guardianVisibleColumns()
            ?? GuardianGradeColumnService::keys();

        return view('nilai-guru.wizard3', compact(
            'userId', 'book', 'books', 'students', 'sumatifMap', 'columns', 'shMap', 'paketOptions', 'selectedPaketId', 'formatifMap',
            'guardianColumns', 'guardianVisibleColumns',
        ));
    }

    /**
     * Simpan definisi kolom Sumatif Harian (tambah/rename/hapus/urutkan).
     * Kolom legacy S1–S6 tidak dapat dihapus.
     */
    public function wizard3Columns(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        abort_if($book['adminBook']->isPublished(), 403, 'Nilai buku ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $request->validate([
            'columns' => 'required|array|min:1',
            'columns.*.id' => 'required|string|max:64',
            'columns.*.label' => 'required|string|max:60',
        ]);

        $columns = app(SumatifHarianService::class)->saveColumns(
            $book['adminBook'],
            $request->input('columns', [])
        );

        return response()->json([
            'success' => true,
            'columns' => $columns,
            'message' => 'Kolom Sumatif Harian berhasil disimpan.',
        ]);
    }

    /**
     * Simpan kolom nilai yang boleh dilihat wali/API untuk buku mapel ini.
     */
    public function wizard3Visibility(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        $request->validate([
            'columns' => 'nullable|array',
            'columns.*' => 'string',
        ]);

        $selected = collect((array) $request->input('columns', []))
            ->filter(fn ($key) => in_array($key, GuardianGradeColumnService::keys(), true))
            ->unique()
            ->values()
            ->all();

        $book['adminBook']->update(['guardian_visible_columns' => $selected]);

        return response()->json([
            'success' => true,
            'columns' => $selected,
            'message' => 'Kolom untuk wali dan API berhasil disimpan.',
        ]);
    }

    public function wizard3Store(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        abort_if($book['adminBook']->isPublished(), 403, 'Nilai buku ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $request->validate([
            'sumatif' => 'required|array',
            'sumatif.*.sh' => 'nullable|array',
            'sumatif.*.sh.*' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sts_murni' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sas_murni' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sts' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sas' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.raport_sts' => 'nullable|numeric|min:0|max:100',
            // Kompatibilitas form lama (tanpa sh[])
            'sumatif.*.s1' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s2' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s3' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s4' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s5' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s6' => 'nullable|numeric|min:0|max:100',
        ]);

        // Aturan: Nilai Murni STS/SAS (hasil ujian) WAJIB diisi sebelum kolom STS/SAS diisi.
        $policyErrors = [];
        foreach ((array) $request->input('sumatif', []) as $studentId => $row) {
            $hasSts = isset($row['sts']) && $row['sts'] !== '' && $row['sts'] !== null;
            $hasSas = isset($row['sas']) && $row['sas'] !== '' && $row['sas'] !== null;
            $hasStsMurni = isset($row['sts_murni']) && $row['sts_murni'] !== '' && $row['sts_murni'] !== null;
            $hasSasMurni = isset($row['sas_murni']) && $row['sas_murni'] !== '' && $row['sas_murni'] !== null;

            if ($hasSts && ! $hasStsMurni) {
                $policyErrors["sumatif.{$studentId}.sts"] = 'Isi Nilai Murni STS (hasil ujian) terlebih dahulu sebelum mengisi kolom STS.';
            }
            if ($hasSas && ! $hasSasMurni) {
                $policyErrors["sumatif.{$studentId}.sas"] = 'Isi Nilai Murni SAS (hasil ujian) terlebih dahulu sebelum mengisi kolom SAS.';
            }
        }

        if (! empty($policyErrors)) {
            throw ValidationException::withMessages($policyErrors);
        }

        $service = app(SumatifHarianService::class);

        // Paket soal final (jika dipilih) — hanya paket approved+published yang sahih.
        $paketId = $request->input('paket_soal_id');
        if ($paketId) {
            $paket = PaketSoal::with('kisiKisi')->find($paketId);
            $isValid = $paket && $paket->isFinal()
                && $paket->kisiKisi
                && $paket->kisiKisi->subject_id === $book['adminBook']->subject_id
                && $paket->kisiKisi->semester === $book['adminBook']->semester;

            if (! $isValid) {
                return back()->with('error', 'Paket soal tidak valid — hanya paket final (approved + dipublikasikan) yang boleh dipakai untuk asesmen final.');
            }
        }

        foreach ($request->sumatif as $studentId => $data) {
            $input = [
                'sts_murni' => array_key_exists('sts_murni', $data) ? $data['sts_murni'] : null,
                'sts' => array_key_exists('sts', $data) ? $data['sts'] : null,
                'sas_murni' => array_key_exists('sas_murni', $data) ? $data['sas_murni'] : null,
                'sas' => array_key_exists('sas', $data) ? $data['sas'] : null,
                'raport_sts' => array_key_exists('raport_sts', $data) ? $data['raport_sts'] : null,
                'ket' => $data['ket'] ?? null,
                // Fallback form lama
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

            // S6 otomatis = rata-rata Penilaian Asesmen Formatif (wizard 4).
            $formatifRow = NilaiFormatif::where('admin_book_id', $book['adminBook']->id)
                ->where('student_id', $studentId)
                ->where('semester', $book['adminBook']->semester)
                ->first();
            if ($formatifRow && $formatifRow->nr_final !== null) {
                $fmtS6 = (float) $formatifRow->nr_final;
                $input['s6'] = $fmtS6;
                if (isset($input['sh']) && is_array($input['sh'])) {
                    $input['sh']['s6'] = $fmtS6;
                }
            } else {
                // Tidak ada formatif → jangan hapus S6 yang sudah tersimpan.
                $existingS6 = NilaiSumatif::where('admin_book_id', $book['adminBook']->id)
                    ->where('student_id', $studentId)
                    ->where('semester', $book['adminBook']->semester)
                    ->value('s6');
                if ($existingS6 !== null) {
                    $input['s6'] = (float) $existingS6;
                    if (isset($input['sh']) && is_array($input['sh'])) {
                        $input['sh']['s6'] = (float) $existingS6;
                    }
                }
            }

            $service->upsertSumatif($book['adminBook'], (string) $studentId, $input);
        }

        if ($paketId) {
            NilaiSumatif::query()
                ->where('admin_book_id', $book['adminBook']->id)
                ->where('semester', $book['adminBook']->semester)
                ->update(['paket_soal_id' => $paketId]);
        }

        return redirect()->back()->with('success', 'Nilai Sumatif berhasil disimpan.');
    }

    public function wizard3Bobot(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        abort_if($book['adminBook']->isPublished(), 403, 'Nilai buku ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $validated = $request->validate([
            'nr_final_weight_rs' => 'required|numeric|min:0|max:100',
            'nr_final_weight_sts' => 'required|numeric|min:0|max:100',
            'nr_final_weight_sas' => 'required|numeric|min:0|max:100',
        ]);

        $total = ($validated['nr_final_weight_rs'] ?? 0)
               + ($validated['nr_final_weight_sts'] ?? 0)
               + ($validated['nr_final_weight_sas'] ?? 0);

        if (abs($total - 100) > 0.01) {
            return response()->json(['success' => false, 'message' => 'Total bobot harus 100%'], 422);
        }

        $book['adminBook']->update([
            'nr_final_weight_rs' => (float) $validated['nr_final_weight_rs'],
            'nr_final_weight_sts' => (float) $validated['nr_final_weight_sts'],
            'nr_final_weight_sas' => (float) $validated['nr_final_weight_sas'],
        ]);

        $updated = 0;
        if (abs($total - 100) < 0.01) {
            $updated = NilaiSumatif::recalcNrFinalByBook(
                $book['adminBook']->id,
                (float) $validated['nr_final_weight_rs'],
                (float) $validated['nr_final_weight_sts'],
                (float) $validated['nr_final_weight_sas'],
            );
        }

        return response()->json([
            'success' => true,
            'weights' => [
                'rs' => $validated['nr_final_weight_rs'],
                'sts' => $validated['nr_final_weight_sts'],
                'sas' => $validated['nr_final_weight_sas'],
            ],
            'nr_final_recalculated' => $updated,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 4 — DAFTAR NILAI ASESMEN FORMATIF
    // ══════════════════════════════════════════════════════════════════

    public function wizard4(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $formatifMap = NilaiFormatif::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        $formatifColumns = array_merge([
            'lkpd' => ['1', '2', '3', '4'],
            'diskusi' => ['1', '2', '3', '4'],
            'kuis' => ['1', '2', '3', '4'],
            'antarteman' => ['1', '2', '3', '4'],
        ], array_filter((array) ($book['adminBook']->formatif_columns ?? []), fn ($v) => is_array($v) && count($v) > 0));

        return view('nilai-guru.wizard4', compact('userId', 'book', 'books', 'students', 'formatifMap', 'formatifColumns'));
    }

    public function wizard4Store(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        abort_if($book['adminBook']->isPublished(), 403, 'Nilai buku ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');

        $request->validate([
            'formatif' => 'required|array',
            'formatif.*.skor_lkpd' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_diskusi' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_kuis' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_antarteman' => 'nullable|numeric|min:0|max:100',
            'formatif.*.lkpd_scores' => 'nullable|array',
            'formatif.*.lkpd_scores.*' => 'nullable|numeric|min:0|max:100',
            'formatif.*.diskusi_scores' => 'nullable|array',
            'formatif.*.diskusi_scores.*' => 'nullable|numeric|min:0|max:100',
            'formatif.*.kuis_scores' => 'nullable|array',
            'formatif.*.kuis_scores.*' => 'nullable|numeric|min:0|max:100',
            'formatif.*.antarteman_scores' => 'nullable|array',
            'formatif.*.antarteman_scores.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $this->saveFormatifRows($book, (array) $request->formatif);

        return redirect()->back()->with('success', 'Nilai Formatif berhasil disimpan.');
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 5 — PENGHARGAAN AKADEMIK
    // ══════════════════════════════════════════════════════════════════

    /**
     * Simpan baris-baris Penilaian Asesmen Formatif (dipakai store & autosave).
     * Mengembalikan saved_rows untuk respons JSON autosave.
     */
    protected function saveFormatifRows(array $book, array $formatifData): array
    {
        $aspects = ['lkpd', 'diskusi', 'kuis', 'antarteman'];
        $savedRows = [];

        foreach ($formatifData as $studentId => $data) {
            if (! is_array($data)) {
                continue;
            }

            $columns = [];
            $averages = [];

            foreach ($aspects as $aspect) {
                $raw = $data[$aspect.'_scores'] ?? null;
                $scores = is_array($raw)
                    ? array_values(array_filter(array_map(
                        fn ($v) => is_numeric($v) ? (float) $v : null,
                        $raw
                    ), fn ($v) => $v !== null))
                    : [];

                // Kompatibilitas format lama (skor tunggal per aspek).
                if (empty($scores) && is_numeric($data['skor_'.$aspect] ?? null)) {
                    $scores = [(float) $data['skor_'.$aspect]];
                }

                $columns[$aspect] = $scores;
                $averages[$aspect] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;
            }

            $filled = array_values(array_filter($averages, fn ($v) => $v !== null));
            $nrFinal = count($filled) > 0 ? round(array_sum($filled) / count($filled), 2) : null;

            NilaiFormatif::updateOrCreate(
                [
                    'admin_book_id' => $book['adminBook']->id,
                    'student_id' => $studentId,
                    'semester' => $book['adminBook']->semester,
                ],
                [
                    'academic_year_id' => $book['adminBook']->academic_year_id,
                    'skor_lkpd' => $averages['lkpd'],
                    'skor_diskusi' => $averages['diskusi'],
                    'skor_kuis' => $averages['kuis'],
                    'skor_antarteman' => $averages['antarteman'],
                    'lkpd_scores' => ! empty($columns['lkpd']) ? $columns['lkpd'] : null,
                    'diskusi_scores' => ! empty($columns['diskusi']) ? $columns['diskusi'] : null,
                    'kuis_scores' => ! empty($columns['kuis']) ? $columns['kuis'] : null,
                    'antarteman_scores' => ! empty($columns['antarteman']) ? $columns['antarteman'] : null,
                    'nr_final' => $nrFinal,
                    'ket' => $data['ket'] ?? null,
                ]
            );

            // Sinkronkan S6 (rata-rata formatif) ke NilaiSumatif + hitung ulang RS/RSA/NR.
            if ($nrFinal !== null) {
                app(SumatifHarianService::class)->upsertSumatif($book['adminBook'], (string) $studentId, ['s6' => $nrFinal]);
            }

            $savedRows[] = [
                'student_id' => (string) $studentId,
                'nr_final' => $nrFinal,
                'skor_lkpd' => $averages['lkpd'],
                'skor_diskusi' => $averages['diskusi'],
                'skor_kuis' => $averages['kuis'],
                'skor_antarteman' => $averages['antarteman'],
            ];
        }

        return $savedRows;
    }

    /**
     * Kelola kolom Penilaian Asesmen Formatif (label sub-kolom per aspek) — ala Nilai Sumatif.
     */
    public function wizard4Columns(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        $validated = $request->validate([
            'columns' => 'required|array',
            'columns.lkpd' => 'array|max:12',
            'columns.lkpd.*' => 'nullable|string|max:20',
            'columns.diskusi' => 'array|max:12',
            'columns.diskusi.*' => 'nullable|string|max:20',
            'columns.kuis' => 'array|max:12',
            'columns.kuis.*' => 'nullable|string|max:20',
            'columns.antarteman' => 'array|max:12',
            'columns.antarteman.*' => 'nullable|string|max:20',
        ]);

        $clean = [];
        foreach (['lkpd', 'diskusi', 'kuis', 'antarteman'] as $aspect) {
            $vals = array_values(array_map(
                fn ($v) => trim((string) $v),
                $validated['columns'][$aspect] ?? []
            ));

            // Penomoran otomatis: label kosong/angka dinomori ulang berurutan; label custom dipertahankan.
            $seq = 0;
            $clean[$aspect] = array_map(function ($v) use (&$seq) {
                if ($v === '' || ctype_digit($v)) {
                    $seq++;

                    return (string) $seq;
                }

                return $v;
            }, $vals);

            if (empty($clean[$aspect])) {
                $clean[$aspect] = ['1', '2', '3', '4'];
            }
        }

        $book['adminBook']->update(['formatif_columns' => $clean]);

        return response()->json(['success' => true, 'columns' => $clean, 'message' => 'Kolom formatif disimpan.']);
    }

    public function wizard5(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $penghargaanMap = PenghargaanAkademik::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        return view('nilai-guru.wizard5', compact('userId', 'book', 'books', 'students', 'penghargaanMap'));
    }

    public function wizard5Store(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        $request->validate([
            'penghargaan' => 'required|array',
            'penghargaan.*.jujur' => 'nullable|integer|min:0|max:100',
            'penghargaan.*.disiplin' => 'nullable|integer|min:0|max:100',
            'penghargaan.*.peduli' => 'nullable|integer|min:0|max:100',
            'penghargaan.*.adab' => 'nullable|integer|min:0|max:100',
            'penghargaan.*.kehadiran' => 'nullable|integer|min:0|max:100',
            'penghargaan.*.keaktifan' => 'nullable|integer|min:0|max:100',
        ]);

        foreach ($request->penghargaan as $studentId => $data) {
            $scores = collect(['jujur', 'disiplin', 'peduli', 'adab', 'kehadiran', 'keaktifan'])
                ->map(fn ($k) => $data[$k] ?? null)
                ->filter(fn ($v) => is_numeric($v))
                ->values()
                ->toArray();
            $nrFinal = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;

            PenghargaanAkademik::updateOrCreate(
                [
                    'admin_book_id' => $book['adminBook']->id,
                    'student_id' => $studentId,
                    'semester' => $book['adminBook']->semester,
                ],
                [
                    'academic_year_id' => $book['adminBook']->academic_year_id,
                    'jujur' => $data['jujur'] ?? null,
                    'disiplin' => $data['disiplin'] ?? null,
                    'peduli' => $data['peduli'] ?? null,
                    'adab' => $data['adab'] ?? null,
                    'kehadiran' => $data['kehadiran'] ?? null,
                    'keaktifan' => $data['keaktifan'] ?? null,
                    'nr_final' => $nrFinal,
                    'ket' => $data['ket'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Penghargaan Akademik berhasil disimpan.');
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 6 — CATATAN GURU
    // ══════════════════════════════════════════════════════════════════

    public function wizard6(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        $books = $this->dedupeBooks(
            $this->applyTeacherBookScope(
                TeacherAdminBook::with(['subject', 'studyGroup'])
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('is_active', true),
                $book['isPrivileged']
            )->orderBy('semester')->get()
        );

        $catatan = AdminCatatanGuru::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->orderBy('note_date', 'desc')
            ->get();

        return view('nilai-guru.wizard6', compact('userId', 'book', 'books', 'catatan'));
    }

    public function wizard6Store(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'note_date' => 'required|date',
            'student_note' => 'nullable|string',
            'learning_note' => 'nullable|string',
        ]);

        $book = $this->loadAdminBook($userId, $adminBookId, true);

        AdminCatatanGuru::updateOrCreate(
            [
                'admin_book_id' => $book['adminBook']->id,
                'semester' => $book['adminBook']->semester,
                'note_date' => $request->note_date,
            ],
            [
                'academic_year_id' => $book['adminBook']->academic_year_id,
                'student_note' => $request->student_note ?? null,
                'learning_note' => $request->learning_note ?? null,
            ]
        );

        return redirect()->back()->with('success', 'Catatan Guru berhasil disimpan.');
    }

    // ══════════════════════════════════════════════════════════════════
    // AUTO-SAVE — debounced per-cell AJAX save
    // ══════════════════════════════════════════════════════════════════

    public function autoSave(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId, true);

        abort_if($book['adminBook']->isPublished(), 403, 'Nilai buku ini sudah dipublikasikan ke wali. Batalkan publikasi terlebih dahulu.');
        $semester = $book['adminBook']->semester;
        $academicYearId = $book['adminBook']->academic_year_id;
        $data = $request->all();

        Log::info('autoSave called', [
            'type' => $request->input('type'),
            'sumatif' => $data['sumatif'] ?? null,
        ]);

        switch ($request->input('type')) {
            case 'formatif':
                return response()->json([
                    'saved' => true,
                    'skipped' => false,
                    'saved_rows' => $this->saveFormatifRows($book, (array) ($data['formatif'] ?? [])),
                ]);

            case 'sumatif':
                $service = app(SumatifHarianService::class);
                $savedRows = [];

                foreach ($data['sumatif'] ?? [] as $sid => $flds) {
                    $hasAny = collect($flds)->filter(fn ($v) => ! is_array($v) && $v !== '' && is_numeric(trim((string) $v)))->isNotEmpty()
                        || collect($flds['sh'] ?? [])->filter(fn ($v) => $v !== '' && is_numeric($v))->isNotEmpty();

                    if (! $hasAny) {
                        continue;
                    }

                    $input = [
                        'sts_murni' => array_key_exists('sts_murni', $flds) ? $flds['sts_murni'] : null,
                        'sts' => array_key_exists('sts', $flds) ? $flds['sts'] : null,
                        'sas_murni' => array_key_exists('sas_murni', $flds) ? $flds['sas_murni'] : null,
                        'sas' => array_key_exists('sas', $flds) ? $flds['sas'] : null,
                        'raport_sts' => array_key_exists('raport_sts', $flds) ? $flds['raport_sts'] : null,
                        'ket' => $flds['ket'] ?? null,
                        // Fallback form lama
                        's1' => $flds['s1'] ?? null,
                        's2' => $flds['s2'] ?? null,
                        's3' => $flds['s3'] ?? null,
                        's4' => $flds['s4'] ?? null,
                        's5' => $flds['s5'] ?? null,
                        's6' => $flds['s6'] ?? null,
                    ];

                    // Aturan: Nilai Murni STS/SAS wajib diisi sebelum kolom STS/SAS (autosave).
                    $hasSts = isset($flds['sts']) && $flds['sts'] !== '' && $flds['sts'] !== null;
                    $hasSas = isset($flds['sas']) && $flds['sas'] !== '' && $flds['sas'] !== null;
                    $hasStsMurni = isset($flds['sts_murni']) && $flds['sts_murni'] !== '' && $flds['sts_murni'] !== null;
                    $hasSasMurni = isset($flds['sas_murni']) && $flds['sas_murni'] !== '' && $flds['sas_murni'] !== null;

                    if (($hasSts && ! $hasStsMurni) || ($hasSas && ! $hasSasMurni)) {
                        return response()->json([
                            'saved' => false,
                            'message' => 'Isi Nilai Murni STS/SAS (hasil ujian) terlebih dahulu sebelum mengisi kolom STS/SAS.',
                        ], 422);
                    }

                    if (array_key_exists('sh', $flds) && is_array($flds['sh'])) {
                        $input['sh'] = $flds['sh'];
                    }

                    // Pertahankan S6 otomatis (rata-rata formatif) agar tidak terhapus saat autosave SH.
                    $formatifRow = NilaiFormatif::where('admin_book_id', $book['adminBook']->id)
                        ->where('student_id', $sid)
                        ->where('semester', $book['adminBook']->semester)
                        ->first();
                    $existingRow = NilaiSumatif::where('admin_book_id', $book['adminBook']->id)
                        ->where('student_id', $sid)
                        ->where('semester', $book['adminBook']->semester)
                        ->first();
                    $s6Keep = ($formatifRow && $formatifRow->nr_final !== null)
                        ? (float) $formatifRow->nr_final
                        : ($existingRow?->s6 !== null ? (float) $existingRow->s6 : null);

                    if ($s6Keep !== null) {
                        $input['s6'] = $s6Keep;
                        if (isset($input['sh']) && is_array($input['sh'])) {
                            $input['sh']['s6'] = $s6Keep;
                        }
                    }

                    $row = $service->upsertSumatif($book['adminBook'], (string) $sid, $input);

                    $savedRows[] = [
                        'student_id' => (string) $sid,
                        'rs' => $row->rs !== null ? (float) $row->rs : null,
                        'rsa' => $row->rsa !== null ? (float) $row->rsa : null,
                        'nr_murni' => $row->nr_murni !== null ? (float) $row->nr_murni : null,
                        'nr_final' => $row->nr_final !== null ? (float) $row->nr_final : null,
                    ];
                }

                if (empty($savedRows)) {
                    return response()->json(['saved' => true, 'skipped' => true]);
                }

                return response()->json([
                    'saved' => true,
                    'saved_rows' => $savedRows,
                ]);

            case 'formatif':
                $savedRows = [];
                foreach ($data['formatif'] ?? [] as $sid => $flds) {
                    $hasAny = collect($flds)->filter(fn ($v) => $v !== '' && is_numeric(trim((string) $v)))->isNotEmpty();
                    if (! $hasAny) {
                        continue;
                    }

                    // NR Final = rata-rata skor yang terisi
                    $scores = collect(['skor_lkpd', 'skor_diskusi', 'skor_kuis', 'skor_antarteman'])
                        ->map(fn ($k) => $flds[$k] ?? null)
                        ->filter(fn ($v) => is_numeric($v))
                        ->values()
                        ->toArray();
                    $nrFinal = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;

                    NilaiFormatif::updateOrCreate(
                        ['admin_book_id' => $book['adminBook']->id, 'student_id' => $sid, 'semester' => $semester],
                        [
                            'academic_year_id' => $academicYearId,
                            'skor_lkpd' => $flds['skor_lkpd'] ?? null,
                            'skor_diskusi' => $flds['skor_diskusi'] ?? null,
                            'skor_kuis' => $flds['skor_kuis'] ?? null,
                            'skor_antarteman' => $flds['skor_antarteman'] ?? null,
                            'nr_final' => $nrFinal,
                            'ket' => $flds['ket'] ?? null,
                        ]
                    );
                    $savedRows[] = ['student_id' => $sid, 'nr_final' => $nrFinal];
                }
                if (empty($savedRows)) {
                    return response()->json(['saved' => true, 'skipped' => true]);
                }

                return response()->json(['saved' => true, 'saved_rows' => $savedRows]);

            case 'penghargaan':
                $savedRows = [];
                foreach ($data['penghargaan'] ?? [] as $sid => $flds) {
                    $hasAny = collect($flds)->filter(fn ($v) => $v !== '' && is_numeric(trim((string) $v)))->isNotEmpty();
                    if (! $hasAny) {
                        continue;
                    }

                    // NR Final = rata-rata komponen yang terisi
                    $scores = collect(['jujur', 'disiplin', 'peduli', 'adab', 'kehadiran', 'keaktifan'])
                        ->map(fn ($k) => $flds[$k] ?? null)
                        ->filter(fn ($v) => is_numeric($v))
                        ->values()
                        ->toArray();
                    $nrFinal = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;

                    PenghargaanAkademik::updateOrCreate(
                        ['admin_book_id' => $book['adminBook']->id, 'student_id' => $sid, 'semester' => $semester],
                        [
                            'academic_year_id' => $academicYearId,
                            'jujur' => $flds['jujur'] ?? null,
                            'disiplin' => $flds['disiplin'] ?? null,
                            'peduli' => $flds['peduli'] ?? null,
                            'adab' => $flds['adab'] ?? null,
                            'kehadiran' => $flds['kehadiran'] ?? null,
                            'keaktifan' => $flds['keaktifan'] ?? null,
                            'nr_final' => $nrFinal,
                            'ket' => $flds['ket'] ?? null,
                        ]
                    );
                    $savedRows[] = ['student_id' => $sid, 'nr_final' => $nrFinal];
                }
                if (empty($savedRows)) {
                    return response()->json(['saved' => true, 'skipped' => true]);
                }

                return response()->json(['saved' => true, 'saved_rows' => $savedRows]);

            default:
                return response()->json(['saved' => false, 'error' => 'Unknown type'], 400);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPER — load admin book
    // ══════════════════════════════════════════════════════════════════

    /**
     * Konteks rencana pembelajaran untuk satu buku administrasi:
     * ATP/TP, PROSEM (distribusi pekan), RPM, dan pekan efektif.
     * Semua dari sumber data yang sama (Kalender → Pekan Efektif → ATP → PROSEM → RPM).
     *
     * @return array<string, mixed>
     */
    private function planContextForBook(TeacherAdminBook $adminBook): array
    {
        $semester = $adminBook->semester === 'genap' ? 'genap' : 'ganjil';
        $gradeLevelId = $adminBook->studyGroup?->grade_level_id;

        $atp = AlurTujuanPembelajaran::query()
            ->where('school_id', $adminBook->school_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('semester', $semester)
            ->where('subject_id', $adminBook->subject_id)
            ->when($gradeLevelId, fn ($q) => $q->where('grade_level_id', $gradeLevelId), fn ($q) => $q->whereNull('grade_level_id'))
            ->first();

        $tps = collect();

        if ($atp) {
            $tps = $atp->items()->with('tujuanPembelajaran')->get()
                ->map->tujuanPembelajaran->filter()->values();
        }

        if ($tps->isEmpty()) {
            $tps = TujuanPembelajaran::query()
                ->active()
                ->bySchool($adminBook->school_id)
                ->byAcademicYear($adminBook->academic_year_id)
                ->bySemester($semester)
                ->where('subject_id', $adminBook->subject_id)
                ->when($gradeLevelId, function ($q) use ($gradeLevelId) {
                    $q->where(function ($q2) use ($gradeLevelId) {
                        $q2->where('grade_level_id', $gradeLevelId)->orWhereNull('grade_level_id');
                    });
                })
                ->orderBy('urutan')
                ->orderBy('kode_tp')
                ->get();
        }

        $prosem = Prosem::query()
            ->where('school_id', $adminBook->school_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('semester', $semester)
            ->where('subject_id', $adminBook->subject_id)
            ->when($gradeLevelId, fn ($q) => $q->where('grade_level_id', $gradeLevelId), fn ($q) => $q->whereNull('grade_level_id'))
            ->first();

        $prosemItems = $prosem
            ? $prosem->items()->with('tujuanPembelajaran:id,kode_tp,deskripsi')->orderBy('urutan')->get()
            : collect();

        // Pekan efektif (hari efektif > 0) — untuk saran pekan berdasarkan tanggal.
        $semesterInt = $semester === 'genap' ? 2 : 1;
        $weeks = PekanEfektif::query()
            ->bySchool($adminBook->school_id)
            ->byAcademicYear($adminBook->academic_year_id)
            ->bySemester($semesterInt)
            ->where('jumlah_hari', '>', 0)
            ->orderBy('minggu_ke')
            ->get(['minggu_ke', 'tanggal_mulai', 'tanggal_selesai', 'jenis', 'jumlah_hari']);

        $rpmOptions = PerangkatPembelajaran::query()
            ->where('school_id', $adminBook->school_id)
            ->where('academic_year_id', $adminBook->academic_year_id)
            ->where('semester', $semester)
            ->where('subject_id', $adminBook->subject_id)
            ->when($gradeLevelId, function ($q) use ($gradeLevelId) {
                $q->where(function ($q2) use ($gradeLevelId) {
                    $q2->where('grade_level_id', $gradeLevelId)->orWhereNull('grade_level_id');
                });
            })
            ->orderBy('judul')
            ->get(['id', 'judul', 'tipe', 'teacher_id']);

        return compact('atp', 'tps', 'prosem', 'prosemItems', 'weeks', 'rpmOptions');
    }

    /**
     * Scope buku untuk guru: buku sendiri + buku KONTEKS tempat ia mengajar
     * (co-teacher) — kebijakan satu buku per mapel+rombel+TA+semester.
     */
    private function applyTeacherBookScope($query, bool $isPrivileged)
    {
        if ($isPrivileged) {
            return $query;
        }

        $contexts = TeachingAssignment::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->get(['subject_id', 'study_group_id', 'academic_year_id']);

        return $query->where(function ($qq) use ($contexts) {
            $qq->where('teacher_id', auth()->id());
            foreach ($contexts as $c) {
                $qq->orWhere(fn ($x) => $x
                    ->where('subject_id', $c->subject_id)
                    ->where('study_group_id', $c->study_group_id)
                    ->where('academic_year_id', $c->academic_year_id));
            }
        });
    }

    /** Satu konteks = satu buku (dedupe tampilan bila data lama duplikat). */
    private function dedupeBooks($books)
    {
        return $books
            ->unique(fn ($b) => $b->subject_id.'|'.$b->study_group_id.'|'.$b->academic_year_id.'|'.$b->semester)
            ->values();
    }

    private function loadAdminBook(string $userId, string $adminBookId, bool $forWrite = false): array
    {
        $schoolId = request()->attributes->get('schoolContextId');
        $authUser = auth()->user();
        $isPrivileged = (bool) ($authUser?->hasAnyRole(['Kepala Sekolah'])
            || app(KurikulumAccess::class)->isKurikulumTeam($authUser));

        // Cast integer adminBookId
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel', 'academicYear', 'teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isPrivileged, fn ($q) => $this->applyTeacherBookScope($q, false))
            ->where('id', $bookId)
            ->firstOrFail();

        if ($forWrite && $adminBook->isLocked()) {
            abort(403, 'Buku nilai sudah dikunci final — hubungi tim kurikulum untuk membuka.');
        }

        // Kebijakan: buku tahun ajaran lampau bersifat arsip permanen
        // (santri sudah naik kelas); guru/kelas berikutnya memakai buku baru.
        // Buku tetap dapat diubah bila TA-nya TA aktif ATAU TA operasional
        // (TA terbaru yang memiliki penugasan mengajar aktif di sekolah tsb).
        if ($forWrite) {
            $activeYearId = AcademicYear::where('is_active', true)->value('id');
            $bookYearId = (string) $adminBook->academic_year_id;

            $operationalYearId = TeachingAssignment::query()
                ->where('school_id', $adminBook->school_id)
                ->where('status', 'active')
                ->join('academic_years as ay', 'ay.id', '=', 'teaching_assignments.academic_year_id')
                ->orderByDesc('ay.start_date')
                ->value('teaching_assignments.academic_year_id');

            $writable = ($activeYearId && $bookYearId === (string) $activeYearId)
                || ($operationalYearId && $bookYearId === (string) $operationalYearId);

            if (! $writable) {
                abort(403, 'Buku tahun ajaran lalu bersifat arsip — data tidak dapat diubah.');
            }
        }

        return [
            'adminBook' => $adminBook,
            'isPrivileged' => $isPrivileged,
            'userId' => $userId,
        ];
    }
}
