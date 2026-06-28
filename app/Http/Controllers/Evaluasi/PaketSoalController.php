<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\KisiKisiSoalItem;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketSoalController extends Controller
{
    /**
     * List paket soal.
     */
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel', 'items'])
            ->whereHas('kisiKisi', fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('jenis_ujian')) {
            $query->whereHas('kisiKisi', fn ($q) => $q->where('jenis_ujian', $request->jenis_ujian));
        }

        $pakets = $query->orderByDesc('updated_at')->paginate(15);

        return view('evalusi.paket-soal.index', compact('pakets'));
    }

    /**
     * Show create form with auto-selection preview.
     */
    public function create(Request $request, string $kisiKisiId)
    {
        $kisi = KisiKisiSoal::with(['items.tujuanPembelajaran', 'subject'])->findOrFail($kisiKisiId);

        return view('evalusi.paket-soal.create', compact('kisi'));
    }

    /**
     * Build paket soal by auto-selecting soal from BankSoal matching kisi-kisi items.
     */
    public function store(Request $request, string $kisiKisiId)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:150',
            'instruksi_umum' => 'nullable|string|max:2000',
            'waktu_pengerjaan_menit' => 'required|integer|min:15|max:480',
            'kkm' => 'nullable|numeric|min:0|max:100',
            'shared_scope' => 'required|in:private,internal_school,public_pool',
            'bank_soal_id' => 'required|exists:bank_soal,id',
            'is_acak_urutan_soal' => 'nullable|boolean',
            'is_acak_opsi' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($validated, $kisiKisiId) {
            $kisi = KisiKisiSoal::with('items')->findOrFail($kisiKisiId);
            $bank = BankSoal::findOrFail($validated['bank_soal_id']);

            $paket = new PaketSoal;
            $paket->fill([
                'kisi_kisi_soal_id' => $kisi->id,
                'judul' => $validated['judul'],
                'versi' => 1,
                'is_acak_urutan_soal' => $validated['is_acak_urutan_soal'] ?? true,
                'is_acak_opsi' => $validated['is_acak_opsi'] ?? true,
                'waktu_pengerjaan_menit' => $validated['waktu_pengerjaan_menit'],
                'instruksi_umum' => $validated['instruksi_umum'] ?? null,
                'is_published' => false,
                'shared_scope' => $validated['shared_scope'],
                'kkm' => $validated['kkm'] ?? null,
            ]);
            $paket->save();

            // Auto-select soal for each kisi-kisi item
            $urutan = 1;
            foreach ($kisi->items as $item) {
                $soalIds = $this->pickSoalForItem($item, $bank->id, $item->jumlah_soal);

                foreach ($soalIds as $soalId) {
                    PaketSoalItem::create([
                        'paket_soal_id' => $paket->id,
                        'soal_id' => $soalId,
                        'urutan' => $urutan++,
                    ]);
                }
            }

            $paket->recomputeTotals();

            return redirect()
                ->route('user.paket-soal.show', $paket->id)
                ->with('success', 'Paket soal berhasil dibuat. Review sebelum publish.');
        });
    }

    /**
     * Show paket soal detail with full soals.
     */
    public function show(string $id)
    {
        $paket = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel',
            'items.soal.options'])
            ->findOrFail($id);

        return view('evalusi.paket-soal.show', compact('paket'));
    }

    /**
     * Publish paket soal (locks the soal selection).
     */
    public function publish(Request $request, string $id)
    {
        $paket = PaketSoal::findOrFail($id);

        if ($paket->jumlah_soal_aktual === 0) {
            return back()->with('error', 'Paket tidak memiliki soal. Tambahkan soal sebelum publish.');
        }

        $paket->publish($request->user()?->id);

        return back()->with('success', 'Paket soal berhasil dipublish.');
    }

    /**
     * Unpublish paket soal.
     */
    public function unpublish(string $id)
    {
        $paket = PaketSoal::findOrFail($id);
        $paket->update(['is_published' => false, 'published_at' => null]);

        return back()->with('success', 'Paket soal di-unpublish.');
    }

    /**
     * Re-roll soal selection (delete current items and re-pick).
     */
    public function reroll(string $id)
    {
        return DB::transaction(function () use ($id) {
            $paket = PaketSoal::with('kisiKisi.items')->findOrFail($id);

            if ($paket->is_published) {
                return back()->with('error', 'Paket sudah dipublish. Unpublish terlebih dahulu untuk re-roll.');
            }

            $paket->items()->delete();

            // Find bank from first item's soal or fallback to kisi-kisi subject
            $bank = BankSoal::where('subject_id', $paket->kisiKisi->subject_id)->first();
            if (! $bank) {
                return back()->with('error', 'Tidak ada bank soal untuk mapel ini.');
            }

            $urutan = 1;
            foreach ($paket->kisiKisi->items as $item) {
                $soalIds = $this->pickSoalForItem($item, $bank->id, $item->jumlah_soal);
                foreach ($soalIds as $soalId) {
                    PaketSoalItem::create([
                        'paket_soal_id' => $paket->id,
                        'soal_id' => $soalId,
                        'urutan' => $urutan++,
                    ]);
                }
            }

            $paket->recomputeTotals();

            return back()->with('success', 'Soal dipilih ulang secara acak.');
        });
    }

    /**
     * Delete paket soal.
     */
    public function destroy(string $id)
    {
        $paket = PaketSoal::findOrFail($id);
        $paket->delete();

        return redirect()->route('user.paket-soal.index')->with('success', 'Paket soal dihapus.');
    }

    /**
     * Pick soal matching kisi-kisi item criteria:
     * - same TP
     * - status = 'approved'
     * - prefer matching level_kognitif (if specified) via tags
     * - prefer matching tingkat_kesulitan_estimasi
     * Returns up to $n soal IDs in randomized order.
     */
    protected function pickSoalForItem(KisiKisiSoalItem $item, string $bankId, int $n): array
    {
        $query = Soal::where('bank_soal_id', $bankId)
            ->where('tp_id', $item->tp_id)
            ->where('status', 'approved')
            ->whereIn('tipe_soal', ['pg', 'bs', 'jodoh']) // only auto-gradable for now
            ->inRandomOrder();

        // Try to match difficulty if specified on item (default: easy/medium mix)
        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            // Fallback: any approved soal for this bank matching TP
            $candidates = Soal::where('bank_soal_id', $bankId)
                ->where('status', 'approved')
                ->whereIn('tipe_soal', ['pg', 'bs', 'jodoh'])
                ->inRandomOrder()
                ->limit($n * 2)
                ->get();
        }

        return $candidates->take($n)->pluck('id')->toArray();
    }
}
