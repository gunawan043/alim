<?php

namespace App\Http\Controllers\Sarpras;

use App\Events\Sarpras\AssetQrScanned;
use App\Http\Requests\Sarpras\BulkAuditSubmitRequest;
use App\Http\Requests\Sarpras\QrAuditSubmitRequest;
use App\Models\Asset;
use App\Models\AssetPhoto;
use App\Models\QrScanHistory;
use App\Services\Sarpras\AssetEventLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SarprasQRController extends SarprasBaseController
{
    public function __construct(public AssetEventLogger $eventLogger)
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    /**
     * Halaman utama QR Code & Audit
     */
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::where('is_active', true);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalAssets = (clone $query)->count();
        $withQR = (clone $query)->whereNotNull('qr_generated_at')->count();
        $withoutQR = $totalAssets - $withQR;

        return view('sarpras.qr.index', compact('totalAssets', 'withQR', 'withoutQR'));
    }

    /**
     * Generate QR untuk semua aset yang belum punya QR
     */
    public function generateAll(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::where('is_active', true)->whereNull('qr_generated_at');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $assets = (clone $query)->get();
        $updated = $query->update([
            'qr_generated_at' => Carbon::now(),
        ]);

        foreach ($assets as $asset) {
            try {
                $this->eventLogger->logQrGenerated($asset, auth()->id());
            } catch (\Throwable $e) {
                Log::error('AssetEventLogger::logQrGenerated failed for asset '.$asset->id.': '.$e->getMessage());
            }
        }

        return back()->with('success', "QR code berhasil di-generate untuk {$updated} aset.");
    }

    /**
     * Generate QR untuk aset tertentu
     */
    public function generateOne(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($asset, $request);

        $asset->update(['qr_generated_at' => Carbon::now()]);

        try {
            $this->eventLogger->logQrGenerated($asset, auth()->id());
        } catch (\Throwable $e) {
            Log::error('AssetEventLogger::logQrGenerated failed for asset '.$asset->id.': '.$e->getMessage());
        }

        return back()->with('success', 'QR code berhasil di-generate.');
    }

    /**
     * Print QR Label - generate PDF untuk print
     */
    public function printLabel(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::where('is_active', true)->whereNotNull('qr_generated_at');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $assets = $query->orderBy('asset_name')->limit(100)->get();

        if ($assets->isEmpty()) {
            return back()->with('error', 'Tidak ada aset dengan QR code untuk dicetak.');
        }

        // Generate QR codes inline using base64
        $qrData = [];
        foreach ($assets as $asset) {
            $url = url('/sarpras/aset/'.$asset->id);
            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(150)
                ->margin(1)
                ->generate($url);
            $qrData[$asset->id] = 'data:image/png;base64,'.base64_encode($qrImage);
        }

        return view('sarpras.qr.print', compact('assets', 'qrData'));
    }

    /**
     * Bulk generate + download QR labels as PDF
     */
    public function downloadPdf(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::where('is_active', true)->whereNotNull('qr_generated_at');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $assets = $query->orderBy('asset_name')->limit(200)->get();

        if ($assets->isEmpty()) {
            return back()->with('error', 'Tidak ada aset dengan QR code.');
        }

        // Generate QR codes
        $qrData = [];
        foreach ($assets as $asset) {
            $url = url('/sarpras/aset/'.$asset->id);
            $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(120)
                ->margin(1)
                ->generate($url);
            $qrData[$asset->id] = base64_encode($qrImage);
        }

        $pdf = \PDF::loadView('sarpras.qr.pdf', compact('assets', 'qrData'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('qr-labels-'.date('Ymd').'.pdf');
    }

    /**
     * QR Scanner - halaman scan
     */
    public function scanner(Request $request)
    {
        return view('sarpras.qr.scanner');
    }

    /**
     * Lookup aset via QR code (scanned data) — JSON API for scanner
     */
    public function lookup(Request $request)
    {
        $code = $request->get('code');

        // Support both URL format and asset code format
        if ($code && str_contains($code, '/sarpras/aset/')) {
            $parts = explode('/sarpras/aset/', $code);
            $id = end($parts);
        } else {
            $id = $code;
        }

        if (! $id) {
            return $this->fail('Kode QR tidak valid.', 422);
        }

        $asset = Asset::with(['room', 'room.school', 'category', 'photos'])->find($id);

        if (! $asset) {
            return $this->notFound('Aset tidak ditemukan.');
        }

        $payload = [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'brand' => $asset->brand,
            'model' => $asset->model,
            'room_name' => $asset->room?->room_name,
            'school_name' => $asset->room?->school?->name,
            'condition' => $asset->condition,
            'status' => $asset->status,
            'photo' => $asset->photos->first()?->photo_path
                ? asset('storage/'.$asset->photos->first()->photo_path)
                : null,
        ];

        // Record scan history and dispatch event (best-effort, never break the lookup)
        if ($request->user()) {
            try {
                $scan = QrScanHistory::create([
                    'asset_id' => $asset->id,
                    'scanned_by' => $request->user()->id,
                    'scanned_at' => now(),
                    'source' => $request->header('X-QR-Source', 'mobile-scanner'),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'raw_code' => (string) $code,
                ]);
                AssetQrScanned::dispatch($asset, $scan, $request->user());
            } catch (\Throwable $e) {
                Log::warning('Asset QR scan history recording failed', [
                    'asset_id' => $asset->id,
                    'scanner_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->ok(['asset' => $payload], 'Aset ditemukan.');
    }

    /**
     * Halaman lookup manual (non-scanner) — render view blade
     */
    public function lookupPage(Request $request)
    {
        return view('sarpras.qr.lookup');
    }

    /**
     * Audit aset via QR scan
     */
    public function audit(Request $request, string $id)
    {
        $asset = Asset::with(['room', 'room.school', 'category', 'photos', 'loans'])
            ->findOrFail($id);
        $this->authorizeAssetAccess($asset, $request);

        return view('sarpras.qr.audit', compact('asset'));
    }

    /**
     * Submit audit hasil scan
     */
    public function auditSubmit(QrAuditSubmitRequest $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $this->authorizeAssetAccess($asset, $request);

        $validated = $request->validated();

        $asset->update([
            'condition' => $validated['condition'],
            'last_condition_update' => Carbon::today(),
            'last_audit_by' => auth()->id(),
            'last_audit_date' => Carbon::today(),
        ]);

        // Upload foto audit
        if (! empty($validated['photos'])) {
            foreach ($validated['photos'] as $photo) {
                $path = $photo->store('assets/photos', 'public');
                AssetPhoto::create([
                    'asset_id' => $asset->id,
                    'photo_path' => $path,
                    'caption' => 'Audit - '.Carbon::now()->format('d/m/Y H:i'),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('sarpras.qr.scanner')
            ->with('success', 'Audit aset "'.$asset->asset_name.'" berhasil disimpan.');
    }

    /**
     * Halaman audit massal (mobile-friendly)
     */
    public function bulkAudit(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::where('is_active', true)
            ->whereNotNull('qr_generated_at')
            ->whereIn('condition', ['rusak_ringan', 'rusak_sedang', 'rusak_berat']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $assets = $query->orderBy('condition', 'desc')->paginate(20)->withQueryString();

        return view('sarpras.qr.bulk-audit', compact('assets'));
    }

    /**
     * Submit bulk audit
     */
    public function bulkAuditSubmit(BulkAuditSubmitRequest $request)
    {
        $validated = $request->validated();
        $count = 0;

        DB::transaction(function () use ($validated, &$count) {
            foreach ($validated['audits'] as $audit) {
                $asset = Asset::find($audit['asset_id']);
                if ($asset) {
                    $asset->update([
                        'condition' => $audit['condition'],
                        'last_condition_update' => Carbon::today(),
                        'last_audit_by' => auth()->id(),
                        'last_audit_date' => Carbon::today(),
                    ]);
                    $count++;
                }
            }
        });

        return redirect()->route('sarpras.qr.bulk-audit')
            ->with('success', "Bulk audit berhasil disimpan untuk {$count} aset.");
    }
}
