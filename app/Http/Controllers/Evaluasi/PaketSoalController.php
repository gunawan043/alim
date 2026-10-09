<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\GtkEmployment;
use App\Models\KisiKisiSoal;
use App\Models\KisiKisiSoalItem;
use App\Models\PaketSoal;
use App\Models\PaketSoalDistribution;
use App\Models\PaketSoalItem;
use App\Models\School;
use App\Models\Soal;
use App\Services\Evaluasi\PaketQualityGateService;
use App\Services\Evaluasi\ReviewWorkflowService;
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

        $baseQuery = PaketSoal::whereHas('kisiKisi', fn ($q) => $q->where('school_id', $schoolId));

        $query = (clone $baseQuery)->with(['kisiKisi.subject', 'kisiKisi.gradeLevel', 'items']);

        if ($request->filled('jenis_ujian')) {
            $query->whereHas('kisiKisi', fn ($q) => $q->where('jenis_ujian', $request->jenis_ujian));
        }
        if ($request->filled('status')) {
            if ($request->status === 'draft') {
                $query->where('workflow_status', PaketSoal::WORKFLOW_DRAFT)->where('is_published', false);
            } elseif ($request->status === 'review') {
                $query->whereIn('workflow_status', [PaketSoal::WORKFLOW_REVIEW, PaketSoal::WORKFLOW_REVISI])
                    ->where('is_published', false);
            } elseif ($request->status === 'final') {
                $query->where(function ($q) {
                    $q->where('is_published', true)
                        ->orWhereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED]);
                });
            }
        }

        $pakets = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('workflow_status', PaketSoal::WORKFLOW_DRAFT)
                ->where('is_published', false)->count(),
            'review' => (clone $baseQuery)
                ->whereIn('workflow_status', [PaketSoal::WORKFLOW_REVIEW, PaketSoal::WORKFLOW_REVISI])
                ->where('is_published', false)->count(),
            'final' => (clone $baseQuery)->where(function ($q) {
                $q->where('is_published', true)
                    ->orWhereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED]);
            })->count(),
        ];

        return view('evalusi.paket-soal.index', compact('pakets', 'statistics'));
    }

    /**
     * Show create form with auto-selection preview.
     */
    public function create(Request $request, string $userId, string $kisiKisiId)
    {
        $kisi = KisiKisiSoal::with(['items.tujuanPembelajaran', 'subject'])->findOrFail($kisiKisiId);

        return view('evalusi.paket-soal.create', compact('kisi'));
    }

    /**
     * Build paket soal by auto-selecting soal from BankSoal matching kisi-kisi items.
     */
    public function store(Request $request, string $userId, string $kisiKisiId)
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
    public function show(string $userId, string $paketUuid)
    {
        $paket = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel',
            'items.soal.options'])
            ->findOrFail($paketUuid);

        return view('evalusi.paket-soal.show', compact('paket'));
    }

    /**
     * Publish paket soal (locks the soal selection).
     */
    public function publish(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        if ($paket->jumlah_soal_aktual === 0) {
            return back()->with('error', 'Paket tidak memiliki soal. Tambahkan soal sebelum publish.');
        }

        $paket->publish($request->user()?->id);

        return back()->with('success', 'Paket soal berhasil dipublish.');
    }

    /**
     * Unpublish paket soal.
     */
    public function unpublish(string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);
        $paket->update(['is_published' => false, 'published_at' => null]);

        return back()->with('success', 'Paket soal di-unpublish.');
    }

    /**
     * Re-roll soal selection (delete current items and re-pick).
     */
    public function reroll(string $userId, string $paketUuid)
    {
        return DB::transaction(function () use ($paketUuid) {
            $paket = PaketSoal::with('kisiKisi.items')->findOrFail($paketUuid);

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
    public function destroy(string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);
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

    // ══════════════════════════════════════════════════════════════════
    // QUALITY GATE → APPROVAL → DISTRIBUSI (Bank Soal Terpusat)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Jalankan quality gate: duplikasi internal + kemiripan historis.
     */
    public function qualityGate(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);
        $result = app(PaketQualityGateService::class)->run($paket);
        $summary = $result['summary'];

        return back()->with(
            'success',
            "Quality gate selesai: {$summary['internal_duplicates']} duplikasi internal, "
            ."{$summary['historical_warnings']} kemiripan historis (tertinggi {$summary['highest_historical']}%)."
        );
    }

    /**
     * Ajukan paket untuk approval reviewer serumpun.
     * Hanya soal approved yang boleh menjadi bagian paket final.
     */
    public function submitApproval(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::with(['kisiKisi', 'items.soal'])->findOrFail($paketUuid);

        if ($paket->items->isEmpty()) {
            return back()->with('error', 'Paket belum memiliki soal.');
        }

        $notApproved = $paket->items->filter(fn ($item) => ! $item->soal?->isApproved())->count();
        if ($notApproved > 0) {
            return back()->with('error', "{$notApproved} soal belum tervalidasi — hanya soal approved yang boleh masuk paket final.");
        }

        if (! $paket->similarity_checked_at) {
            app(PaketQualityGateService::class)->run($paket);
        }

        $assignments = app(ReviewWorkflowService::class)->submit($paket, $request->user());

        return back()->with('success', 'Paket diajukan untuk approval ('.count($assignments).' reviewer serumpun).');
    }

    /**
     * Halaman distribusi & quality gate paket.
     */
    public function distribution(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::with([
            'kisiKisi.subject', 'kisiKisi.gradeLevel', 'kisiKisi.academicYear',
            'items.soal.options', 'distributions.recipient', 'printJobs.creator',
        ])->findOrFail($paketUuid);

        $progress = app(ReviewWorkflowService::class)->progress($paket);
        $summary = $paket->similarity_summary ?? [];
        $notApproved = $paket->items->filter(fn ($item) => ! $item->soal?->isApproved())->count();

        return view('evalusi.paket-soal.distribusi', compact('paket', 'progress', 'summary', 'notApproved'));
    }

    /**
     * Distribusikan paket final via sistem ke TU, Waka, Kurikulum, Koordinator, KSP.
     */
    public function distribute(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::with(['kisiKisi', 'items.soal'])->findOrFail($paketUuid);

        if (! $paket->isFinal()) {
            return back()->with('error', 'Hanya paket final (approved + dipublikasikan) yang dapat didistribusikan.');
        }

        $notApproved = $paket->items->filter(fn ($item) => ! $item->soal?->isApproved())->count();
        if ($notApproved > 0) {
            return back()->with('error', "Ada {$notApproved} soal belum tervalidasi pada paket ini.");
        }

        $recipients = $this->resolveRecipients($paket);

        DB::transaction(function () use ($paket, $recipients, $request) {
            PaketSoalDistribution::where('paket_soal_id', $paket->id)->delete();

            foreach ($recipients as $recipient) {
                PaketSoalDistribution::create([
                    'paket_soal_id' => $paket->id,
                    'recipient_user_id' => $recipient['user_id'] ?? null,
                    'recipient_role' => $recipient['role'],
                    'recipient_name' => $recipient['name'] ?? $recipient['role'],
                    'status' => PaketSoalDistribution::STATUS_SENT,
                    'distributed_at' => now(),
                    'distributed_by' => $request->user()?->id,
                ]);
            }

            $paket->forceFill([
                'distributed_at' => now(),
                'workflow_status' => PaketSoal::WORKFLOW_PUBLISHED,
            ])->save();

            app(ReviewWorkflowService::class)->audit($paket, 'distributed', $request->user(), [
                'recipients' => count($recipients),
            ]);
        });

        return back()->with('success', 'Paket didistribusikan ke '.count($recipients).' penerima langsung melalui sistem.');
    }

    /**
     * @return array<int, array{role: string, user_id?: ?string, name?: ?string}>
     */
    private function resolveRecipients(PaketSoal $paket): array
    {
        $schoolId = $paket->kisiKisi?->school_id;

        $definitions = [
            ['role' => 'Tata Usaha', 'like' => 'tata usaha'],
            ['role' => 'Waka', 'like' => 'wakil'],
            ['role' => 'Kurikulum', 'like' => 'kurikulum'],
            ['role' => 'Koordinator', 'like' => 'koordinator'],
            ['role' => 'KSP', 'principal' => true],
        ];

        $recipients = [];

        foreach ($definitions as $definition) {
            $user = null;

            if (! empty($definition['principal'])) {
                $principalId = $schoolId ? School::where('id', $schoolId)->value('principal_user_id') : null;
                $user = $principalId ? \App\Models\User::find($principalId) : null;
            } else {
                $employment = GtkEmployment::query()
                    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                    ->where('jabatan', 'like', '%'.$definition['like'].'%')
                    ->first();
                $user = $employment ? \App\Models\User::find($employment->user_id) : null;
            }

            $recipients[] = [
                'role' => $definition['role'],
                'user_id' => $user?->id,
                'name' => $user?->name,
            ];
        }

        return $recipients;
    }
}
