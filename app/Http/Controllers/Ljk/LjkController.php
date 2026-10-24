<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ljk;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkSheet;
use App\Models\PaketSoal;
use App\Models\StudentClassHistory;
use App\Models\TeacherAdminBook;
use App\Services\Ljk\LjkAccess;
use App\Services\Ljk\LjkExamService;
use App\Services\Ljk\LjkGradingService;
use App\Services\Ljk\LjkPdfFonts;
use App\Services\Ljk\LjkSheetService;
use App\Services\KurikulumAccess;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * LJK — kelola ujian berbasis Paket Soal, terbitkan lembar, dan cetak PDF.
 */
class LjkController extends Controller
{
    use AuthorizesAcademicScope;

    public function __construct(
        private readonly LjkExamService $exams,
        private readonly LjkSheetService $sheets,
        private readonly LjkGradingService $grading,
        private readonly LjkAccess $access,
        private readonly KurikulumAccess $kurikulum,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $this->contextSchoolId($request);
        $user = $request->user();

        // Basis query (scope sekolah + hak akses) — dipakai statistik & daftar.
        $base = LjkExam::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if (! $this->access->isOfficer($user)) {
            $books = TeacherAdminBook::query()->where('teacher_id', $user->id)->pluck('id')
                ->merge(
                    TeacherAdminBook::query()
                        ->whereIn('subject_id', function ($q) use ($user) {
                            $q->select('subject_id')->from('teaching_assignments')
                                ->where('teacher_id', $user->id)->where('status', 'active');
                        })
                        ->pluck('id')
                )
                ->unique();

            $base->where(function ($q) use ($books, $user) {
                $q->whereIn('admin_book_id', $books)->orWhere('created_by', $user->id);
            });
        }

        $statistics = [
            'total' => (clone $base)->count(),
            'aktif' => (clone $base)->whereIn('status', [LjkExam::STATUS_AKTIF, LjkExam::STATUS_PENGUMPULAN, LjkExam::STATUS_REVIEW])->count(),
            'final' => (clone $base)->where('status', LjkExam::STATUS_FINAL)->count(),
            'tersinkron' => (clone $base)->where('status', LjkExam::STATUS_TERSINKRON)->count(),
            'dibatalkan' => (clone $base)->where('status', LjkExam::STATUS_DIBATALKAN)->count(),
            'status_counts' => (clone $base)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ];

        $query = (clone $base)
            ->with([
                'paketSoal:id,judul,kode_paket',
                'studyGroup:id,name',
                'subject:id,name',
                'adminBook.teacher:id,name',
            ])
            ->withCount(['participants', 'sheets'])
            ->withCount(['sheets as scanned_sheets_count' => fn ($q) => $q->where('status', LjkSheet::STATUS_SCANNED)])
            ->orderByDesc('created_at');

        $status = $request->query('status');
        if ($status && array_key_exists($status, LjkExam::STATUS_OPTIONS)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('paketSoal', fn ($p) => $p->where('judul', 'like', "%{$search}%")
                        ->orWhere('kode_paket', 'like', "%{$search}%"));
            });
        }

        $jenis = $request->query('jenis');
        if ($jenis && in_array($jenis, LjkExam::JENIS_UJIAN, true)) {
            $query->where('jenis_ujian', $jenis);
        }

        $exams = $query->paginate(12)->withQueryString();

        return view('ljk.index', compact('exams', 'statistics', 'userId', 'status', 'search', 'jenis'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $this->contextSchoolId($request);
        $user = $request->user();

        // Buku nilai yang boleh dikelola guru ini (sumber rombel & mapel).
        $books = TeacherAdminBook::query()
            ->with(['subject:id,name', 'studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,level,name', 'academicYear:id,name'])
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (TeacherAdminBook $book) => $this->access->managesBook($user, $book) || $this->access->isOfficer($user))
            ->unique(fn (TeacherAdminBook $book) => $book->subject_id.'|'.$book->study_group_id.'|'.$book->academic_year_id.'|'.$book->semester)
            ->values();

        if ($books->isEmpty() && $this->access->isOfficer($user)) {
            $books = TeacherAdminBook::query()
                ->with(['subject:id,name', 'studyGroup:id,name,grade_level_id', 'studyGroup.gradeLevel:id,level,name', 'academicYear:id,name'])
                ->where('is_active', true)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderByDesc('created_at')
                ->get();
        }

        // Paket soal final untuk mapel & TA buku yang dipilih guru.
        $paketQuery = PaketSoal::query()
            ->where('is_published', true)
            ->whereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED])
            ->with([
                'kisiKisi:id,subject_id,academic_year_id,semester,school_id,jenis_ujian,grade_level_id',
                'kisiKisi.subject:id,name',
                'kisiKisi.gradeLevel:id,level,name',
                'items.soal:id,bahasa,pertanyaan',
            ])
            ->orderByDesc('published_at');

        if ($schoolId) {
            $paketQuery->whereHas('kisiKisi', fn ($q) => $q->where('school_id', $schoolId));
        }

        $pakets = $paketQuery->limit(200)->get();

        // Metadata paket untuk isian otomatis di form.
        $paketMeta = $pakets->mapWithKeys(function (PaketSoal $paket) {
            return [$paket->id => [
                'jenis' => $paket->kisiKisi?->jenis_ujian ?: 'sts',
                'bahasa' => $this->bahasaForPaket($paket),
                'subject_name' => $paket->kisiKisi?->subject?->name ?? '',
                'grade_name' => $paket->kisiKisi?->gradeLevel?->name ?? '',
                'instruksi' => (string) ($paket->instruksi_umum ?? ''),
            ]];
        });

        // Kolom sumatif harian (untuk jenis ujian ulangan/try out/latihan).
        $sumatifColumns = app(\App\Services\SumatifHarianService::class)->defaultColumns();

        // Isi otomatis berdasarkan paket soal yang dipilih.
        $requestedPaket = $pakets->firstWhere(
            'id',
            old('paket_soal_id', $request->input('paket_soal_id'))
        );
        $autoBook = null;
        $defaultJenis = 'sts';
        $defaultBahasa = 'gabungan';
        $defaultTitle = 'STS ';
        $defaultInstruksi = '';
        $defaultTanggal = now()->toDateString();

        if ($requestedPaket) {
            $requestedPaket->loadMissing(['kisiKisi.subject', 'kisiKisi.gradeLevel', 'items.soal']);
            $kisi = $requestedPaket->kisiKisi;
            $defaultJenis = $kisi?->jenis_ujian ?: 'sts';
            $defaultInstruksi = (string) ($requestedPaket->instruksi_umum ?? '');

            $autoBook = $books->first(function ($book) use ($kisi) {
                return (! $kisi?->subject_id || $book->subject_id === $kisi->subject_id)
                    && (! $kisi?->academic_year_id || $book->academic_year_id === $kisi->academic_year_id)
                    && (! $kisi?->semester || $book->semester === $kisi->semester)
                    && (! $kisi?->grade_level_id || $book->studyGroup?->grade_level_id === $kisi->grade_level_id);
            });

            $defaultBahasa = $paketMeta[$requestedPaket->id]['bahasa'] ?? 'gabungan';

            $subjectName = $kisi?->subject?->name ?? 'Mapel';
            $groupName = $autoBook?->studyGroup?->name ?? $kisi?->gradeLevel?->name;
            $defaultTitle = trim($this->jenisLabel($defaultJenis).' '.$subjectName.' '.($groupName ?? ''));
        }

        return view('ljk.create', compact(
            'books', 'pakets', 'sumatifColumns', 'userId',
            'requestedPaket', 'autoBook', 'defaultJenis', 'defaultBahasa', 'defaultTitle', 'defaultInstruksi',
            'defaultTanggal', 'paketMeta'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:150',
            'paket_soal_id' => 'required|uuid',
            'admin_book_id' => 'required|uuid',
            'study_group_id' => 'nullable|uuid',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'bahasa' => 'nullable|in:latin,gabungan,arab',
            'nilai_target_column' => 'nullable|string|max:60',
            'tanggal' => 'nullable|date',
            'waktu_mulai' => 'nullable|date_format:H:i',
            'waktu_selesai' => 'nullable|date_format:H:i',
            'instruksi' => 'nullable|string|max:2000',
            'canonical_order' => 'nullable|boolean',
            'peserta' => 'nullable|array',
            'peserta.*' => 'uuid',
        ]);

        $book = TeacherAdminBook::with(['studyGroup', 'academicYear'])->findOrFail($data['admin_book_id']);
        $this->ensureSchoolScope($request, $book->school_id, 'Buku nilai tidak ditemukan pada satuan pendidikan Anda.');

        if (! $this->access->managesBook($request->user(), $book) && ! $this->access->isOfficer($request->user())) {
            abort(403, 'Anda tidak berwenang menerbitkan LJK untuk buku nilai ini.');
        }

        $paket = PaketSoal::with('kisiKisi')->findOrFail($data['paket_soal_id']);
        if (! $paket->isFinal()) {
            return back()->withInput()->with('error', 'Paket soal harus final (approved & published) untuk diterbitkan LJK.');
        }

        if ($paket->kisiKisi?->subject_id !== $book->subject_id) {
            return back()->withInput()->with('error', 'Paket soal tidak sesuai dengan mata pelajaran buku nilai ini.');
        }

        try {
            $exam = $this->exams->createExam([
                'judul' => $data['judul'],
                'paket_soal_id' => $paket->id,
                'admin_book_id' => $book->id,
                'study_group_id' => $data['study_group_id'] ?? $book->study_group_id,
                'jenis_ujian' => $data['jenis_ujian'],
                'bahasa' => $data['bahasa'] ?? null,
                'nilai_target' => in_array($data['jenis_ujian'], ['sts', 'sas'], true)
                    ? null
                    : [
                        'mode' => 'sumatif_column',
                        'column_id' => $data['nilai_target_column'] ?? 's1',
                        'label' => 'Kolom Sumatif Harian',
                    ],
                'tanggal' => $data['tanggal'] ?? null,
                'waktu_mulai' => $data['waktu_mulai'] ?? null,
                'waktu_selesai' => $data['waktu_selesai'] ?? null,
                'instruksi' => $data['instruksi'] ?? null,
                'canonical_order' => (bool) ($data['canonical_order'] ?? false),
            ], $request->user());

            $this->exams->provisionParticipants($exam, $data['peserta'] ?? []);
            $count = $this->exams->generateSheets($exam, $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('user.ljk.show', ['userId' => $userId, 'ljkUuid' => $exam->id])
            ->with('success', "LJK diterbitkan: {$exam->code} ({$count} lembar).");
    }

    public function show(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::with([
            'paketSoal:id,judul,kode_paket,versi',
            'studyGroup:id,name',
            'subject:id,name',
            'adminBook.teacher:id,name',
            'template',
            'academicYear:id,name',
            'school:id,name',
        ])->findOrFail($ljkUuid);

        $this->ensureSchoolScope($request, $exam->school_id, 'Ujian LJK tidak ditemukan pada satuan pendidikan Anda.');

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang melihat ujian LJK ini.');
        }

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->with(['student:id,name,nis', 'sheet'])
            ->orderBy('nomor_peserta')
            ->get();

        $summary = $this->grading->reviewSummary($exam);
        $digest = $this->exams->verifyPaketDigest($exam);
        $canonical = $this->exams->usesCanonicalOrder($exam);

        return view('ljk.show', compact('exam', 'participants', 'summary', 'digest', 'canonical', 'userId'));
    }

    /**
     * PDF lembar satu peserta (atau seluruh peserta).
     */
    public function pdf(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang mengunduh lembar LJK ini.');
        }

        $master = $request->boolean('master');
        $copies = max(1, min(100, (int) $request->query('copies', 1)));
        $participantId = $request->query('participant');

        $participants = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->when($participantId, fn ($q) => $q->where('id', $participantId))
            ->orderBy('nomor_peserta')
            ->with(['student', 'sheet.template'])
            ->get();

        if ($participants->isEmpty()) {
            abort(404, 'Peserta tidak ditemukan.');
        }

        $pages = [];

        if ($master) {
            // Master fotokopi: satu rancangan lembar tanpa nama/QR/data strip.
            $template = $participants->first();
            $canonical = $this->exams->usesCanonicalOrder($exam);
            $masterNote = $canonical
                ? null
                : 'Hanya berlaku untuk peserta '.$template->nomor_peserta.' — paket ini mengacak urutan soal/opsi. Terbitkan ulang dengan "Mode Fotokopi" agar master berlaku untuk semua peserta.';

            for ($i = 0; $i < $copies; $i++) {
                $pages[] = $this->sheets->renderSheetHtml($exam, $template, 1, true, $masterNote);
            }
        } else {
            foreach ($participants as $participant) {
                if (! $participant->sheet) {
                    continue;
                }

                $pageCount = max(1, (int) $participant->sheet->pages);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $pages[] = $this->sheets->renderSheetHtml($exam, $participant, $page);
                }

                $participant->sheet->markPrinted();
            }
        }

        if ($pages === []) {
            abort(404, 'Belum ada lembar yang diterbitkan.');
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            @page { margin: 0; }
            .page-break { page-break-after: always; }
        </style></head><body>'.implode('<div class="page-break"></div>', $pages).'</body></html>';

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        // Seluruh PDF LJK memakai font Cambria (fallback serif bila belum dipasang).
        LjkPdfFonts::configure($options);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'LJK-'.$exam->code
            .($master ? '-MASTER' : ($participantId ? '-'.$participants->first()?->nomor_peserta : ''))
            .'.pdf';

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    /**
     * Lihat soal ujian (naskah beku versi paket yang dipakai lembar ini).
     *
     * Kunci jawaban hanya ditampilkan bila pemakai berhak menurut kebijakan
     * kunci yang berlaku (tim kurikulum / guru serumpun).
     */
    public function questions(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::with([
            'paketSoal:id,judul,kode_paket,versi',
            'subject:id,name',
            'studyGroup:id,name',
            'template',
        ])->findOrFail($ljkUuid);

        $this->ensureSchoolScope($request, $exam->school_id, 'Ujian LJK tidak ditemukan pada satuan pendidikan Anda.');

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang melihat soal ujian ini.');
        }

        $items = $exam->paketSoal
            ? $exam->paketSoal->items()->with(['soal.options', 'soal.tujuanPembelajaran', 'soal.wacana'])->orderBy('urutan')->get()
            : collect();

        $canSeeKeys = $this->kurikulum->canViewAnswerKeys($request->user(), $exam->subject);

        $questions = $items->map(function ($item, $index) use ($canSeeKeys) {
            $konten = $item->kontenFinal();

            return [
                'nomor' => (int) ($konten['nomor'] ?? $index + 1),
                'jenis' => (string) ($konten['jenis'] ?? 'pg'),
                'pertanyaan' => (string) ($konten['pertanyaan'] ?? ''),
                'pembahasan' => $canSeeKeys ? ($konten['pembahasan'] ?? null) : null,
                'wacana' => $konten['wacana'] ?? null,
                'bobot' => (float) ($konten['bobot'] ?? 0),
                'tp' => $item->soal?->tujuanPembelajaran?->kode_tp,
                'opsi' => collect($konten['opsi'] ?? [])->map(fn ($o) => [
                    'label' => (string) ($o['label'] ?? ''),
                    'teks' => (string) ($o['teks'] ?? ''),
                    'kunci' => $canSeeKeys ? (bool) ($o['kunci'] ?? false) : null,
                ])->values()->all(),
            ];
        })->values();

        $digest = $this->exams->verifyPaketDigest($exam);
        $canonical = $this->exams->usesCanonicalOrder($exam);

        return view('ljk.soal', compact('exam', 'questions', 'canSeeKeys', 'digest', 'canonical', 'userId'));
    }

    /**
     * Cetak lembar soal (naskah untuk santri) — format kop madrasah,
     * tanpa bobot/poin dan tanpa kunci jawaban.
     */
    public function soalPdf(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id, 'Ujian LJK tidak ditemukan pada satuan pendidikan Anda.');

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403, 'Anda tidak berwenang mencetak soal ujian ini.');
        }

        // Kertas otomatis: A4 default → F4 (folio) bila menghemat halaman.
        $rendered = app(\App\Services\Ljk\LjkSoalPdfRenderer::class)->render($exam);

        $filename = 'Soal-'.$exam->code.'.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($rendered['bytes'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'X-LJK-Paper' => $rendered['paper'],
            'X-LJK-Pages' => (string) $rendered['pages'],
        ]);
    }

    /**
     * Preview tata letak (HTML) sebelum uji cetak guru.
     */
    public function preview(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403);
        }

        $participant = LjkParticipant::query()
            ->where('ljk_exam_id', $exam->id)
            ->orderBy('nomor_peserta')
            ->with(['student', 'sheet.template'])
            ->firstOrFail();

        return response($this->sheets->renderSheetHtml($exam, $participant, 1));
    }

    public function cancel(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canManage($request->user(), $exam)) {
            abort(403);
        }

        $exam->forceFill(['status' => LjkExam::STATUS_DIBATALKAN])->save();

        activity()->performedOn($exam)->causedBy($request->user())->log('ljk.exam.cancelled');

        return back()->with('success', 'Ujian LJK dibatalkan (riwayat tetap tersimpan).');
    }

    /**
     * Daftar peserta yang dapat dipilih (untuk store: peserta[]).
     */
    public function candidates(Request $request, string $userId, string $ljkUuid)
    {
        $exam = LjkExam::findOrFail($ljkUuid);
        $this->ensureSchoolScope($request, $exam->school_id);

        if (! $this->access->canView($request->user(), $exam)) {
            abort(403);
        }

        $rows = StudentClassHistory::query()
            ->with('student:id,name,nis')
            ->where('study_group_id', $exam->study_group_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('is_active', true)
            ->orderByRaw('COALESCE(attendance_number, 9999)')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->student_id,
                'name' => $row->student?->name,
                'nis' => $row->student?->nis,
            ]);

        return response()->json(['success' => true, 'data' => ['candidates' => $rows]]);
    }

    private function bahasaForPaket(PaketSoal $paket): string
    {
        $paket->loadMissing('items.soal:id,bahasa,pertanyaan');

        $modes = $paket->items
            ->map(fn ($item) => $item->soal?->bahasaEfektif() ?? 'latin');

        if ($modes->isEmpty()) {
            return 'gabungan';
        }

        if ($modes->every(fn ($mode) => $mode === 'arab')) {
            return 'arab';
        }

        return $modes->contains('arab') ? 'gabungan' : 'latin';
    }

    private function jenisLabel(string $jenis): string
    {
        return match ($jenis) {
            'sas' => 'SAS',
            'ulangan_harian' => 'Ulangan Harian',
            'try_out' => 'Try Out',
            'latihan' => 'Latihan',
            default => 'STS',
        };
    }
}
