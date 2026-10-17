<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\PaketSoal;
use App\Models\PaketSoalPrintJob;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Tata Usaha — menerima paket soal final langsung di sistem dan mengelola
 * proses operasional: jumlah cetak, pencetakan, perbanyakan, status produksi,
 * tanggal produksi, dan petugas.
 *
 * TU TIDAK dapat mengubah isi akademik soal/paket.
 */
class PaketSoalTuController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $request->attributes->get('schoolContextId');

        $pakets = PaketSoal::with([
            'kisiKisi.subject:id,name',
            'kisiKisi.gradeLevel:id,name',
            'kisiKisi.academicYear:id,name',
            'distributions',
            'printJobs',
        ])
            ->when($schoolId, fn ($q) => $q->whereHas('kisiKisi', fn ($k) => $k->where('school_id', $schoolId)))
            ->where('is_published', true)
            ->whereIn('workflow_status', [PaketSoal::WORKFLOW_APPROVED, PaketSoal::WORKFLOW_PUBLISHED])
            ->orderByDesc('distributed_at')
            ->get();

        $stats = [
            'total' => $pakets->count(),
            'didistribusikan' => $pakets->whereNotNull('distributed_at')->count(),
            'total_cetak' => (int) $pakets->sum(fn ($p) => $p->printJobs->sum('jumlah_cetak')),
            'selesai' => (int) $pakets->sum(fn ($p) => $p->printJobs->where('status', PaketSoalPrintJob::STATUS_SELESAI)->count()),
        ];

        return view('evalusi.paket-soal.tu', compact('userId', 'pakets', 'stats'));
    }

    public function storePrintJob(Request $request, string $userId, string $paketUuid)
    {
        $this->authorizeManage($request);

        $paket = PaketSoal::findOrFail($paketUuid);

        if (! $paket->isFinal()) {
            return back()->with('error', 'Paket belum final (approved + dipublikasikan).');
        }

        $validated = $request->validate([
            'jumlah_cetak' => 'required|integer|min:1|max:100000',
            'status' => 'nullable|in:antri,proses,selesai',
            'tanggal_produksi' => 'nullable|date',
            'petugas' => 'nullable|string|max:150',
            'catatan' => 'nullable|string|max:2000',
        ]);

        PaketSoalPrintJob::create([
            'paket_soal_id' => $paket->id,
            'jumlah_cetak' => $validated['jumlah_cetak'],
            'status' => $validated['status'] ?? PaketSoalPrintJob::STATUS_ANTRI,
            'tanggal_produksi' => $validated['tanggal_produksi'] ?? null,
            'petugas' => $validated['petugas'] ?? $request->user()?->name,
            'catatan' => $validated['catatan'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Perintah cetak dicatat. TU hanya mengelola produksi — isi soal tidak berubah.');
    }

    public function updatePrintJob(Request $request, string $userId, string $paketUuid, string $jobId)
    {
        $this->authorizeManage($request);

        $job = PaketSoalPrintJob::where('paket_soal_id', $paketUuid)->findOrFail($jobId);

        $validated = $request->validate([
            'jumlah_cetak' => 'required|integer|min:1|max:100000',
            'status' => 'required|in:antri,proses,selesai',
            'tanggal_produksi' => 'nullable|date',
            'petugas' => 'nullable|string|max:150',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $job->update($validated);

        return back()->with('success', 'Status produksi diperbarui.');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        if ($this->isTu($user) || $this->access->isKurikulumTeam($user)) {
            return;
        }

        abort(403, 'Halaman ini untuk Tata Usaha.');
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();

        if ($this->isTu($user) || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return;
        }

        abort(403, 'Hanya Tata Usaha yang dapat mengelola produksi cetak.');
    }

    private function isTu($user): bool
    {
        $jabatan = strtolower(trim((string) ($user->employment?->jabatan ?? '')));

        return str_contains($jabatan, 'tata usaha');
    }
}
