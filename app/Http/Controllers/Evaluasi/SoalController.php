<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\WacanaSoal;
use App\Models\SoalOption;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\Evaluasi\ContentHashEngine;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Services\Evaluasi\SoalSimilarityService;
use App\Services\SubjectGroupResolver;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SoalController extends Controller
{
    use AuthorizesAcademicScope;

    /**
     * List soal within a BankSoal (inline endpoint for tab on show page).
     */
    public function index(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::withCount('soal')->with(['soal' => function ($q) use ($request) {
            if ($status = $request->get('status')) {
                $q->where('status', $status);
            }
            $q->orderBy('created_at');
        }])->findOrFail($bankId);

        return view('evalusi.bank-soal.partials.soal-list', [
            'bank' => $bank,
            'userId' => $userId,
        ]);
    }

    /**
     * Show create form for a new Soal within a BankSoal.
     */
    /**
     * Resolusi wacana untuk soal: buat baru (bila teks diisi) atau pakai yang ada.
     * Mengembalikan null bila soal tidak memakai wacana.
     */
    private function resolveWacana(Request $request, BankSoal $bank, string $userId): ?string
    {
        $teks = trim((string) $request->input('wacana_teks', ''));

        if ($teks !== '') {
            $wacana = WacanaSoal::create([
                'bank_soal_id' => $bank->id,
                'school_id' => $bank->school_id,
                'judul' => ($judul = trim((string) $request->input('wacana_judul', ''))) !== '' ? $judul : null,
                'teks' => $teks,
                'sumber' => ($sumber = trim((string) $request->input('wacana_sumber', ''))) !== '' ? $sumber : null,
                'created_by' => $userId,
            ]);

            return $wacana->id;
        }

        $wacanaId = (string) $request->input('wacana_id', '');

        if ($wacanaId !== '') {
            $exists = WacanaSoal::query()
                ->where('id', $wacanaId)
                ->where(function ($q) use ($bank) {
                    $q->where('bank_soal_id', $bank->id)->orWhereNull('bank_soal_id');
                })
                ->exists();

            return $exists ? $wacanaId : null;
        }

        return null;
    }

    public function create(string $userId, string $bankId)
    {
        $bank = BankSoal::with('subject:id,name')->findOrFail($bankId);

        $this->authorizeManage(request(), $bank);

        $tps = TujuanPembelajaran::where('subject_id', $bank->subject_id)->get();

        $tipeSoal = [
            'pg' => 'Pilihan Ganda',
            'bs' => 'Benar / Salah',
            'jodoh' => 'Menjodohkan',
            'isian' => 'Isian Singkat',
            'uraian' => 'Uraian',
        ];

        $wacanas = WacanaSoal::query()
            ->where('bank_soal_id', $bank->id)
            ->orderByDesc('created_at')
            ->get(['id', 'judul', 'teks']);

        $wacanas = WacanaSoal::query()
            ->where('bank_soal_id', $bank->id)
            ->orderByDesc('created_at')
            ->get(['id', 'judul', 'teks']);

        return view('evalusi.soal.create', compact('bank', 'tps', 'tipeSoal', 'wacanas', 'userId'));
    }

    /**
     * Halaman edit soal: form builder yang sama dengan halaman input,
     * tetapi seluruh soal bank dimuat sekaligus.
     */
    public function manage(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::with([
            'subject:id,name',
            'soal' => fn ($q) => $q->with('options')->orderBy('created_at'),
        ])->findOrFail($bankId);

        $this->authorizeManage($request, $bank);

        $tps = TujuanPembelajaran::where('subject_id', $bank->subject_id)->get();
        $wacanas = WacanaSoal::query()
            ->where('bank_soal_id', $bank->id)
            ->orderByDesc('created_at')
            ->get(['id', 'judul', 'teks', 'rentang_nomor', 'posisi_setelah_soal']);

        $tipeSoal = [
            'pg' => 'Pilihan Ganda',
            'bs' => 'Benar / Salah',
            'jodoh' => 'Menjodohkan',
            'isian' => 'Isian Singkat',
            'uraian' => 'Uraian',
        ];

        $builderQuestions = $bank->soal->map(fn (Soal $soal) => [
            'id' => $soal->id,
            'tipe_soal' => $soal->tipe_soal,
            'pertanyaan' => $soal->pertanyaan,
            'materi' => $soal->materi,
            'pembahasan' => $soal->pembahasan,
            'tp_id' => $soal->tp_id,
            'wacana_id' => $soal->wacana_id,
            'level_kognitif' => $soal->level_kognitif,
            'level_kognitif_label' => $soal->level_kognitif_label,
            'level_kognitif_rumpun' => $soal->level_kognitif_rumpun,
            'tags' => is_array($soal->tags) ? implode(', ', $soal->tags) : ($soal->tags ?? ''),
            'gambar_keterangan' => $soal->gambar_keterangan,
            'options' => $soal->options->map(fn (SoalOption $option) => [
                'id' => $option->id,
                'label' => $option->label,
                'teks_opsi' => $option->teks_opsi,
                'is_correct' => (bool) $option->is_correct,
            ])->values()->all(),
        ])->values()->all();

        $isEditMode = true;
        $formAction = route('user.soal.update-all', ['userId' => $userId, 'bankId' => $bank->id]);

        return view('evalusi.soal.create', compact(
            'bank', 'userId', 'tps', 'wacanas', 'tipeSoal',
            'builderQuestions', 'isEditMode', 'formAction'
        ));
    }

    /**
     * Simpan perubahan banyak soal sekaligus dari halaman edit.
     */
    public function updateAll(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $validated = $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|uuid',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe_soal' => 'nullable|in:pg,bs,jodoh,isian,uraian',
            'questions.*.tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'questions.*.level_kognitif' => 'nullable|in:auto,C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta,C6_mencita',
            'questions.*.materi' => 'nullable|string|max:150',
            'questions.*.wacana_id' => 'nullable|uuid|exists:wacana_soal,id',
            'questions.*.pembahasan' => 'nullable|string|max:5000',
            'questions.*.tags' => 'nullable|string|max:255',
            'questions.*.gambar' => 'nullable|image|max:4096',
            'questions.*.gambar_keterangan' => 'nullable|string|max:255',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.teks_opsi' => 'nullable|string|max:1000',
            'questions.*.options.*.is_correct' => 'nullable|boolean',
        ]);

        $typeMap = [
            'pilihan_ganda' => 'pg',
            'multiple_choice_complex' => 'pg',
            'benar_salah' => 'bs',
            'menjodohkan' => 'jodoh',
            'isian_singkat' => 'isian',
            'uraian' => 'uraian',
        ];
        $bankType = $typeMap[$bank->jenis_soal] ?? 'pg';
        $engine = app(ContentHashEngine::class);
        $updated = 0;
        $created = 0;

        DB::transaction(function () use ($validated, $request, $bank, $userId, $bankType, $engine, &$updated, &$created) {
            foreach ($validated['questions'] as $index => $question) {
                $soal = null;

                if (! empty($question['id'])) {
                    $soal = Soal::where('bank_soal_id', $bank->id)->find($question['id']);

                    if (! $soal) {
                        throw ValidationException::withMessages([
                            "questions.$index.id" => 'Soal tidak ditemukan pada bank ini.',
                        ]);
                    }

                    $this->authorizeEdit($request, $bank, $soal);
                }

                $type = $bank->jenis_soal === 'campuran'
                    ? ($question['tipe_soal'] ?? 'pg')
                    : $bankType;

                $options = collect($question['options'] ?? [])
                    ->filter(fn ($option) => trim((string) ($option['teks_opsi'] ?? '')) !== '')
                    ->values();

                $hasCorrect = $options->contains(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN));
                if (in_array($type, ['pg', 'bs', 'jodoh'], true) && $options->count() >= 2 && ! $hasCorrect) {
                    throw ValidationException::withMessages([
                        "questions.$index.options" => 'Pilih satu jawaban benar pada soal ini.',
                    ]);
                }

                $wacanaId = $question['wacana_id'] ?? null;
                if ($wacanaId) {
                    $validWacana = WacanaSoal::whereKey($wacanaId)
                        ->where(fn ($q) => $q->where('bank_soal_id', $bank->id)->orWhereNull('bank_soal_id'))
                        ->exists();
                    $wacanaId = $validWacana ? $wacanaId : null;
                }

                $imagePath = $soal?->gambar_path;
                if ($request->hasFile("questions.$index.gambar")) {
                    $imagePath = $request->file("questions.$index.gambar")->store('soal/'.$bank->id, 'public');
                }

                if (! $soal) {
                    $soal = new Soal;
                    $soal->fill([
                        'bank_soal_id' => $bank->id,
                        'bahasa' => $bank->bahasa ?? 'latin',
                        'bobot_default' => 1,
                        'tingkat_kesulitan_estimasi' => 'sedang',
                        'waktu_estimasi_menit' => 2,
                        'status' => 'draft',
                        'workflow_status' => Soal::WORKFLOW_DRAFT,
                        'dibuat_oleh' => $userId,
                    ]);
                }

                $soal->fill([
                    'tp_id' => $question['tp_id'] ?? null,
                    'wacana_id' => $wacanaId,
                    'materi' => $question['materi'] ?? null,
                    'level_kognitif' => ($question['level_kognitif'] ?? null) !== 'auto' ? ($question['level_kognitif'] ?? null) : null,
                    'tipe_soal' => $type,
                    'pertanyaan' => $question['pertanyaan'],
                    'pembahasan' => $question['pembahasan'] ?? null,
                    'gambar_path' => $imagePath,
                    'gambar_keterangan' => $question['gambar_keterangan'] ?? null,
                    'tags' => ! empty($question['tags']) ? array_map('trim', explode(',', $question['tags'])) : null,
                ]);

                $soal->content_hash = $engine->hashFromSoal($soal->pertanyaan, $options->pluck('teks_opsi')->all());
                $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);
                $soal->save();

                // Ganti opsi agar perubahan opsi lama tidak tertinggal.
                $soal->options()->delete();
                if (in_array($type, ['pg', 'bs', 'jodoh'], true)) {
                    foreach ($options as $optionIndex => $option) {
                        SoalOption::create([
                            'soal_id' => $soal->id,
                            'label' => chr(65 + $optionIndex),
                            'teks_opsi' => $option['teks_opsi'],
                            'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'urutan' => $optionIndex + 1,
                        ]);
                    }
                }

                if (! $soal->wasRecentlyCreated) {
                    if ($soal->reviewAssignments()->exists() || $soal->workflow_status !== Soal::WORKFLOW_DRAFT) {
                        $soal->reviewAssignments()->delete();
                        $soal->syncWorkflowStatus(Soal::WORKFLOW_DRAFT);
                    }

                    $soal->similarities()->delete();
                    $soal->forceFill([
                        'similarity_checked_at' => null,
                        'similarity_summary' => null,
                        'similarity_ack_note' => null,
                        'similarity_ack_by' => null,
                        'similarity_ack_at' => null,
                    ])->save();
                }

                if ($soal->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        return redirect()
            ->route('user.soal.manage', ['userId' => $userId, 'bankId' => $bank->id])
            ->with('success', "Perubahan soal disimpan. {$updated} soal diperbarui, {$created} soal baru ditambahkan.");
    }

    /**
     * Persist a new Soal (with its options).
     */
    public function store(Request $request, string $userId, string $bankId)
    {
        if ($request->has('questions')) {
            return $this->storeQuestions($request, $userId, $bankId);
        }

        $bank = BankSoal::findOrFail($bankId);

        $this->authorizeManage(request(), $bank);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'pertanyaan' => 'required|string',
            'pembahasan' => 'nullable|string|max:5000',
            'materi' => 'nullable|string|max:150',
            'gambar_path' => 'nullable|string|max:255',
            'audio_path' => 'nullable|string|max:255',
            'bobot_default' => 'required|numeric|min:0|max:100',
            'tingkat_kesulitan_estimasi' => 'required|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'required|integer|min:1|max:120',
            'tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'tags' => 'nullable|string',
            'options' => 'required_if:tipe_soal,pg,bs,jodoh|array|min:2',
            'options.*.label' => 'required_with:options|string|max:5',
            'options.*.teks_opsi' => 'required_with:options|string|max:1000',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request, $bank, $userId) {
            $engine = app(ContentHashEngine::class);
            $correctTexts = collect($request->options ?? [])
                ->filter(fn ($opt) => isset($opt['is_correct']) && (string) $opt['is_correct'] === '1')
                ->pluck('teks_opsi')
                ->all();

            $soal = new Soal;
            $soal->fill([
                'bank_soal_id' => $bank->id,
                'tp_id' => $validated['tp_id'] ?? null,
                'wacana_id' => $this->resolveWacana($request, $bank, $userId),
                'materi' => $validated['materi'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
                'bahasa' => $validated['bahasa'] ?? $bank->bahasa ?? 'latin',
                'pertanyaan' => $validated['pertanyaan'],
                'pembahasan' => $validated['pembahasan'] ?? null,
                'gambar_path' => $validated['gambar_path'] ?? null,
                'audio_path' => $validated['audio_path'] ?? null,
                'bobot_default' => $validated['bobot_default'],
                'tingkat_kesulitan_estimasi' => $validated['tingkat_kesulitan_estimasi'],
                'waktu_estimasi_menit' => $validated['waktu_estimasi_menit'],
                'tags' => ($validated['tags'] ?? null) ? array_map('trim', explode(',', $validated['tags'])) : null,
                'status' => 'draft',
                'workflow_status' => Soal::WORKFLOW_DRAFT,
                'dibuat_oleh' => $userId,
            ]);

            // Hash & shingles ternormalisasi (fondasi deteksi kemiripan).
            $soal->content_hash = $engine->hashFromSoal($soal->pertanyaan, $correctTexts);
            $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);

            $soal->save();

            // Persist options if relevant
            if (in_array($validated['tipe_soal'], ['pg', 'bs', 'jodoh']) && $request->filled('options')) {
                foreach ($request->options as $i => $opt) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => $opt['label'],
                        'teks_opsi' => $opt['teks_opsi'],
                        'is_correct' => filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'urutan' => $i + 1,
                    ]);
                }
            }

            return redirect()
                ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
                ->with('success', 'Soal berhasil dibuat (status: draft). Submit untuk review.');
        });
    }

    public function uploadWacanaImage(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $request->validate([
            'upload' => 'required|image|max:4096',
        ]);

        $path = $request->file('upload')->store('wacana/'.$bank->id, 'public');

        return response()->json([
            'uploaded' => 1,
            'fileName' => basename($path),
            'url' => Storage::url($path),
        ]);
    }

    public function updateWacana(Request $request, string $userId, string $bankId, string $wacanaId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $data = $request->validate([
            'judul' => 'nullable|string|max:150',
            'teks' => 'required|string',
            'sumber' => 'nullable|string|max:150',
            'rentang_nomor' => 'nullable|string|max:100',
            'posisi_setelah_soal' => 'nullable|integer|min:0|max:1000',
        ]);

        $wacana = WacanaSoal::where('bank_soal_id', $bank->id)->findOrFail($wacanaId);
        $wacana->update([
            'judul' => $data['judul'] ?? null,
            'teks' => $this->sanitizeWacanaHtml($data['teks']),
            'sumber' => $data['sumber'] ?? null,
            'rentang_nomor' => $data['rentang_nomor'] ?? null,
            'posisi_setelah_soal' => $data['posisi_setelah_soal'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'id' => $wacana->id,
            'judul' => $wacana->judul ?: Str::limit(strip_tags($wacana->teks), 50),
        ]);
    }

    public function destroyWacana(Request $request, string $userId, string $bankId, string $wacanaId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $wacana = WacanaSoal::where('bank_soal_id', $bank->id)->findOrFail($wacanaId);

        DB::transaction(function () use ($wacana) {
            Soal::where('wacana_id', $wacana->id)->update(['wacana_id' => null]);
            $wacana->delete();
        });

        return response()->json(['success' => true]);
    }

    private function sanitizeWacanaHtml(string $html): string
    {
        $clean = strip_tags($html, '<p><br><strong><b><em><i><u><s><ul><ol><li><a><img><h2><h3><h4><blockquote><table><thead><tbody><tr><th><td>');
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
        $clean = preg_replace('/\s(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/i', '', $clean);

        return trim($clean);
    }

    public function storeWacana(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $data = $request->validate([
            'judul' => 'nullable|string|max:150',
            'teks' => 'required|string',
            'sumber' => 'nullable|string|max:150',
            'rentang_nomor' => 'nullable|string|max:100',
            'posisi_setelah_soal' => 'nullable|integer|min:0|max:1000',
        ]);

        $wacana = WacanaSoal::create([
            'bank_soal_id' => $bank->id,
            'school_id' => $bank->school_id,
            'judul' => $data['judul'] ?? null,
            'teks' => $this->sanitizeWacanaHtml($data['teks']),
            'sumber' => $data['sumber'] ?? null,
            'rentang_nomor' => $data['rentang_nomor'] ?? null,
            'posisi_setelah_soal' => $data['posisi_setelah_soal'] ?? 0,
            'created_by' => $userId,
        ]);

        return response()->json([
            'id' => $wacana->id,
            'judul' => ($wacana->judul ?: Str::limit(strip_tags($wacana->teks), 50)).($wacana->rentang_nomor ? ' · No. '.$wacana->rentang_nomor : ''),
            'posisi_setelah_soal' => (int) $wacana->posisi_setelah_soal,
            'teks' => $wacana->teks,
        ]);
    }

    private function storeQuestions(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $this->authorizeManage($request, $bank);

        $validated = $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.pertanyaan' => 'required|string',
            'questions.*.tipe_soal' => 'nullable|in:pg,bs,jodoh,isian,uraian',
            'questions.*.tingkat_kesulitan_estimasi' => 'nullable|in:mudah,sedang,sulit',
            'questions.*.level_kognitif' => 'nullable|in:auto,C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencipta,C6_mencita',
            'questions.*.bobot_default' => 'nullable|numeric|min:0|max:100',
            'questions.*.tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'questions.*.materi' => 'nullable|string|max:150',
            'questions.*.wacana_id' => 'nullable|uuid|exists:wacana_soal,id',
            'questions.*.pembahasan' => 'nullable|string|max:5000',
            'questions.*.tags' => 'nullable|string|max:255',
            'questions.*.gambar' => 'nullable|image|max:4096',
            'questions.*.gambar_keterangan' => 'nullable|string|max:255',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.teks_opsi' => 'nullable|string|max:1000',
            'questions.*.options.*.is_correct' => 'nullable|boolean',
        ]);

        $typeMap = [
            'pilihan_ganda' => 'pg',
            'multiple_choice_complex' => 'pg',
            'benar_salah' => 'bs',
            'menjodohkan' => 'jodoh',
            'isian_singkat' => 'isian',
            'uraian' => 'uraian',
        ];
        $bankType = $typeMap[$bank->jenis_soal] ?? 'pg';
        $engine = app(ContentHashEngine::class);
        $created = 0;

        DB::transaction(function () use ($validated, $request, $bank, $userId, $bankType, $engine, &$created) {
            foreach ($validated['questions'] as $index => $question) {
                $type = $bank->jenis_soal === 'campuran'
                    ? ($question['tipe_soal'] ?? 'pg')
                    : $bankType;
                $options = collect($question['options'] ?? [])
                    ->filter(fn ($option) => trim((string) ($option['teks_opsi'] ?? '')) !== '')
                    ->values();

                $hasCorrect = $options->contains(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN));
                if (in_array($type, ['pg', 'bs', 'jodoh'], true) && $options->count() >= 2 && ! $hasCorrect) {
                    throw ValidationException::withMessages([
                        "questions.$index.options" => 'Pilih satu jawaban benar pada soal ini.',
                    ]);
                }

                $wacanaId = $question['wacana_id'] ?? null;
                if ($wacanaId) {
                    $validWacana = WacanaSoal::whereKey($wacanaId)
                        ->where(fn ($q) => $q->where('bank_soal_id', $bank->id)->orWhereNull('bank_soal_id'))
                        ->exists();
                    $wacanaId = $validWacana ? $wacanaId : null;
                }

                $imagePath = null;
                if ($request->hasFile("questions.$index.gambar")) {
                    $imagePath = $request->file("questions.$index.gambar")->store('soal/'.$bank->id, 'public');
                }

                $soal = new Soal;
                $soal->fill([
                    'bank_soal_id' => $bank->id,
                    'tp_id' => $question['tp_id'] ?? null,
                    'wacana_id' => $wacanaId,
                    'materi' => $question['materi'] ?? null,
                    'level_kognitif' => ($question['level_kognitif'] ?? null) !== 'auto' ? ($question['level_kognitif'] ?? null) : null,
                    'tipe_soal' => $type,
                    'bahasa' => $bank->bahasa ?? 'latin',
                    'pertanyaan' => $question['pertanyaan'],
                    'pembahasan' => $question['pembahasan'] ?? null,
                    'gambar_path' => $imagePath,
                    'gambar_keterangan' => $question['gambar_keterangan'] ?? null,
                    'bobot_default' => $question['bobot_default'] ?? 1,
                    'tingkat_kesulitan_estimasi' => $question['tingkat_kesulitan_estimasi'] ?? 'sedang',
                    'waktu_estimasi_menit' => 2,
                    'tags' => ! empty($question['tags']) ? array_map('trim', explode(',', $question['tags'])) : null,
                    'status' => 'draft',
                    'workflow_status' => Soal::WORKFLOW_DRAFT,
                    'dibuat_oleh' => $userId,
                ]);
                $soal->content_hash = $engine->hashFromSoal($soal->pertanyaan, $options->pluck('teks_opsi')->all());
                $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);
                $soal->save();

                if (in_array($type, ['pg', 'bs', 'jodoh'], true)) {
                    foreach ($options as $optionIndex => $option) {
                        SoalOption::create([
                            'soal_id' => $soal->id,
                            'label' => chr(65 + $optionIndex),
                            'teks_opsi' => $option['teks_opsi'],
                            'is_correct' => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'urutan' => $optionIndex + 1,
                        ]);
                    }
                }

                $created++;
            }
        });

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', $created.' soal berhasil dibuat (status: draft).');
    }

    /**
     * Show edit form for Soal.
     */
    public function edit(string $userId, string $bankId, string $id)
    {
        $soal = Soal::with([
            'options',
            'wacana',
            'reviewAssignments:id,reviewable_type,reviewable_id,reviewer_id,status',
            'similarities:id,soal_id,compared_soal_id,score,level',
            'similarities.comparedSoal:id,bank_soal_id,pertanyaan,dibuat_oleh,materi',
            'similarities.comparedSoal.bankSoal:id,subject_id,academic_year_id,semester,jenis_soal',
            'similarities.comparedSoal.bankSoal.subject:id,name',
            'similarities.comparedSoal.bankSoal.academicYear:id,name',
            'similarities.comparedSoal.creator:id,name',
        ])->findOrFail($id);
        $bank = $soal->bankSoal;

        $this->authorizeEdit(request(), $bank, $soal);

        $tps = TujuanPembelajaran::where('subject_id', $bank->subject_id)->get();

        $tipeSoal = [
            'pg' => 'Pilihan Ganda',
            'bs' => 'Benar / Salah',
            'jodoh' => 'Menjodohkan',
            'isian' => 'Isian Singkat',
            'uraian' => 'Uraian',
        ];

        return view('evalusi.soal.edit', compact('soal', 'bank', 'tps', 'tipeSoal', 'userId'));
    }

    /**
     * Update Soal and its options.
     */
    public function update(Request $request, string $userId, string $bankId, string $id)
    {
        $soal = Soal::findOrFail($id);
        $bank = $soal->bankSoal;

        $this->authorizeEdit(request(), $bank, $soal);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'pertanyaan' => 'required|string',
            'pembahasan' => 'nullable|string|max:5000',
            'materi' => 'nullable|string|max:150',
            'gambar_path' => 'nullable|string|max:255',
            'audio_path' => 'nullable|string|max:255',
            'bobot_default' => 'required|numeric|min:0|max:100',
            'tingkat_kesulitan_estimasi' => 'required|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'required|integer|min:1|max:120',
            'tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'tags' => 'nullable|string',
            'options' => 'required_if:tipe_soal,pg,bs,jodoh|array|min:2',
            'options.*.id' => 'nullable|exists:soal_options,id',
            'options.*.label' => 'required_with:options|string|max:5',
            'options.*.teks_opsi' => 'required_with:options|string|max:1000',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $request, $soal, $userId) {
            $engine = app(ContentHashEngine::class);
            $correctTexts = collect($request->options ?? [])
                ->filter(fn ($opt) => isset($opt['is_correct']) && (string) $opt['is_correct'] === '1')
                ->pluck('teks_opsi')
                ->all();

            $soal->fill([
                'tp_id' => $validated['tp_id'] ?? null,
                'wacana_id' => $request->has('wacana_id') || $request->filled('wacana_teks')
                    ? $this->resolveWacana($request, $bank, $userId)
                    : $soal->wacana_id,
                'materi' => $validated['materi'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
                'bahasa' => $validated['bahasa'] ?? $soal->bahasa ?? $bank->bahasa ?? 'latin',
                'pertanyaan' => $validated['pertanyaan'],
                'pembahasan' => $validated['pembahasan'] ?? null,
                'gambar_path' => $validated['gambar_path'] ?? null,
                'audio_path' => $validated['audio_path'] ?? null,
                'bobot_default' => $validated['bobot_default'],
                'tingkat_kesulitan_estimasi' => $validated['tingkat_kesulitan_estimasi'],
                'waktu_estimasi_menit' => $validated['waktu_estimasi_menit'],
                'tags' => ($validated['tags'] ?? null) ? array_map('trim', explode(',', $validated['tags'])) : null,
            ]);
            $soal->content_hash = $engine->hashFromSoal($soal->pertanyaan, $correctTexts);
            $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);
            $soal->save();

            // Perubahan soal setelah approval/review → versi baru wajib divalidasi ulang.
            // Hasil similarity & pengecualian lama juga kedaluwarsa (teks soal berubah).
            if ($soal->reviewAssignments()->exists() || $soal->workflow_status !== Soal::WORKFLOW_DRAFT) {
                $soal->reviewAssignments()->delete();
                $soal->syncWorkflowStatus(Soal::WORKFLOW_DRAFT);
            }

            $soal->similarities()->delete();
            $soal->forceFill([
                'similarity_checked_at' => null,
                'similarity_summary' => null,
                'similarity_ack_note' => null,
                'similarity_ack_by' => null,
                'similarity_ack_at' => null,
            ])->save();

            // Replace options (simpler than diff for now)
            if (in_array($validated['tipe_soal'], ['pg', 'bs', 'jodoh']) && $request->filled('options')) {
                $soal->options()->delete();
                foreach ($request->options as $i => $opt) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => $opt['label'],
                        'teks_opsi' => $opt['teks_opsi'],
                        'is_correct' => filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'urutan' => $i + 1,
                    ]);
                }
            } else {
                $soal->options()->delete();
            }

            return redirect()
                ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $soal->bank_soal_id])
                ->with('success', 'Soal berhasil diperbarui.');
        });
    }

    /**
     * Delete a Soal.
     */
    public function destroy(string $userId, string $bankId, string $id)
    {
        $soal = Soal::findOrFail($id);
        $bank = $soal->bankSoal;

        $this->authorizeEdit(request(), $bank, $soal);

        $soal->delete();

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Ajukan soal untuk review serumpun:
     *  1) jalankan automatic similarity check (cross-bank/historical),
     *  2) catat alasan bila penyusun tetap melanjutkan meski ada kemiripan,
     *  3) tugaskan reviewer serumpun lintas satuan pendidikan.
     * Similarity adalah warning — guru tetap dapat melanjutkan review.
     */
    public function submitForReview(Request $request, string $userId, string $bankId, string $id)
    {
        $soal = Soal::findOrFail($id);
        $user = $request->user();

        $this->authorizeManage($request, $soal->bankSoal);

        $check = app(SoalSimilarityService::class)->check($soal, 5, 'review');

        $ackNote = trim((string) $request->input('ack_note', ''));
        if ($check['summary']['total'] > 0 && $ackNote !== '') {
            $soal->forceFill([
                'similarity_ack_note' => $ackNote,
                'similarity_ack_by' => $user->id,
                'similarity_ack_at' => now(),
            ])->save();
        }

        $assignments = app(ReviewWorkflowService::class)->submit($soal, $user);

        $warning = $check['summary']['total'] > 0
            ? " Ditemukan {$check['summary']['total']} soal historis mirip (tertinggi {$check['summary']['highest']}%) — mohon ditinjau reviewer."
            : ' Tidak ditemukan kemiripan signifikan dengan soal historis.';

        $ack = $ackNote !== '' ? ' Alasan melanjutkan tercatat.' : '';

        return back()->with('success', 'Soal diajukan untuk review ('.count($assignments).' reviewer serumpun).'.$warning.$ack);
    }

    /**
     * Approve langsung oleh tim kurikulum (jalur legacy).
     * Alur utama tetap melalui review serumpun (ReviewSoalController).
     */
    public function approve(string $userId, string $bankId, string $id)
    {
        // Approval langsung tidak lagi dipakai. Soal hanya boleh dipakai
        // setelah disetujui oleh guru serumpun melalui ReviewSoalController.
        Soal::findOrFail($id);

        return back()->with('error', 'Soal tidak dapat disetujui langsung. Ajukan review dan tunggu persetujuan seluruh guru serumpun.');
    }

    // ── Otorisasi bank soal (tanpa Gate: registrar snapshot meng-intercept ability) ──

    /**
     * Akses baca bank soal: tim/TU, bank accessible (owner/publik/internal),
     * atau penyusun.
     */
    private function authorizeBankAccess(Request $request, ?BankSoal $bank): void
    {
        $user = $request->user();

        if (! $bank) {
            abort(404);
        }

        if (app(KurikulumAccess::class)->canAccessAllBankSoal($user)) {
            return;
        }

        $accessible = BankSoal::query()
            ->accessibleBy($user->id, $request->attributes->get('schoolContextId'))
            ->whereKey($bank->id)
            ->exists();

        if (! $accessible) {
            abort(403, 'Anda tidak berwenang mengakses bank soal ini.');
        }
    }

    private function authorizeManage(Request $request, ?BankSoal $bank): void
    {
        $user = $request->user();

        if (! $bank) {
            abort(404);
        }

        $isOwner = $bank->owner_user_id === $user->id || $bank->created_by === $user->id;
        $isTeam = app(KurikulumAccess::class)->isKurikulumTeam($user);

        // Kebijakan Tahap 1: guru mengelola bank pada rumpun kewenangannya
        // (bukan hanya kesamaan mapel) dan hanya untuk bank yang dapat diakses.
        $resolver = app(SubjectGroupResolver::class);
        $bankGroup = $bank->subject ? $resolver->groupForSubject($bank->subject) : null;
        $inRumpun = $bankGroup && $resolver->groupsForTeacher($user)->contains('id', $bankGroup->id);
        $accessible = BankSoal::query()
            ->accessibleBy($user->id, $request->attributes->get('schoolContextId'))
            ->whereKey($bank->id)
            ->exists();

        if (! $isOwner && ! $isTeam && ! ($inRumpun && $accessible)) {
            abort(403, 'Anda tidak berwenang mengelola soal pada bank ini.');
        }
    }

    private function authorizeEdit(Request $request, ?BankSoal $bank, Soal $soal): void
    {
        $user = $request->user();

        $isAuthor = $soal->dibuat_oleh === $user->id;
        $isTeam = app(KurikulumAccess::class)->isKurikulumTeam($user);
        $isBankOwner = $bank && ($bank->owner_user_id === $user->id || $bank->created_by === $user->id);

        if (! $isAuthor && ! $isTeam && ! $isBankOwner) {
            abort(403, 'Hanya pembuat soal atau tim kurikulum yang dapat mengubah soal ini.');
        }
    }
}
