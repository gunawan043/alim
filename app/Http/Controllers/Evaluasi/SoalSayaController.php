<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Soal;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\Documents\DocumentVisualService;
use App\Services\Evaluasi\DocxQuestionParser;
use App\Services\Evaluasi\PrintSettingsService;
use App\Services\Evaluasi\QuickSoalService;
use App\Services\Evaluasi\SoalNaskahPdfService;
use App\Services\Evaluasi\TeachingScopeService;
use App\Services\SchoolGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jalur cepat asesmen: Soal Saya, Cari Soal, impor Word, dan cetak langsung.
 */
class SoalSayaController extends Controller
{
    public function __construct(
        private readonly QuickSoalService $quickSoal,
        private readonly DocxQuestionParser $docxParser,
        private readonly TeachingScopeService $teachingScope,
        private readonly PrintSettingsService $printSettings,
    ) {}

    public function index(Request $request, string $userId)
    {
        $school = $this->school($request);
        $query = Soal::with(['bankSoal.subject:id,name,name_ar', 'bankSoal.gradeLevel:id,name', 'options', 'creator:id,name'])
            ->where('dibuat_oleh', $userId)
            ->when($request->filled('q'), fn ($q) => $q->where('pertanyaan', 'like', '%'.$request->q.'%'))
            ->when($request->filled('jenis_asesmen'), fn ($q) => $q->where('jenis_asesmen', $request->jenis_asesmen))
            ->when($request->filled('tipe_soal'), fn ($q) => $q->where('tipe_soal', $request->tipe_soal))
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('subject_id', $request->subject_id)))
            ->orderByDesc('created_at');

        $questions = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Soal::where('dibuat_oleh', $userId)->count(),
            'draft' => Soal::where('dibuat_oleh', $userId)->where('workflow_status', Soal::WORKFLOW_DRAFT)->count(),
            'review' => Soal::where('dibuat_oleh', $userId)->whereIn('workflow_status', [Soal::WORKFLOW_REVIEW, Soal::WORKFLOW_REVISI])->count(),
            'approved' => Soal::where('dibuat_oleh', $userId)->where('workflow_status', Soal::WORKFLOW_APPROVED)->count(),
            'formal' => Soal::where('dibuat_oleh', $userId)->whereIn('jenis_asesmen', [Soal::JENIS_ASESMEN_STS, Soal::JENIS_ASESMEN_SAS])->count(),
        ];

        $subjects = $this->subjects($school);
        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.index', compact('userId', 'school', 'questions', 'stats', 'subjects', 'scope'));
    }

    public function create(Request $request, string $userId)
    {
        $school = $this->school($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAyId = $request->input('academic_year_id', $activeAy?->id);
        $semester = $request->input('semester', $academicYears->firstWhere('id', $selectedAyId)?->semester ?? 'ganjil');

        $subjects = $this->subjects($school);
        $grades = $this->grades($school);

        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);
        $tps = TujuanPembelajaran::query()
            ->where('school_id', $school->id)
            ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId))
            ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
            ->where('semester', in_array($semester, ['ganjil', 'genap'], true) ? $semester : 'ganjil')
            ->orderBy('urutan')
            ->limit(500)
            ->get(['id', 'kode_tp', 'deskripsi']);

        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.create', compact(
            'userId', 'school', 'academicYears', 'selectedAyId', 'semester',
            'subjects', 'grades', 'tps', 'selectedSubjectId', 'scope'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $school = $this->school($request);

        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_asesmen' => 'required|in:harian,formatif,sts,sas',
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'pertanyaan' => 'required|string|max:10000',
            'pembahasan' => 'nullable|string|max:10000',
            'materi' => 'nullable|string|max:150',
            'tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'tingkat_kesulitan_estimasi' => 'required|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'nullable|integer|min:1|max:120',
            'tags' => 'nullable|string|max:255',
            'correct_label' => 'nullable|string|max:2',
            'options' => 'nullable|array',
            'options.*.label' => 'nullable|string|max:2',
            'options.*.teks_opsi' => 'nullable|string|max:1000',
        ]);

        $this->assertWithinDecree($school, $data['subject_id'], $data['grade_level_id'] ?? null);

        $subject = Subject::where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->findOrFail($data['subject_id']);
        $grade = $request->filled('grade_level_id') ? GradeLevel::find($request->grade_level_id) : null;
        $ay = AcademicYear::findOrFail($data['academic_year_id']);

        $options = collect($request->input('options', []))
            ->filter(fn ($option) => trim((string) ($option['teks_opsi'] ?? '')) !== '')
            ->values()
            ->all();

        if ($data['tipe_soal'] === 'pg' && count($options) < 2) {
            return back()->withInput()->with('error', 'Soal pilihan ganda minimal memiliki 2 opsi.');
        }

        if ($data['tipe_soal'] === 'pg' && $request->filled('correct_label')) {
            $labels = array_map(fn ($option) => strtoupper((string) ($option['label'] ?? '')), $options);
            if (! in_array(strtoupper($request->correct_label), $labels, true)) {
                return back()->withInput()->with('error', 'Kunci jawaban tidak ada di opsi.');
            }
        }

        $result = $this->quickSoal->create(
            $request->user(),
            $school,
            $subject,
            $grade,
            $ay,
            $data['semester'],
            $data,
            $options,
            $request->input('correct_label')
        );

        $message = 'Soal berhasil disimpan di Soal Saya.';
        if (in_array($data['jenis_asesmen'], [Soal::JENIS_ASESMEN_STS, Soal::JENIS_ASESMEN_SAS], true)) {
            $message .= $result['soal']->isValidated()
                ? ' Soal sudah tervalidasi.'
                : ' Soal menunggu validasi guru serumpun.';
        }

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', $message)
            ->with('warning', $result['warning']);
    }

    public function search(Request $request, string $userId)
    {
        // Menu lama "Cari Soal" kini diarahkan ke mesin pencari terpadu
        // (Soal, RPM, Bank Soal, Paket Soal, AI, dan Internet).
        return redirect()->route('user.pencarian.index', [
            'userId' => $userId,
            'q' => $request->input('q'),
            'source' => 'db',
            'type' => 'all',
        ]);
    }

    public function clone(Request $request, string $userId, string $id)
    {
        $school = $this->school($request);
        $source = Soal::with(['bankSoal.subject', 'bankSoal.gradeLevel', 'bankSoal.academicYear', 'options'])->findOrFail($id);

        $accessible = BankSoal::accessibleBy($userId, $school->id)->whereKey($source->bank_soal_id)->exists();
        if (! $accessible) {
            abort(403, 'Soal ini tidak dapat diambil.');
        }

        $result = $this->quickSoal->clone($request->user(), $school, $source);

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', 'Soal berhasil disalin ke Soal Saya.')
            ->with('warning', $result['warning']);
    }

    /**
     * Data satu soal untuk modal "Edit soal" pada halaman cetak naskah.
     *
     * Dikembalikan sebagai JSON agar modal bisa diisi tanpa memuat ulang
     * halaman — pratinjau PDF dan centang "Soal yang ikut dicetak" tetap
     * utuh di tempatnya.
     */
    public function editJson(Request $request, string $userId, string $id)
    {
        $soal = $this->editableSoal($request, $userId, $id);

        return response()->json([
            'id' => $soal->id,
            'tipe_soal' => $soal->tipe_soal,
            'bahasa' => $soal->bahasa,
            'pertanyaan' => $soal->pertanyaan,
            'pembahasan' => $soal->pembahasan,
            'materi' => $soal->materi,
            'tingkat_kesulitan_estimasi' => $soal->tingkat_kesulitan_estimasi,
            'waktu_estimasi_menit' => $soal->waktu_estimasi_menit,
            // Kunci jawaban tidak punya kolom sendiri di tabel soal: ia
            // diturunkan dari opsi yang bertanda is_correct.
            'correct_label' => $soal->options()->where('is_correct', true)->value('label'),
            'options' => $soal->options()->orderBy('urutan')->get()->map(fn ($o) => [
                'label' => $o->label,
                'teks_opsi' => $o->teks_opsi,
                'is_correct' => (bool) $o->is_correct,
            ])->all(),
            'update_url' => route('user.soal-saya.update', ['userId' => $userId, 'id' => $soal->id]),
        ]);
    }

    /**
     * Simpan perubahan dari modal "Edit soal" pada halaman cetak naskah.
     *
     * Sengaja hanya menyentuh isi soal (pertanyaan, opsi, kunci, pembahasan)
     * — bukan pengaturan cetak. Setelah disimpan, kembalikan guru ke halaman
     * cetak dengan pratinjau yang dirender ulang dari data terbaru.
     */
    public function updateSoal(Request $request, string $userId, string $id)
    {
        $soal = $this->editableSoal($request, $userId, $id);

        $data = $request->validate([
            'pertanyaan' => 'required|string|max:10000',
            'pembahasan' => 'nullable|string|max:10000',
            'materi' => 'nullable|string|max:150',
            'tingkat_kesulitan_estimasi' => 'required|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'nullable|integer|min:1|max:120',
            'correct_label' => 'nullable|string|max:2',
            'options' => 'nullable|array',
            'options.*.label' => 'nullable|string|max:2',
            'options.*.teks_opsi' => 'nullable|string|max:1000',
        ]);

        $options = collect($request->input('options', []))
            ->filter(fn ($option) => trim((string) ($option['teks_opsi'] ?? '')) !== '')
            ->values();

        $correct = strtoupper(trim((string) ($data['correct_label'] ?? '')));

        if ($soal->tipe_soal === 'pg' && $options->count() < 2) {
            return back()->withInput()->with('error', 'Soal pilihan ganda minimal memiliki 2 opsi.');
        }

        if ($correct !== '' && ! $options->contains(fn ($o) => strtoupper(trim((string) ($o['label'] ?? ''))) === $correct)) {
            return back()->withInput()->with('error', 'Kunci jawaban tidak ada di opsi.');
        }

        DB::transaction(function () use ($soal, $data, $options, $correct) {
            $soal->fill([
                'pertanyaan' => $data['pertanyaan'],
                'pembahasan' => $data['pembahasan'] ?? null,
                'materi' => $data['materi'] ?? null,
                'tingkat_kesulitan_estimasi' => $data['tingkat_kesulitan_estimasi'],
                'waktu_estimasi_menit' => $data['waktu_estimasi_menit'] ?? $soal->waktu_estimasi_menit,
            ]);
            $soal->save();

            // Opsi diganti utuh: id opsi lama tidak dipakai lagi oleh LJK
            // (jawaban santri disimpan per label, bukan per id opsi).
            if (in_array($soal->tipe_soal, ['pg', 'bs', 'jodoh'], true)) {
                $soal->options()->delete();
                $options->each(function ($opt, $i) use ($soal, $correct) {
                    $label = strtoupper(trim((string) ($opt['label'] ?? ''))) ?: chr(65 + $i);

                    \App\Models\SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => $label,
                        'teks_opsi' => (string) $opt['teks_opsi'],
                        'is_correct' => $correct !== '' && $label === $correct,
                        'urutan' => $i + 1,
                    ]);
                });
            }
        });

        return redirect()
            ->route('user.soal-saya.cetak', ['userId' => $userId])
            ->with('success', 'Soal berhasil diperbarui.');
    }

    /**
     * Soal yang boleh diedit dari halaman cetak: harus milik halaman guru
     * ini dan pembuatnya sendiri (atau tim kurikulum). Tanpa cek ini, modal
     * bisa dipakai untuk mengubah soal guru lain lewat id di URL.
     */
    private function editableSoal(Request $request, string $userId, string $id): Soal
    {
        $soal = Soal::with('bankSoal')->findOrFail($id);

        abort_if($soal->dibuat_oleh !== $userId, 403, 'Soal ini bukan milik halaman Anda.');

        $bankIds = BankSoal::accessibleBy($request->user()->id, $soal->bankSoal?->school_id)->pluck('id')->all();
        abort_unless(in_array($soal->bank_soal_id, $bankIds, true), 403, 'Anda tidak memiliki akses ke soal ini.');

        return $soal;
    }

    /**
     * Hapus satu soal dari daftar Soal Saya. Hanya pembuat soal.
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $soal = Soal::findOrFail($id);

        abort_unless(
            $soal->dibuat_oleh === $request->user()->id,
            403,
            'Hanya pembuat soal yang dapat menghapusnya.'
        );

        $soal->delete();

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Hapus beberapa soal sekaligus dari daftar Soal Saya.
     * Hanya soal milik pengguna sendiri yang diproses; sisanya diabaikan.
     */
    public function destroyBulk(Request $request, string $userId)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $soal = Soal::whereIn('id', $validated['ids'])
            ->where('dibuat_oleh', $request->user()->id)
            ->get();

        if ($soal->isEmpty()) {
            return redirect()
                ->route('user.soal-saya.index', ['userId' => $userId])
                ->with('warning', 'Tidak ada soal milik Anda yang dipilih.');
        }

        DB::transaction(fn () => $soal->each->delete());

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', $soal->count().' soal berhasil dihapus.');
    }

    public function importForm(Request $request, string $userId)
    {
        $school = $this->school($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAyId = $request->input('academic_year_id', $activeAy?->id);
        $semester = $request->input('semester', $academicYears->firstWhere('id', $selectedAyId)?->semester ?? 'ganjil');
        $subjects = $this->subjects($school);
        $grades = $this->grades($school);
        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.import', compact(
            'userId', 'school', 'academicYears', 'selectedAyId', 'semester', 'subjects', 'grades', 'scope'
        ));
    }

    public function importStore(Request $request, string $userId)
    {
        $school = $this->school($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_asesmen' => 'required|in:harian,formatif,sts,sas',
            // Nama paket baru (opsional; kosong = dibuat otomatis).
            'paket_baru' => 'nullable|string|max:150',
            // Jenis soal & bahasa: pilihan guru, menimpa tebakan parser.
            'jenis_soal' => 'required|in:campuran,pg,uraian,isian',
            'bahasa' => 'required|in:otomatis,latin,arab,gabungan',
        ], [
            'file.mimes' => 'File harus berformat .docx (Word modern). File .doc lama belum didukung.',
        ]);

        $this->assertWithinDecree($school, $data['subject_id'], $data['grade_level_id'] ?? null);

        $subject = Subject::where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->findOrFail($data['subject_id']);
        $grade = $request->filled('grade_level_id') ? GradeLevel::find($request->grade_level_id) : null;
        $ay = AcademicYear::findOrFail($data['academic_year_id']);

        // Baca berkas lebih dulu: bila Word tidak bisa dibaca, jangan sampai
        // paket kosong sudah terlanjur dibuat.
        try {
            $parsed = $this->docxParser->parse($request->file('file')->getRealPath(), [
                'jenis_soal' => $data['jenis_soal'],
                'bahasa' => $data['bahasa'],
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal membaca Word: '.$e->getMessage());
        }

        // Setiap unggahan Word selalu menjadi paket soal baru.
        $targetBank = $this->resolveImportBank($request, $userId, $school, $subject, $ay, $data);

        $created = 0;
        $warnings = $parsed['warnings'] ?? [];

        foreach ($parsed['questions'] as $index => $question) {
            try {
                $result = $this->quickSoal->create(
                    $request->user(),
                    $school,
                    $subject,
                    $grade,
                    $ay,
                    $data['semester'],
                    [
                        'pertanyaan' => $question['pertanyaan'],
                        'pembahasan' => $question['pembahasan'],
                        'tipe_soal' => $question['tipe_soal'],
                        'jenis_asesmen' => $data['jenis_asesmen'],
                        'tingkat_kesulitan_estimasi' => 'sedang',
                        'waktu_estimasi_menit' => 2,
                        'bobot_default' => 1,
                        'bahasa' => $question['bahasa'] ?? ($data['bahasa'] === 'otomatis' ? null : $data['bahasa']),
                        // Wacana bersama: dibuat sekali lalu dipakai ulang.
                        'wacana_teks' => $question['wacana'] ?? null,
                    ],
                    $question['options'] ?? [],
                    $question['correct_label'] ?? null,
                    $targetBank
                );

                $created++;
                if ($result['warning']) {
                    $warnings[] = 'Soal '.($question['number'] ?? $index + 1).': '.$result['warning'];
                }
            } catch (\Throwable $e) {
                $warnings[] = 'Soal '.($question['number'] ?? $index + 1).' gagal disimpan: '.$e->getMessage();
            }
        }

        if ($created === 0) {
            // Paket tanpa soal tidak berguna; buang agar daftar Bank Soal bersih.
            $targetBank->forceDelete();

            return back()->withInput()->with('error', 'Tidak ada soal yang berhasil diimpor.')->with('import_warnings', $warnings);
        }

        $pesan = "{$created} soal berhasil diimpor ke paket \"{$targetBank->nama}\".";

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $targetBank->id])
            ->with('success', $pesan)
            ->with('import_warnings', $warnings);
    }

    /**
     * Paket tujuan impor. Setiap berkas Word yang diunggah SELALU menjadi
     * satu paket soal baru — guru tidak perlu menamai dulu, karena nama
     * otomatis dibuat dari mapel, jenis asesmen, dan tanggal.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveImportBank(
        Request $request,
        string $userId,
        $school,
        Subject $subject,
        AcademicYear $ay,
        array $data
    ): BankSoal {
        $nama = trim((string) ($data['paket_baru'] ?? ''));

        if ($nama === '') {
            $nama = $this->namaPaketOtomatis($userId, $subject, $data);
        }

        return BankSoal::create([
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $ay->id,
            'semester' => $data['semester'] ?? 'ganjil',
            'nama' => $nama,
            'deskripsi' => 'Dibuat otomatis dari impor Word.',
            'jenis_soal' => $this->jenisPaketDariImpor($data['jenis_soal']),
            'bahasa' => $data['bahasa'] === 'otomatis' ? 'latin' : $data['bahasa'],
            'tingkat_kesulitan_target' => 'campuran',
            'is_public' => false,
            'shared_scope' => 'internal_school',
            'owner_user_id' => $userId,
            'created_by' => $userId,
            'total_soal' => 0,
        ]);
    }

    /**
     * Terjemahkan pilihan jenis soal pada form impor (campuran/pg/uraian/isian)
     * ke nilai enum kolom bank_soal.jenis_soal. Kolom itu dijaga CHECK
     * constraint di SQLite, sehingga 'pg' atau 'isian' mentah akan menggagalkan
     * insert paket.
     */
    private function jenisPaketDariImpor(string $jenis): string
    {
        return match ($jenis) {
            'pg' => 'pilihan_ganda',
            'isian' => 'isian_singkat',
            default => in_array($jenis, ['uraian', 'campuran'], true) ? $jenis : 'campuran',
        };
    }

    /**
     * Nama paket otomatis, mis. "Impor Word Matematika — Harian 23-11-2026".
     * Diberi akhiran (2), (3), ... bila nama itu sudah dipakai.
     *
     * @param  array<string, mixed>  $data
     */
    private function namaPaketOtomatis(string $userId, Subject $subject, array $data): string
    {
        $asesmen = Soal::JENIS_ASESMEN_OPTIONS[$data['jenis_asesmen'] ?? ''] ?? 'Impor';
        $dasar = 'Impor Word '.$subject->name.' — '.$asesmen.' '.now()->format('d-m-Y');

        $nama = $dasar;
        $urut = 2;

        while (BankSoal::where('owner_user_id', $userId)->where('nama', $nama)->exists()) {
            $nama = $dasar.' ('.$urut.')';
            $urut++;
        }

        return $nama;
    }

    /**
     * Halaman cetak naskah: pilih soal, atur tata letak, lihat pratinjau,
     * lalu cetak. Berdiri sendiri tanpa paket soal — dipakai untuk soal
     * harian milik guru.
     */
    public function cetakPage(Request $request, string $userId)
    {
        $school = $this->school($request);
        $settings = $this->printSettings->preview($request);

        $bankIds = BankSoal::accessibleBy($userId, $school->id)->pluck('id');

        $query = Soal::with(['bankSoal.subject:id,name,name_ar', 'bankSoal.gradeLevel:id,name', 'options'])
            ->whereIn('bank_soal_id', $bankIds)
            ->when($request->filled('bank_id'), fn ($q) => $q->where('bank_soal_id', $request->bank_id))
            ->when($request->filled('jenis_asesmen'), fn ($q) => $q->where('jenis_asesmen', $request->jenis_asesmen))
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('subject_id', $request->subject_id)))
            ->orderByDesc('created_at');

        $questions = $query->get();

        $banks = BankSoal::query()
            ->accessibleBy($userId, $school->id)
            ->koleksi()
            ->withCount('soal')
            ->orderBy('nama')
            ->get(['id', 'nama', 'subject_id', 'school_id', 'owner_user_id', 'created_by']);

        $subjects = $this->subjects($school);

        // Soal yang sudah dipilih sebelumnya (dari daftar Soal Saya / bank soal).
        $selectedIds = collect($request->input('ids', []))->filter()->map(fn ($id) => (string) $id)->all();

        $blocked = $this->printSettings->blockedQuestions($questions);

        $kop = app(DocumentVisualService::class)->imageDataUri($school->kop_path);

        return view('evalusi.soal-saya.cetak', compact(
            'userId', 'school', 'questions', 'banks', 'subjects',
            'settings', 'selectedIds', 'blocked', 'kop'
        ));
    }

    public function print(Request $request, string $userId)
    {
        $school = $this->school($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
            'with_key' => 'nullable|boolean',
            'kop' => 'nullable|boolean',
            'judul' => 'nullable|string|max:150',
        ]);

        $bankIds = BankSoal::accessibleBy($userId, $school->id)->pluck('id');
        $questions = Soal::with(['bankSoal.subject:id,name,name_ar', 'bankSoal.gradeLevel:id,name', 'options'])
            ->whereIn('id', $data['ids'])
            ->whereIn('bank_soal_id', $bankIds)
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $data['ids'], true))
            ->values();

        if ($questions->count() !== count(array_unique($data['ids']))) {
            return back()->with('error', 'Sebagian soal tidak ditemukan atau tidak dapat diakses.');
        }

        $blocked = $this->printSettings->blockedQuestions($questions);

        if ($blocked !== []) {
            return back()->with(
                'error',
                'Soal STS/SAS berikut belum tervalidasi mayoritas guru serumpun: '.implode('; ', $blocked)
                .'. Untuk soal harian tidak diperlukan validasi — ubah jenis asesmennya bila memang soal harian.'
            );
        }

        // Pengaturan tata letak dari halaman cetak (bila dikirim).
        $settings = $this->printSettings->resolve($request);

        $withKey = $request->has('with_key')
            ? $request->boolean('with_key')
            : (bool) $settings['show_key'];
        $useKop = $request->has('kop')
            ? $request->boolean('kop')
            : (bool) $settings['show_kop'];

        // Naskah memakai format resmi yang sama dengan STS/SAS (ljk.soal-pdf):
        // kop bergaris, tabel identitas, penanaman karakter, opsi sejajar.
        $settings['show_kop'] = $useKop;

        $pdf = app(SoalNaskahPdfService::class)->pdf(
            $questions,
            $school,
            $request->user(),
            $settings,
            $this->resolvedTitle($data, $settings),
            $request->filled('kelas_label') ? (string) $request->input('kelas_label') : null
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="soal-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    /**
     * Pratinjau PDF di halaman cetak. Memakai dokumen yang sama persis dengan
     * hasil cetak, sehingga yang terlihat di layar adalah naskah sebenarnya.
     */
    public function previewPdf(Request $request, string $userId)
    {
        $school = $this->school($request);
        $settings = $this->printSettings->preview($request);

        $bankIds = BankSoal::accessibleBy($userId, $school->id)->pluck('id');

        // Pratinjau mengikuti soal yang dicentang bila dikirim, sehingga
        // naskah di layar sama dengan naskah yang akan dicetak.
        $selected = collect($request->input('ids', []))->filter()->map(fn ($id) => (string) $id)->values()->all();

        $questions = Soal::with(['bankSoal.subject:id,name,name_ar', 'bankSoal.gradeLevel:id,name', 'options', 'wacana'])
            ->whereIn('bank_soal_id', $bankIds)
            ->when($selected !== [], fn ($q) => $q->whereIn('id', $selected))
            ->when($request->filled('bank_id'), fn ($q) => $q->where('bank_soal_id', $request->bank_id))
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('subject_id', $request->subject_id)))
            ->orderByDesc('created_at')
            ->get()
            ->reject(fn (Soal $soal) => $soal->requiresValidation() && ! $soal->isValidated())
            ->values();

        if ($selected !== []) {
            $questions = $questions->sortBy(fn ($q) => array_search($q->id, $selected, true))->values();
        }

        if ($questions->isEmpty()) {
            return response('Belum ada soal yang dapat dicetak.', 200, ['Content-Type' => 'text/plain']);
        }

        $pdf = app(SoalNaskahPdfService::class)->pdf(
            $questions,
            $school,
            $request->user(),
            $settings,
            $settings['judul'] ?: null
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pratinjau-soal.pdf"',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /**
     * Judul naskah: eksplisit dari form, pengaturan tersimpan, atau bawaan.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $settings
     */
    private function resolvedTitle(array $data, array $settings): string
    {
        $explicit = trim((string) ($data['judul'] ?? ''));

        if ($explicit !== '') {
            return $explicit;
        }

        $stored = trim((string) ($settings['judul'] ?? ''));

        return $stored !== '' ? $stored : 'NASKAH SOAL';
    }

    private function school(Request $request): School
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        // Admin dengan akses global bisa membuka workspace guru tanpa memilih
        // sekolah di session. Turunkan konteks dari bank soal / guru yang
        // dituju, jangan langsung 422 — halaman ini tetap dibatasi oleh
        // accessibleBy() saat mengambil soal.
        if (! $school && $request->filled('bank_id')) {
            $school = BankSoal::query()->find($request->input('bank_id'))?->school;
        }

        if (! $school) {
            $target = User::query()->find($request->route('userId'));
            $school = $target ? SchoolGroupService::getUserSchool($target) : null;
        }

        if (! $school && $request->filled('ids')) {
            $firstId = collect($request->input('ids'))->filter()->first();
            $school = $firstId
                ? Soal::query()->find($firstId)?->bankSoal?->school
                : null;
        }

        abort_if(! $school, 422, 'Konteks satuan pendidikan tidak ditemukan.');

        return $school;
    }

    private function subjects(School $school): Collection
    {
        $request = request();

        return $this->teachingScope->subjects(
            $request->user(),
            $school->id,
            null
        );
    }

    /**
     * Kelas yang boleh dipilih: hanya kelas yang diajar menurut SK aktif.
     * Bila mapel dipilih, dipersempit lagi ke kelas untuk mapel tersebut.
     */
    private function grades(School $school, ?string $subjectId = null): Collection
    {
        $request = request();

        if ($subjectId) {
            return $this->teachingScope->gradeLevelsForSubject($request->user(), $school->id, null, $subjectId);
        }

        $gradeIds = $this->teachingScope->assignments($request->user(), $school->id, null)
            ->map(fn ($a) => $a->studyGroup?->grade_level_id)
            ->filter()
            ->unique();

        return GradeLevel::query()->whereIn('id', $gradeIds)->orderBy('level')->get();
    }

    /**
     * Tolak mapel/kelas di luar SK aktif.
     *
     * @throws ValidationException
     */
    private function assertWithinDecree(School $school, ?string $subjectId, ?string $gradeLevelId): void
    {
        $user = request()->user();

        if (! $this->teachingScope->allowsSubject($user, $school->id, null, $subjectId)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Mata pelajaran ini tidak ada pada SK Pembagian Tugas aktif Anda. '
                    .'Minta Waka menambahkannya ke SK, atau pilih mapel yang memang Anda ajar.',
            ]);
        }

        if (! $this->teachingScope->allowsGradeLevel($user, $school->id, null, $subjectId, $gradeLevelId)) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Kelas ini tidak Anda ajar untuk mata pelajaran tersebut menurut SK aktif.',
            ]);
        }
    }

    private function scopeSummary(School $school): array
    {
        return $this->teachingScope->summary(request()->user(), $school->id, null);
    }
}
