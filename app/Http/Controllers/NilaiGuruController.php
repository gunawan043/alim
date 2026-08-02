<?php

namespace App\Http\Controllers;

use App\Models\AdminCatatanGuru;
use App\Models\AdminJurnalPembelajaran;
use App\Models\AdminPresensiMapel;
use App\Models\AdminPresensiSiswa;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PenghargaanAkademik;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NilaiGuruController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // MENU UTAMA — index guru mapel
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = User::findOrFail($userId);

        $isPrivileged = $user->hasAnyRole([
            'Admin Tata Usaha', 'Wakil Kepala Sekolah', 'Kepala Sekolah Pondok',
        ]);

        // Daftar mapel yang diampu guru ini
        $baseQuery = TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isPrivileged, fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester');

        $adminBooks = (clone $baseQuery)->get()->groupBy('semester');

        $subjects = (clone $baseQuery)
            ->pluck('subject_id')->unique()
            ->map(fn ($id) => \App\Models\Subject::find($id))
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
            $firstBook = TeacherAdminBook::with(['subject', 'studyGroup'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where('teacher_id', $userId)
                ->where('is_active', true)
                ->orderBy('semester')->first();

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
        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

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
            ->groupBy(fn ($m) => \Carbon\Carbon::parse($m->attendance_date)->format('Y-m'))
            ->map(fn ($group, $key) => [
                'month' => $key,
                'label' => \Carbon\Carbon::parse($key.'-01')->translatedFormat('F Y'),
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
            $rekapMonthObj = \Carbon\Carbon::parse($rekapMonth.'-01');

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
            'status.*' => 'in:hadir,izin,sakit,alpa',
        ]);

        $book = $this->loadAdminBook($userId, $adminBookId);
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

        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

        $journals = AdminJurnalPembelajaran::where('admin_book_id', $book['adminBook']->id)
            ->orderBy('meeting_number', 'asc')
            ->get();

        return view('nilai-guru.wizard2', compact('userId', 'book', 'books', 'journals'));
    }

    public function wizard2Store(Request $request, string $userId, string $adminBookId)
    {
        $request->validate([
            'meeting_number' => 'required|integer|min:1',
            'meeting_date' => 'required|date',
        ]);

        $book = $this->loadAdminBook($userId, $adminBookId);

        AdminJurnalPembelajaran::updateOrCreate(
            [
                'admin_book_id' => $book['adminBook']->id,
                'meeting_number' => (int) $request->meeting_number,
                'semester' => $book['adminBook']->semester,
            ],
            [
                'academic_year_id' => $book['adminBook']->academic_year_id,
                'meeting_date' => $request->meeting_date,
                'time_in' => $request->time_in ?? null,
                'time_out' => $request->time_out ?? null,
                'material' => $request->material ?? null,
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

        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $sumatifMap = NilaiSumatif::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        return view('nilai-guru.wizard3', compact('userId', 'book', 'books', 'students', 'sumatifMap'));
    }

    public function wizard3Store(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);

        $request->validate([
            'sumatif' => 'required|array',
            'sumatif.*.s1' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s2' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s3' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s4' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s5' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.s6' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sts' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.sas' => 'nullable|numeric|min:0|max:100',
            'sumatif.*.raport_sts' => 'nullable|numeric|min:0|max:100',
        ]);

        $wRs = (float) ($book['adminBook']->nr_final_weight_rs ?? 50.0);
        $wSts = (float) ($book['adminBook']->nr_final_weight_sts ?? 25.0);
        $wSas = (float) ($book['adminBook']->nr_final_weight_sas ?? 25.0);

        foreach ($request->sumatif as $studentId => $data) {
            $rs = NilaiSumatif::calcRs($data);
            $raportSts = $data['raport_sts'] !== '' && is_numeric($data['raport_sts'])
                ? (float) $data['raport_sts'] : null;
            $rsa = NilaiSumatif::calcRsa(
                $data['sts'] !== '' && is_numeric($data['sts']) ? (float) $data['sts'] : null,
                $data['sas'] !== '' && is_numeric($data['sas']) ? (float) $data['sas'] : null,
                $raportSts
            );
            $nrMurni = NilaiSumatif::calcNrMurni($rs, $rsa);
            $nrFinal = NilaiSumatif::calcNrFinal(
                $rs,
                $data['sts'] !== '' && is_numeric($data['sts']) ? (float) $data['sts'] : null,
                $data['sas'] !== '' && is_numeric($data['sas']) ? (float) $data['sas'] : null,
                $wRs, $wSts, $wSas,
                $raportSts
            );

            NilaiSumatif::updateOrCreate(
                [
                    'admin_book_id' => $book['adminBook']->id,
                    'student_id' => $studentId,
                    'semester' => $book['adminBook']->semester,
                ],
                [
                    'academic_year_id' => $book['adminBook']->academic_year_id,
                    's1' => $data['s1'] ?? null,
                    's2' => $data['s2'] ?? null,
                    's3' => $data['s3'] ?? null,
                    's4' => $data['s4'] ?? null,
                    's5' => $data['s5'] ?? null,
                    's6' => $data['s6'] ?? null,
                    'rs' => $rs,
                    'sts' => $data['sts'] !== '' && is_numeric($data['sts']) ? (float) $data['sts'] : null,
                    'raport_sts' => $raportSts,
                    'sas' => $data['sas'] !== '' && is_numeric($data['sas']) ? (float) $data['sas'] : null,
                    'rsa' => $rsa,
                    'nr_murni' => $nrMurni,
                    'nr_final' => $nrFinal,
                    'ket' => $data['ket'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Nilai Sumatif berhasil disimpan.');
    }

    public function wizard3Bobot(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);

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

        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

        $students = StudentClassHistory::with('student')
            ->where('study_group_id', $book['adminBook']->study_group_id)
            ->where('academic_year_id', $book['adminBook']->academic_year_id)
            ->where('is_active', true)
            ->orderBy('attendance_number')->get();

        $formatifMap = NilaiFormatif::where('admin_book_id', $book['adminBook']->id)
            ->where('semester', $book['adminBook']->semester)
            ->get()
            ->keyBy('student_id');

        return view('nilai-guru.wizard4', compact('userId', 'book', 'books', 'students', 'formatifMap'));
    }

    public function wizard4Store(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);

        $request->validate([
            'formatif' => 'required|array',
            'formatif.*.skor_lkpd' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_diskusi' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_kuis' => 'nullable|numeric|min:0|max:100',
            'formatif.*.skor_antarteman' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($request->formatif as $studentId => $data) {
            $scores = collect(['skor_lkpd', 'skor_diskusi', 'skor_kuis', 'skor_antarteman'])
                ->map(fn ($k) => $data[$k] ?? null)
                ->filter(fn ($v) => is_numeric($v))
                ->values()
                ->toArray();
            $nrFinal = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;

            NilaiFormatif::updateOrCreate(
                [
                    'admin_book_id' => $book['adminBook']->id,
                    'student_id' => $studentId,
                    'semester' => $book['adminBook']->semester,
                ],
                [
                    'academic_year_id' => $book['adminBook']->academic_year_id,
                    'skor_lkpd' => $data['skor_lkpd'] ?? null,
                    'skor_diskusi' => $data['skor_diskusi'] ?? null,
                    'skor_kuis' => $data['skor_kuis'] ?? null,
                    'skor_antarteman' => $data['skor_antarteman'] ?? null,
                    'nr_final' => $nrFinal,
                    'ket' => $data['ket'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Nilai Formatif berhasil disimpan.');
    }

    // ══════════════════════════════════════════════════════════════════
    // WIZARD 5 — PENGHARGAAN AKADEMIK
    // ══════════════════════════════════════════════════════════════════

    public function wizard5(Request $request, string $userId, string $adminBookId)
    {
        $book = $this->loadAdminBook($userId, $adminBookId);
        $schoolId = $request->attributes->get('schoolContextId');

        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

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
        $book = $this->loadAdminBook($userId, $adminBookId);

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

        $books = TeacherAdminBook::with(['subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $book['isPrivileged'], fn ($q) => $q->where('teacher_id', $userId))
            ->where('is_active', true)
            ->orderBy('semester')->get();

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

        $book = $this->loadAdminBook($userId, $adminBookId);

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
        $book = $this->loadAdminBook($userId, $adminBookId);
        $semester = $book['adminBook']->semester;
        $academicYearId = $book['adminBook']->academic_year_id;
        $data = $request->all();

        \Illuminate\Support\Facades\Log::info('autoSave called', [
            'type' => $request->input('type'),
            'sumatif' => $data['sumatif'] ?? null,
        ]);

        switch ($request->input('type')) {
            case 'sumatif':
                // Bobot NR Final dari admin_book (default: RS=50, STS=25, SAS=25)
                $wRs = (float) ($book['adminBook']->nr_final_weight_rs ?? 50.0);
                $wSts = (float) ($book['adminBook']->nr_final_weight_sts ?? 25.0);
                $wSas = (float) ($book['adminBook']->nr_final_weight_sas ?? 25.0);

                $savedRows = [];

                foreach ($data['sumatif'] ?? [] as $sid => $flds) {
                    $hasAny = collect($flds)->filter(fn ($v) => $v !== '' && is_numeric(trim((string) $v)))->isNotEmpty();
                    if (! $hasAny) {
                        continue;
                    }

                    // RS = rata-rata S1-S6
                    $shFilled = collect(['s1', 's2', 's3', 's4', 's5', 's6'])
                        ->map(fn ($k) => $flds[$k] ?? null)
                        ->filter(fn ($v) => is_numeric($v))
                        ->values()
                        ->toArray();
                    $rs = count($shFilled) > 0 ? round(array_sum($shFilled) / count($shFilled), 2) : null;

                    $stsVal = is_numeric($flds['sts'] ?? null) ? (float) $flds['sts'] : null;
                    $sasVal = is_numeric($flds['sas'] ?? null) ? (float) $flds['sas'] : null;
                    $raportStsVal = is_numeric($flds['raport_sts'] ?? null) ? (float) $flds['raport_sts'] : null;

                    // RSA = (raport_sts|sts + SAS) / 2
                    $rsa = NilaiSumatif::calcRsa($stsVal, $sasVal, $raportStsVal);

                    // NR Murni = (RS + RSA) / 2
                    $nrMurni = NilaiSumatif::calcNrMurni($rs, $rsa);

                    // NR Final = (RS × wRs + raport_sts|sts × wSts + SAS × wSas) / 100
                    $nrFinal = NilaiSumatif::calcNrFinal($rs, $stsVal, $sasVal, $wRs, $wSts, $wSas, $raportStsVal);

                    NilaiSumatif::updateOrCreate(
                        ['admin_book_id' => $book['adminBook']->id, 'student_id' => $sid, 'semester' => $semester],
                        [
                            'academic_year_id' => $academicYearId,
                            's1' => $flds['s1'] ?? null, 's2' => $flds['s2'] ?? null,
                            's3' => $flds['s3'] ?? null, 's4' => $flds['s4'] ?? null,
                            's5' => $flds['s5'] ?? null, 's6' => $flds['s6'] ?? null,
                            'rs' => $rs,
                            'sts' => $stsVal,
                            'raport_sts' => $raportStsVal,
                            'sas' => $sasVal,
                            'rsa' => $rsa,
                            'nr_murni' => $nrMurni,
                            'nr_final' => $nrFinal,
                            'ket' => $flds['ket'] ?? null,
                        ]
                    );
                    $savedRows[] = ['student_id' => $sid, 'rs' => $rs, 'rsa' => $rsa, 'nr_murni' => $nrMurni, 'nr_final' => $nrFinal];
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

    private function loadAdminBook(string $userId, string $adminBookId): array
    {
        $schoolId = request()->attributes->get('schoolContextId');
        $user = User::findOrFail($userId);

        $isPrivileged = $user->hasAnyRole([
            'Admin Tata Usaha', 'Wakil Kepala Sekolah', 'Kepala Sekolah Pondok',
        ]);

        // Cast integer adminBookId
        $bookId = is_numeric($adminBookId) ? (int) $adminBookId : $adminBookId;

        $adminBook = TeacherAdminBook::with(['subject', 'studyGroup', 'studyGroup.gradeLevel', 'academicYear', 'teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $isPrivileged, fn ($q) => $q->where('teacher_id', $userId))
            ->where('id', $bookId)
            ->firstOrFail();

        return [
            'adminBook' => $adminBook,
            'isPrivileged' => $isPrivileged,
            'userId' => $userId,
        ];
    }
}
