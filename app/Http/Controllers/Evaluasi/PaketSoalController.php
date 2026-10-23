<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
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
use App\Services\Evaluasi\PaketNaskahService;
use App\Services\Evaluasi\PaketQualityGateService;
use App\Services\Evaluasi\ReviewWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketSoalController extends Controller
{
    use AuthorizesAcademicScope;

    /**
     * List paket soal.
     */
    /**
     * Guard kisi-kisi saat membangun paket: batas satuan pendidikan,
     * perubahan hanya penyusun kisi atau tim kurikulum.
     */
    private function authorizePaketKisi(Request $request, KisiKisiSoal $kisi, bool $write = true): void
    {
        $this->ensureSchoolScope($request, $kisi->school_id, 'Kisi-kisi tidak ditemukan pada satuan pendidikan Anda.');

        if (! $write) {
            return;
        }

        $user = $request->user();

        if (app(\App\Services\KurikulumAccess::class)->isKurikulumTeam($user)) {
            return;
        }

        if ((string) $kisi->created_by === (string) $user->id) {
            return;
        }

        abort(403, 'Anda tidak berwenang membangun paket dari kisi-kisi ini.');
    }

    /**
     * Guard paket: batas satuan pendidikan + kewenangan mengubah.
     * TU/Kurikulum/Waka boleh melihat sesuai scope; perubahan hanya
     * penyusun kisi-kisi (owner) atau tim kurikulum (TU tidak mengubah isi akademik).
     */
    private function authorizePaket(Request $request, PaketSoal $paket, bool $write = true): void
    {
        $paket->loadMissing('kisiKisi');

        $this->ensureSchoolScope(
            $request,
            $paket->kisiKisi?->school_id,
            'Paket soal tidak ditemukan pada satuan pendidikan Anda.'
        );

        if (! $write) {
            return;
        }

        $user = $request->user();

        if (app(\App\Services\KurikulumAccess::class)->isKurikulumTeam($user)) {
            return;
        }

        if ((string) ($paket->kisiKisi?->created_by) === (string) $user->id) {
            return;
        }

        abort(403, 'Anda tidak berwenang mengubah paket ini.');
    }

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

        $this->authorizePaketKisi($request, $kisi, false);

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

        return DB::transaction(function () use ($validated, $kisiKisiId, $request, $userId) {
            $kisi = KisiKisiSoal::with('items')->findOrFail($kisiKisiId);

            $this->authorizePaketKisi($request, $kisi);
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
            app(PaketNaskahService::class)->sinkronMeta($paket);

            return redirect()
                ->route('user.paket-soal.show', ['userId' => $userId, 'paketUuid' => $paket->id])
                ->with('success', 'Paket soal berhasil dibuat. Review sebelum publish.');
        });
    }

    /**
     * Show paket soal detail with full soals.
     */
    public function show(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel',
            'items.soal.options'])
            ->findOrFail($paketUuid);

        $this->authorizePaket($request, $paket, false);

        $naskah = app(PaketNaskahService::class);
        $komposisi = $paket->komposisi ?? [];
        $validasiNaskah = $naskah->validasi($paket);
        $bisaKelolaNaskah = ! $paket->is_published && ! $paket->hasSnapshot()
            && in_array($paket->workflow_status, [PaketSoal::WORKFLOW_DRAFT, PaketSoal::WORKFLOW_REVISI], true);

        return view('evalusi.paket-soal.show', compact('paket', 'komposisi', 'validasiNaskah', 'bisaKelolaNaskah'));
    }

    /**
     * Publish paket soal (locks the soal selection).
     */
    public function publish(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        $this->authorizePaket($request, $paket);

        // Kebijakan approval: paket hanya boleh dipublikasikan setelah
        // disetujui seluruh reviewer (workflow Approved). Publish dari draft
        // tidak lagi memungkinkan agar paket "final" tidak melewati approval.
        if ($paket->workflow_status === PaketSoal::WORKFLOW_PUBLISHED || $paket->is_published) {
            return back()->with('error', 'Paket ini sudah dipublikasikan.');
        }

        if ($paket->workflow_status !== PaketSoal::WORKFLOW_APPROVED) {
            return back()->with('error', 'Paket hanya dapat dipublikasikan setelah disetujui seluruh reviewer (status Approved).');
        }

        if ($paket->jumlah_soal_aktual === 0) {
            return back()->with('error', 'Paket tidak memiliki soal. Tambahkan soal sebelum publish.');
        }

        // Naskah: komposisi harus terpenuhi, lalu butir dibekukan (snapshot).
        $naskah = app(PaketNaskahService::class);
        $validasi = $naskah->validasi($paket);

        if (! $validasi['ok']) {
            return back()->with('error', 'Komposisi naskah belum terpenuhi — '.implode('; ', $validasi['errors']).'.');
        }

        $naskah->snapshot($paket);
        $paket->refresh();

        $paket->publish($request->user()?->id);

        return back()->with('success', 'Paket soal berhasil dipublish (naskah butir dibekukan).');
    }

    /**
     * Unpublish paket soal.
     */
    public function unpublish(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        $this->authorizePaket($request, $paket);

        if ($paket->workflow_status !== PaketSoal::WORKFLOW_PUBLISHED && ! $paket->is_published) {
            return back()->with('error', 'Paket belum dipublikasikan.');
        }

        // State machine: unpublish mengembalikan paket ke Approved (naskah
        // snapshot tetap tersimpan sebagai riwayat versi final).
        $paket->update([
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
            'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
        ]);

        return back()->with('success', 'Paket soal di-unpublish (kembali ke status Approved).');
    }

    /**
     * Re-roll soal selection (delete current items and re-pick).
     */
    public function reroll(Request $request, string $userId, string $paketUuid)
    {
        return DB::transaction(function () use ($paketUuid, $request) {
            $paket = PaketSoal::with('kisiKisi.items')->findOrFail($paketUuid);

            $this->authorizePaket($request, $paket);

            if ($paket->is_published) {
                return back()->with('error', 'Paket sudah dipublish. Unpublish terlebih dahulu untuk re-roll.');
            }

            if ($paket->hasSnapshot()) {
                return back()->with('error', 'Butir paket sudah dibekukan sebagai naskah — re-roll tidak lagi tersedia.');
            }

            // State machine: pilih ulang hanya pada draft / perlu perbaikan.
            if (! in_array($paket->workflow_status, [PaketSoal::WORKFLOW_DRAFT, PaketSoal::WORKFLOW_REVISI], true)) {
                return back()->with('error', 'Paket sedang dalam proses approval — pilih ulang soal tidak tersedia.');
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
            app(PaketNaskahService::class)->sinkronMeta($paket);

            return back()->with('success', 'Soal dipilih ulang secara acak.');
        });
    }

    /**
     * Delete paket soal.
     */
    public function destroy(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        $this->authorizePaket($request, $paket);

        // Naskah final tidak boleh dihapus — riwayat paket harus utuh.
        if ($paket->is_published || $paket->workflow_status === PaketSoal::WORKFLOW_PUBLISHED) {
            return back()->with('error', 'Paket final tidak dapat dihapus. Unpublish dan batalkan approval bila perlu.');
        }

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

        $this->authorizePaket($request, $paket);
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

        $this->authorizePaket($request, $paket);

        if ($paket->items->isEmpty()) {
            return back()->with('error', 'Paket belum memiliki soal.');
        }

        // State machine: ajukan approval hanya dari draft / perlu perbaikan.
        if (! in_array($paket->workflow_status, [PaketSoal::WORKFLOW_DRAFT, PaketSoal::WORKFLOW_REVISI], true)) {
            return back()->with('error', 'Paket sudah dalam proses approval atau final — pengajuan ulang tidak diperlukan.');
        }

        if ($paket->hasSnapshot()) {
            return back()->with('error', 'Butir paket sudah dibekukan sebagai naskah final.');
        }

        // Naskah: komposisi wajib terpenuhi sebelum diajukan.
        $validasi = app(PaketNaskahService::class)->validasi($paket);
        if (! $validasi['ok']) {
            return back()->with('error', 'Komposisi naskah belum terpenuhi — '.implode('; ', $validasi['errors']).'.');
        }

        $notApproved = $paket->items->filter(fn ($item) => ! $item->soal?->isApproved())->count();
        if ($notApproved > 0) {
            return back()->with('error', "{$notApproved} soal belum tervalidasi — hanya soal approved yang boleh masuk paket final.");
        }

        if (! $paket->similarity_checked_at) {
            app(PaketQualityGateService::class)->run($paket);
        }

        // Kebijakan: approval final paket oleh SELURUH anggota rumpun (kolektif),
        // bukan koordinator tunggal — review/telaah tetap terpisah.
        $workflow = app(ReviewWorkflowService::class);
        $assignments = $workflow->submit(
            $paket,
            $request->user(),
            $workflow->resolveRumpunApprovers($paket, $request->user())
        );

        return back()->with('success', 'Paket diajukan untuk approval ('.count($assignments).' approver anggota rumpun).');
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

        $this->authorizePaket($request, $paket);

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

        $this->authorizePaket($request, $paket);

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
     * Simpan komposisi naskah paket (jenis/jumlah/bobot/urutan).
     */
    public function simpanKomposisi(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        $this->authorizePaket($request, $paket);

        if ($paket->is_published || $paket->hasSnapshot()) {
            return back()->with('error', 'Naskah final terkunci — komposisi tidak dapat diubah.');
        }

        // Baris kosong diabaikan (form menampilkan beberapa baris sekaligus).
        $rows = collect($request->input('komposisi', []))
            ->filter(fn ($row) => ! empty($row['jenis']))
            ->values()
            ->all();
        $request->merge(['komposisi' => $rows]);

        $validated = $request->validate([
            'komposisi' => 'required|array|min:1',
            'komposisi.*.jenis' => 'required|string|in:'.implode(',', PaketNaskahService::JENIS_VALID),
            'komposisi.*.jumlah' => 'required|integer|min:1|max:100',
            'komposisi.*.bobot' => 'required|numeric|min:0|max:100',
        ]);

        app(PaketNaskahService::class)->simpanKomposisi($paket, $validated['komposisi']);

        return back()->with('success', 'Komposisi naskah tersimpan.');
    }

    /**
     * Isi butir paket dari komposisi (mendukung pg/mcc/bs/jodoh/isian/uraian).
     */
    public function generateKomposisi(Request $request, string $userId, string $paketUuid)
    {
        $paket = PaketSoal::findOrFail($paketUuid);

        $this->authorizePaket($request, $paket);

        if ($paket->is_published || $paket->hasSnapshot()) {
            return back()->with('error', 'Naskah final terkunci — butir tidak dapat dibuat ulang.');
        }

        if (! in_array($paket->workflow_status, [PaketSoal::WORKFLOW_DRAFT, PaketSoal::WORKFLOW_REVISI], true)) {
            return back()->with('error', 'Paket sedang dalam proses approval — generate tidak tersedia.');
        }

        if (empty($paket->komposisi)) {
            return back()->with('error', 'Komposisi naskah belum disimpan.');
        }

        $created = app(PaketNaskahService::class)->generateDariKomposisi($paket);

        if ($created === 0) {
            return back()->with('error', 'Tidak ada soal approved yang cocok dengan komposisi pada bank.');
        }

        return back()->with('success', "Naskah diisi: {$created} butir sesuai komposisi.");
    }

    /**
     * Penerima menandai paket sudah diterima (fase distribusi `received`).
     */
    public function terimaDistribusi(Request $request, string $userId, string $paketUuid, string $distributionId)
    {
        $paket = PaketSoal::findOrFail($paketUuid);
        $distribution = PaketSoalDistribution::query()
            ->where('paket_soal_id', $paket->id)
            ->findOrFail($distributionId);

        if (! $distribution->recipient_user_id
            || (string) $distribution->recipient_user_id !== (string) $request->user()?->id) {
            abort(403, 'Hanya penerima paket yang dapat menandai diterima.');
        }

        if ($distribution->isReceived()) {
            return back()->with('success', 'Paket ini sudah Anda tandai diterima.');
        }

        $distribution->update([
            'status' => PaketSoalDistribution::STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => $request->user()->id,
        ]);

        app(ReviewWorkflowService::class)->audit($paket, 'distribution_received', $request->user(), [
            'distribution_id' => $distribution->id,
        ]);

        return back()->with('success', 'Paket ditandai sudah diterima.');
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

    // ─────────────────────────────────────────────────────────────
    // KARTU SOAL & VALIDASI SOAL (Increment 2 — item 4C & 4D)
    // ─────────────────────────────────────────────────────────────

    /**
     * Kartu Soal (web) — satu baris per butir soal paket.
     */
    public function naskah(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildNaskahData($request, $paketUuid);

        return view('evalusi.paket-soal.naskah', $data + ['userId' => $userId]);
    }

    public function naskahCetak(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildNaskahData($request, $paketUuid);

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'evalusi.paket-soal.naskah-cetak',
            $data,
            'Naskah-Soal-'.$data['paket']->judul,
        );
    }

    /**
     * Data naskah soal untuk siswa: tanpa kunci jawaban/pembahasan.
     *
     * @return array<string, mixed>
     */
    protected function buildNaskahData(Request $request, string $paketUuid): array
    {
        $paket = PaketSoal::with([
            'kisiKisi.subject',
            'kisiKisi.gradeLevel',
            'kisiKisi.academicYear',
        ])->findOrFail($paketUuid);

        $this->authorizePaket($request, $paket, false);

        $questions = $paket->items()
            ->with('soal')
            ->orderBy('urutan')
            ->get()
            ->map(function (PaketSoalItem $item) {
                $konten = $item->kontenFinal();

                return [
                    'type' => 'question',
                    'nomor' => (int) ($konten['nomor'] ?? $item->urutan),
                    'jenis' => $konten['jenis'] ?? $item->soal?->tipe_soal,
                    'wacana' => $konten['wacana'] ?? null,
                    'pertanyaan' => (string) ($konten['pertanyaan'] ?? ''),
                    'gambar_path' => $konten['gambar_path'] ?? null,
                    'gambar_keterangan' => $konten['gambar_keterangan'] ?? null,
                    'opsi' => collect($konten['opsi'] ?? [])->values()->all(),
                ];
            });

        // Wacana dicetak sekali pada posisi yang ditentukan (setelah soal ke-N),
        // bukan diulang di setiap soal yang memakainya.
        $wacanas = [];
        foreach ($questions as $question) {
            if (! empty($question['wacana']['id']) && ! isset($wacanas[$question['wacana']['id']])) {
                $wacanas[$question['wacana']['id']] = $question['wacana'];
            }
            $question['wacana'] = null;
        }
        $questions = $questions->sortBy('nomor')->values();

        $rows = collect();
        $inserted = [];
        foreach ($questions as $question) {
            foreach ($wacanas as $id => $wacana) {
                $position = (int) ($wacana['posisi_setelah_soal'] ?? 0);
                if (! isset($inserted[$id]) && $position < $question['nomor']) {
                    $rows->push(['type' => 'wacana', 'wacana' => $wacana]);
                    $inserted[$id] = true;
                }
            }

            $rows->push($question);
        }

        foreach ($wacanas as $id => $wacana) {
            if (! isset($inserted[$id])) {
                $rows->push(['type' => 'wacana', 'wacana' => $wacana]);
            }
        }

        $school = $paket->kisiKisi?->school_id
            ? School::find($paket->kisiKisi->school_id)
            : null;

        return [
            'paket' => $paket,
            'rows' => $rows,
            'school' => $school,
        ];
    }

    public function kartuSoal(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildKartuSoalData($request, $paketUuid);

        return view('evalusi.paket-soal.kartu-soal', $data + ['userId' => $userId]);
    }

    /**
     * Kartu Soal (cetak).
     */
    public function kartuSoalCetak(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildKartuSoalData($request, $paketUuid);
        $judul = $data['paket']->judul ?? $data['paket']->nama ?? 'Paket';

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'evalusi.paket-soal.kartu-soal-cetak',
            $data,
            'Kartu-Soal-'.$judul,
            'landscape',
        );
    }

    /**
     * Data Kartu Soal: nomor, indikator, soal, kunci (terlindungi),
     * level kognitif, dan skor — diambil dari butir soal sebenarnya.
     *
     * @return array<string, mixed>
     */
    protected function buildKartuSoalData(Request $request, string $paketUuid): array
    {
        $paket = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel', 'kisiKisi.academicYear'])
            ->findOrFail($paketUuid);

        $this->authorizePaket($request, $paket, false);

        $user = $request->user();
        $kurikulum = app(\App\Services\KurikulumAccess::class);

        // Level kognitif per TP dari kisi-kisi paket (sumber: item kisi-kisi).
        $levelByTp = collect();
        if ($paket->kisi_kisi_soal_id) {
            $levelByTp = KisiKisiSoalItem::where('kisi_kisi_soal_id', $paket->kisi_kisi_soal_id)
                ->get(['tp_id', 'level_kognitif'])
                ->groupBy('tp_id')
                ->map(fn ($rows) => $rows->first()->level_kognitif);
        }

        $items = $paket->items()
            ->with(['soal.options', 'soal.tujuanPembelajaran', 'soal.bankSoal.subject'])
            ->orderBy('urutan')
            ->get();

        $subject = $paket->kisiKisi?->subject;

        // KisiKisiSoal tidak memiliki relasi `creator` — resolusi lewat kolom created_by.
        $pembuatNama = $paket->kisiKisi?->created_by
            ? \App\Models\User::find($paket->kisiKisi->created_by)?->name
            : null;

        $rows = $items->map(function (PaketSoalItem $item) use ($user, $kurikulum, $levelByTp, $subject) {
            $konten = $item->kontenFinal();
            $soal = $item->soal;

            $soalSubject = $subject ?? $soal?->bankSoal?->subject;

            // Penyusun butir soal selalu berhak melihat kuncinya sendiri;
            // peran lain mengikuti kebijakan (serumpun / tim kurikulum).
            $isAuthor = $soal && (string) $soal->dibuat_oleh === (string) $user->id;
            $canSeeKey = $isAuthor || $kurikulum->canViewAnswerKeys($user, $soalSubject);

            $kunci = null;
            if ($canSeeKey) {
                $labels = collect($konten['opsi'] ?? [])->where('kunci', true)->pluck('label')->all();
                $kunci = $labels !== [] ? implode(', ', $labels) : ($konten['pembahasan'] ?? null);
            }

            $tp = $soal?->tujuanPembelajaran;
            $level = $levelByTp->get($konten['tp_id'] ?? $soal?->tp_id);

            return [
                'nomor' => $konten['nomor'] ?? ($item->nomor ?? $item->urutan),
                'jenis' => $konten['jenis'] ?? $soal?->tipe_soal,
                'indikator' => $tp
                    ? trim(($tp->kode_tp ? $tp->kode_tp.' — ' : '').(string) $tp->deskripsi)
                    : ($soal?->materi ?: null),
                'pertanyaan' => $konten['pertanyaan'] ?? null,
                'opsi' => $konten['opsi'] ?? [],
                'kunci' => $kunci,
                'kunci_terlindungi' => ! $canSeeKey,
                'pembahasan' => $canSeeKey ? ($konten['pembahasan'] ?? null) : null,
                'level' => $level,
                'level_label' => $this->levelKognitifLabel($level),
                'skor' => (float) ($konten['bobot'] ?? 0),
            ];
        });

        return [
            'paket' => $paket,
            'rows' => $rows,
            'pembuatNama' => $pembuatNama,
            'canSeeKeys' => $rows->contains(fn ($r) => ! $r['kunci_terlindungi']),
            'totalSkor' => (float) $rows->sum('skor'),
        ];
    }

    /**
     * Validasi Soal (web) — dokumen validasi paket: pembuat, validator,
     * soal yang perlu diperbaiki, dan rekomendasi.
     */
    public function validasiSoal(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildValidasiSoalData($request, $paketUuid);

        return view('evalusi.paket-soal.validasi-soal', $data + ['userId' => $userId]);
    }

    /**
     * Validasi Soal (cetak).
     */
    public function validasiSoalCetak(Request $request, string $userId, string $paketUuid)
    {
        $data = $this->buildValidasiSoalData($request, $paketUuid);
        $judul = $data['paket']->judul ?? $data['paket']->nama ?? 'Paket';

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'evalusi.paket-soal.validasi-soal-cetak',
            $data,
            'Validasi-Soal-'.$judul,
        );
    }

    /**
     * Data dokumen validasi: identitas asesmen, pembuat soal, validator
     * (dari penugasan review serumpun yang sah), soal yang perlu diperbaiki,
     * dan rekomendasi validator.
     *
     * @return array<string, mixed>
     */
    protected function buildValidasiSoalData(Request $request, string $paketUuid): array
    {
        $paket = PaketSoal::with(['kisiKisi.subject', 'kisiKisi.gradeLevel', 'kisiKisi.academicYear'])
            ->findOrFail($paketUuid);

        $this->authorizePaket($request, $paket, false);

        $items = $paket->items()
            ->with(['soal.tujuanPembelajaran', 'soal.creator'])
            ->orderBy('urutan')
            ->get();

        $subject = $paket->kisiKisi?->subject;
        $gradeLevel = $paket->kisiKisi?->gradeLevel;
        $academicYear = $paket->kisiKisi?->academicYear;
        $semester = $paket->kisiKisi?->semester;
        $jenisUjian = $paket->kisiKisi?->jenis_ujian;

        // Pembuat soal: penyusun kisi-kisi/paket + penulis butir soal.
        $kisiCreatorName = $paket->kisiKisi?->created_by
            ? \App\Models\User::find($paket->kisiKisi->created_by)?->name
            : null;

        $pembuat = collect()
            ->push($kisiCreatorName)
            ->merge($items->map(fn ($i) => $i->soal?->creator?->name))
            ->filter()
            ->unique()
            ->values();

        // Validator: dari penugasan review soal (workflow existing).
        $assignments = $items
            ->flatMap(fn ($i) => collect($i->soal?->reviewAssignments ?? [])->map(fn ($a) => [
                'soal' => $i,
                'assignment' => $a,
            ]))
            ->values();

        $validatorIds = $assignments->pluck('assignment.reviewer_id')->filter()->unique()->values();
        $validators = \App\Models\User::whereIn('id', $validatorIds)->pluck('name', 'id');

        $perValidator = $assignments
            ->groupBy(fn ($row) => $row['assignment']->reviewer_id)
            ->map(function ($rows, $reviewerId) use ($validators) {
                $statuses = $rows->groupBy(fn ($r) => $r['assignment']->status)->map->count();

                return [
                    'nama' => $validators[$reviewerId] ?? 'Validator',
                    'amount' => $rows->count(),
                    'approved' => $statuses['approved'] ?? 0,
                    'revision' => $statuses['revision'] ?? 0,
                    'pending' => $statuses['pending'] ?? 0,
                    'rejected' => $statuses['rejected'] ?? 0,
                    'catatan' => $rows->map(fn ($r) => $r['assignment']->note)->filter()->unique()->values(),
                ];
            })
            ->values();

        // Soal yang perlu diperbaiki: penugasan revisi/ditolak/bercatatan.
        $perluDiperbaiki = $assignments
            ->filter(fn ($row) => in_array($row['assignment']->status, ['revision', 'rejected'], true) || filled($row['assignment']->note))
            ->map(fn ($row) => [
                'nomor' => $row['soal']->nomor ?? $row['soal']->urutan,
                'indikator' => $row['soal']->soal?->tujuanPembelajaran?->kode_tp,
                'validator' => $validators[$row['assignment']->reviewer_id] ?? 'Validator',
                'status' => $row['assignment']->status,
                'status_label' => \App\Models\ReviewAssignment::STATUS_OPTIONS[$row['assignment']->status] ?? ucfirst((string) $row['assignment']->status),
                'catatan' => $row['assignment']->note,
                'decided_at' => $row['assignment']->decided_at,
            ])
            ->values();

        $rekomendasi = $assignments
            ->map(fn ($row) => $row['assignment']->note_text ?? $row['assignment']->note)
            ->filter()
            ->unique()
            ->values();

        return [
            'paket' => $paket,
            'items' => $items,
            'subject' => $subject,
            'gradeLevel' => $gradeLevel,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'jenisUjian' => $jenisUjian,
            'pembuat' => $pembuat,
            'perValidator' => $perValidator,
            'perluDiperbaiki' => $perluDiperbaiki,
            'rekomendasi' => $rekomendasi,
        ];
    }

    /**
     * Label level kognitif (nilai enum DB → label ramah).
     */
    protected function levelKognitifLabel(?string $level): string
    {
        return match ($level) {
            'C1_mengingat' => 'C1 — Mengingat',
            'C2_memahami' => 'C2 — Memahami',
            'C3_menerapkan' => 'C3 — Menerapkan',
            'C4_menganalisis' => 'C4 — Menganalisis',
            'C5_mengevaluasi' => 'C5 — Mengevaluasi',
            'C6_mencita', 'C6_mencipta' => 'C6 — Mencipta',
            default => '—',
        };
    }
}
