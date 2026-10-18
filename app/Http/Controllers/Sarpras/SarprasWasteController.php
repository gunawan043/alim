<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetBuilding;
use App\Models\GtkWorkUnit;
use App\Models\User;
use App\Models\WasteHandover;
use App\Models\WasteLog;
use App\Models\WasteSource;
use App\Models\WorkUnit;
use App\Services\Sarpras\PhotoProofService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SarprasWasteController extends SarprasBaseController
{
    public function __construct(protected PhotoProofService $photos) {}

    public function index(Request $request)
    {
        $this->authorizeWaste();

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $sources = WasteSource::with('building')->where('is_active', true)->orderBy('name')->get();
        $logsQuery = WasteLog::with(['source', 'recorder', 'verifier'])
            ->whereBetween('log_date', [$start, $end])
            ->when($request->filled('source_id'), fn ($q) => $q->where('source_id', $request->source_id))
            ->when($request->filled('waste_type'), fn ($q) => $q->where('waste_type', $request->waste_type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $stats = [
            'weight' => (clone $logsQuery)->sum('weight_kg'),
            'sacks' => (clone $logsQuery)->sum('sacks_count'),
            'logs' => (clone $logsQuery)->count(),
            'handovers' => WasteHandover::whereBetween('handover_date', [$start, $end])->sum('total_weight_kg'),
        ];

        $logs = $logsQuery->orderByDesc('log_date')->paginate(20)->withQueryString();
        $handovers = WasteHandover::with('officer')->whereBetween('handover_date', [$start, $end])->orderByDesc('handover_date')->get();
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::orderBy('building_name')->get(['id', 'building_name']);

        $typeSummary = WasteLog::selectRaw('waste_type, SUM(weight_kg) as weight, SUM(sacks_count) as sacks')
            ->whereBetween('log_date', [$start, $end])
            ->groupBy('waste_type')->get();

        return view('sarpras.waste.index', compact('sources', 'logs', 'handovers', 'stats', 'month', 'users', 'buildings', 'typeSummary'));
    }

    public function storeSource(Request $request)
    {
        $this->authorizeWaste();
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'source_type' => 'required|in:'.implode(',', WasteSource::TYPE_OPTIONS),
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $building = $validated['building_id'] ? AssetBuilding::find($validated['building_id']) : null;
        $urt = WorkUnit::where('code', 'DEP-URT')->orWhere('name', 'like', '%Rumah Tangga%')->value('id');

        WasteSource::create([...$validated, 'work_unit_id' => $urt, 'is_active' => true, 'created_by' => auth()->id()]);
        return back()->with('success', 'Sumber sampah ditambahkan.');
    }

    public function storeLog(Request $request)
    {
        $this->authorizeWaste();
        $validated = $request->validate([
            'source_id' => 'required|uuid|exists:waste_sources,id',
            'log_date' => 'required|date',
            'waste_type' => 'required|in:'.implode(',', WasteLog::TYPE_OPTIONS),
            'weight_kg' => 'required|numeric|min:0',
            'sacks_count' => 'required|integer|min:0',
            'volume_m3' => 'nullable|numeric|min:0',
            'sorting_status' => 'required|in:'.implode(',', WasteLog::SORTING_OPTIONS),
            'destination' => 'required|in:'.implode(',', WasteLog::DESTINATION_OPTIONS),
            'notes' => 'nullable|string|max:2000',
            'photo_url' => 'nullable|url|max:2000',
            'photo' => 'nullable|image|max:8192',
        ]);

        if (($validated['weight_kg'] <= 0 && $validated['sacks_count'] <= 0)) {
            return back()->withInput()->with('error', 'Isi berat (kg) atau jumlah karung minimal salah satu.');
        }

        if (empty($validated['photo_url']) && ! $request->hasFile('photo')) {
            return back()->withInput()->with('error', 'Foto bukti sampah wajib diisi (upload/kamera atau link Google Drive).');
        }

        $photo = $this->photos->store($request->file('photo'), $validated['photo_url'] ?? null, 'waste/logs');

        WasteLog::create([
            ...collect($validated)->except(['photo_url', 'photo'])->all(),
            'photo_path' => $photo['path'],
            'photo_url' => $photo['url'],
            'recorded_by' => auth()->id(),
            'status' => 'submitted',
        ]);

        return back()->with('success', 'Catatan sampah berhasil disimpan.');
    }

    public function verifyLog(string $userId, Request $request, string $logId)
    {
        $this->authorizeWaste();
        $log = WasteLog::findOrFail($logId);
        $validated = $request->validate([
            'decision' => 'required|in:verified,rejected',
            'coordinator_note' => 'nullable|string|max:1500',
        ]);

        $log->update([
            'status' => $validated['decision'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'coordinator_note' => $validated['coordinator_note'] ?? null,
        ]);

        return back()->with('success', 'Verifikasi catatan sampah disimpan.');
    }

    public function storeHandover(Request $request)
    {
        $this->authorizeWaste();
        $validated = $request->validate([
            'handover_date' => 'required|date',
            'destination' => 'required|string|max:191',
            'waste_type' => 'nullable|in:'.implode(',', WasteLog::TYPE_OPTIONS),
            'total_weight_kg' => 'required|numeric|min:0',
            'total_sacks' => 'required|integer|min:0',
            'receipt_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'receipt_url' => 'nullable|url|max:2000',
            'photo_url' => 'nullable|url|max:2000',
            'receipt' => 'nullable|image|max:8192',
            'photo' => 'nullable|image|max:8192',
        ]);

        if (empty($validated['photo_url']) && ! $request->hasFile('photo')) {
            return back()->withInput()->with('error', 'Foto penyerahan sampah wajib diisi.');
        }

        $photo = $this->photos->store($request->file('photo'), $validated['photo_url'] ?? null, 'waste/handovers');
        $receipt = $this->photos->store($request->file('receipt'), $validated['receipt_url'] ?? null, 'waste/receipts');

        WasteHandover::create([
            ...collect($validated)->except(['receipt_url', 'photo_url', 'receipt', 'photo'])->all(),
            'receipt_path' => $receipt['path'],
            'receipt_url' => $receipt['url'],
            'photo_path' => $photo['path'],
            'photo_url' => $photo['url'],
            'officer_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Penyerahan sampah ke DLH dicatat.');
    }

    public function report(Request $request)
    {
        $this->authorizeWaste();
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $logs = WasteLog::with(['source', 'recorder', 'verifier'])->whereBetween('log_date', [$start, $end])->orderBy('log_date')->get();
        $handovers = WasteHandover::with('officer')->whereBetween('handover_date', [$start, $end])->orderBy('handover_date')->get();
        $summary = WasteLog::selectRaw('waste_type, SUM(weight_kg) as weight, SUM(sacks_count) as sacks')
            ->whereBetween('log_date', [$start, $end])->groupBy('waste_type')->get();

        return view('sarpras.waste.report', compact('logs', 'handovers', 'summary', 'month'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorizeWaste();
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $logs = WasteLog::with(['source', 'recorder', 'verifier'])->whereBetween('log_date', [$start, $end])->orderBy('log_date')->get();
        $handovers = WasteHandover::with('officer')->whereBetween('handover_date', [$start, $end])->orderBy('handover_date')->get();
        $summary = WasteLog::selectRaw('waste_type, SUM(weight_kg) as weight, SUM(sacks_count) as sacks')
            ->whereBetween('log_date', [$start, $end])->groupBy('waste_type')->get();

        $pdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
        $pdf->loadHtml(view('sarpras.waste.pdf', compact('logs', 'handovers', 'summary', 'month'))->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-dlh-'.str_replace('-', '', $month).'.pdf"',
        ]);
    }

    private function authorizeWaste(): void
    {
        if ($this->isUrtLeadership()) {
            return;
        }

        $user = auth()->user();
        $jabatan = $user ? Str::upper(GtkWorkUnit::where('user_id', $user->id)->pluck('jabatan')->implode(' ')) : '';

        abort_unless(str_contains($jabatan, 'KEBERSIHAN') || str_contains($jabatan, 'LINGKUNGAN'), 403, 'Hanya URT/koordinator kebersihan yang dapat mengelola data sampah.');
    }
}
