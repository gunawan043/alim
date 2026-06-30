<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\TujuanPembelajaran;
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
            'bank'   => $bank,
            'userId' => $userId,
        ]);
    }

    /**
     * Show create form for a new Soal within a BankSoal.
     */
    public function create(string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);

        Gate::authorize('createSoal', $bank);

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

        Gate::authorize('createSoal', $bank);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'pertanyaan' => 'required|string',
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
            $soal = new Soal;
            $soal->fill([
                'bank_soal_id' => $bank->id,
                'tp_id' => $validated['tp_id'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
                'pertanyaan' => $validated['pertanyaan'],
                'gambar_path' => $validated['gambar_path'] ?? null,
                'audio_path' => $validated['audio_path'] ?? null,
                'bobot_default' => $validated['bobot_default'],
                'tingkat_kesulitan_estimasi' => $validated['tingkat_kesulitan_estimasi'],
                'waktu_estimasi_menit' => $validated['waktu_estimasi_menit'],
                'tags' => $validated['tags'] ? array_map('trim', explode(',', $validated['tags'])) : null,
                'status' => 'draft',
                'dibuat_oleh' => $userId,
            ]);

            // Auto-hash for dedupe detection
            $soal->content_hash = hash('sha256', $soal->pertanyaan);

            $soal->save();

            // Persist options if relevant
            if (in_array($validated['tipe_soal'], ['pg', 'bs', 'jodoh']) && $request->filled('options')) {
                foreach ($request->options as $i => $opt) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => $opt['label'],
                        'teks_opsi' => $opt['teks_opsi'],
                        'is_correct' => isset($opt['is_correct']) && $opt['is_correct'] === '1',
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
        $soal = Soal::with('options')->findOrFail($id);
        $bank = $soal->bankSoal;

        Gate::authorize('updateSoal', [$bank, $soal]);

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

        Gate::authorize('updateSoal', [$bank, $soal]);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'pertanyaan' => 'required|string',
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
            $soal->fill([
                'tp_id' => $validated['tp_id'] ?? null,
                'tipe_soal' => $validated['tipe_soal'],
                'pertanyaan' => $validated['pertanyaan'],
                'gambar_path' => $validated['gambar_path'] ?? null,
                'audio_path' => $validated['audio_path'] ?? null,
                'bobot_default' => $validated['bobot_default'],
                'tingkat_kesulitan_estimasi' => $validated['tingkat_kesulitan_estimasi'],
                'waktu_estimasi_menit' => $validated['waktu_estimasi_menit'],
                'tags' => $validated['tags'] ? array_map('trim', explode(',', $validated['tags'])) : null,
            ]);
            $soal->content_hash = hash('sha256', $soal->pertanyaan);
            $soal->save();

            // Replace options (simpler than diff for now)
            if (in_array($validated['tipe_soal'], ['pg', 'bs', 'jodoh']) && $request->filled('options')) {
                $soal->options()->delete();
                foreach ($request->options as $i => $opt) {
                    SoalOption::create([
                        'soal_id' => $soal->id,
                        'label' => $opt['label'],
                        'teks_opsi' => $opt['teks_opsi'],
                        'is_correct' => isset($opt['is_correct']) && $opt['is_correct'] === '1',
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

        Gate::authorize('updateSoal', [$bank, $soal]);

        $soal->delete();

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Submit soal for review (draft → submitted).
     */
    public function submitForReview(string $userId, string $bankId, string $id)
    {
        $soal = Soal::findOrFail($id);
        $soal->update(['status' => 'submitted']);

        return back()->with('success', 'Soal disubmit untuk review.');
    }

    /**
     * Approve a soal (kepalasekolah / kaprog only — via Gate).
     */
    public function approve(string $userId, string $bankId, string $id)
    {
        $soal = Soal::findOrFail($id);

        Gate::authorize('approveSoal', $soal);

        $soal->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Soal disetujui.');
    }
}
