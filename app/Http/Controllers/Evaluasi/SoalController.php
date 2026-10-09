<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\Evaluasi\ContentHashEngine;
use App\Services\Evaluasi\ReviewWorkflowService;
use App\Services\Evaluasi\SoalSimilarityService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SoalController extends Controller
{
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
    public function create(string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);

        $this->authorizeManage(request(), $bank);

        $tps = TujuanPembelajaran::where('subject_id', $bank->subject_id)->get();

        $tipeSoal = [
            'pg' => 'Pilihan Ganda',
            'bs' => 'Benar / Salah',
            'jodoh' => 'Menjodohkan',
            'isian' => 'Isian Singkat',
            'uraian' => 'Uraian',
        ];

        return view('evalusi.soal.create', compact('bank', 'tps', 'tipeSoal', 'userId'));
    }

    /**
     * Persist a new Soal (with its options).
     */
    public function store(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);

        $this->authorizeManage(request(), $bank);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
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
                'materi' => $validated['materi'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
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

    /**
     * Show edit form for Soal.
     */
    public function edit(string $userId, string $bankId, string $id)
    {
        $soal = Soal::with([
            'options',
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
                'materi' => $validated['materi'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
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
        $soal = Soal::findOrFail($id);

        if (! app(KurikulumAccess::class)->isKurikulumTeam(request()->user())) {
            abort(403, 'Hanya tim kurikulum yang dapat menyetujui langsung.');
        }

        $soal->syncWorkflowStatus(Soal::WORKFLOW_APPROVED);
        $soal->forceFill(['approved_by' => $userId])->save();

        return back()->with('success', 'Soal disetujui.');
    }

    // ── Otorisasi bank soal (tanpa Gate: registrar snapshot meng-intercept ability) ──

    private function authorizeManage(Request $request, ?BankSoal $bank): void
    {
        $user = $request->user();

        if (! $bank) {
            abort(404);
        }

        $isOwner = $bank->owner_user_id === $user->id || $bank->created_by === $user->id;
        $isTeam = app(KurikulumAccess::class)->isKurikulumTeam($user);
        $isSubjectTeacher = TeachingAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('subject_id', $bank->subject_id)
            ->where('status', 'active')
            ->exists();

        if (! $isOwner && ! $isTeam && ! $isSubjectTeacher) {
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
