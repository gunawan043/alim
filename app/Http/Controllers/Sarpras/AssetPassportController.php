<?php

namespace App\Http\Controllers\Sarpras;

use App\Events\Sarpras\AssetQrScanned;
use App\Models\Asset;
use App\Models\QrScanHistory;
use App\Services\Sarpras\AssetPassportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetPassportController extends SarprasBaseController
{
    public function __construct(public AssetPassportService $passportService) {}

    /**
     * Asset Passport — full lifecycle view for a single asset.
     * URL: /sarpras/assets/{uuid}/passport
     */
    public function show(string $userId, Request $request, string $uuid)
    {
        $asset = Asset::with([
            'room', 'room.building', 'room.school',
            'category', 'creator', 'workUnit',
            'photos', 'healthMetric',
        ])->findOrFail($uuid);

        $this->authorizeAssetAccess($asset, $request);

        $passport = $this->passportService->buildFull($asset);

        return view('sarpras.passport.show', [
            'asset' => $asset,
            'passport' => $passport,
        ]);
    }

    /**
     * API endpoint for passport JSON (mobile / AJAX).
     */
    public function json(string $userId, Request $request, string $uuid)
    {
        $asset = Asset::findOrFail($uuid);
        $this->authorizeAssetAccess($asset, $request);

        return $this->ok($this->passportService->buildFull($asset));
    }

    /**
     * Passport 2.0 — adds TCO + Repair-vs-Replace + predictive insights.
     */
    public function showV2(string $userId, Request $request, string $uuid)
    {
        $asset = Asset::with([
            'room', 'room.building', 'room.school',
            'category', 'creator', 'workUnit',
            'photos', 'healthMetric',
        ])->findOrFail($uuid);
        $this->authorizeAssetAccess($asset, $request);

        $passport = $this->passportService->buildPassportV2($asset);

        return view('sarpras.passport.v2', [
            'asset' => $asset,
            'passport' => $passport,
        ]);
    }

    public function jsonV2(string $userId, Request $request, string $uuid)
    {
        $asset = Asset::findOrFail($uuid);
        $this->authorizeAssetAccess($asset, $request);

        return $this->ok($this->passportService->buildPassportV2($asset));
    }

    /**
     * Lookup by QR scan — dispatches event and returns redirect to passport or contextual page.
     */
    public function scan(string $userId, Request $request, string $code)
    {
        return $this->resolveScan($request, $code);
    }

    /**
     * QR seragam kelompok: satu label untuk banyak unit aset sejenis
     * (mis. kursi ruang kelas). Halaman ini menampilkan seluruh unit
     * dalam kelompok beserta kondisinya.
     */
    public function scanGroup(string $userId, Request $request, string $key)
    {
        $assets = Asset::with(['room', 'room.school', 'category'])
            ->where('qr_mode', Asset::QR_MODE_GROUP)
            ->where('qr_group_key', $key)
            ->where('is_active', true)
            ->orderBy('asset_name')
            ->get();

        if ($assets->isEmpty()) {
            return view('sarpras.passport.scan_missing', ['code' => $key]);
        }

        $this->recordScan($request, $key, $assets->first(), 'group');

        return view('sarpras.qr.group', [
            'groupKey' => $key,
            'assets' => $assets,
            'asset' => $assets->first(),
        ]);
    }

    private function resolveScan(Request $request, string $code)
    {
        $asset = Asset::where('id', $code)
            ->orWhere('asset_code', $code)
            ->first();

        $this->recordScan($request, $code, $asset);

        if (! $asset) {
            return view('sarpras.passport.scan_missing', ['code' => $code]);
        }

        if ($asset->status === 'dihapus') {
            return view('sarpras.passport.scan_disposed', ['asset' => $asset]);
        }

        if (! $asset->is_active) {
            return view('sarpras.passport.scan_inactive', ['asset' => $asset]);
        }

        return redirect()->route('sarpras.assets.passport', ['uuid' => $asset->id]);
    }

    /**
     * Catat riwayat scan + kirim event (best-effort, tidak boleh menggagalkan scan).
     */
    private function recordScan(Request $request, string $code, ?Asset $asset, string $scanType = 'qr'): void
    {
        $user = $request->user();

        try {
            $scanHistory = QrScanHistory::create([
                'asset_id' => $asset?->id,
                'scanned_by' => $user?->id,
                'scan_type' => $scanType,
                'lookup_value' => (string) $code,
                'source' => $request->header('X-QR-Source', 'web-scanner'),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            Log::warning('QR scan history recording failed', ['code' => $code, 'error' => $e->getMessage()]);

            return;
        }

        if (! $asset) {
            return;
        }

        try {
            AssetQrScanned::dispatch($asset, $scanHistory, $user);
        } catch (\Throwable $e) {
            Log::warning('AssetQrScanned event dispatch failed', ['asset' => $asset->id, 'error' => $e->getMessage()]);
        }
    }
}
