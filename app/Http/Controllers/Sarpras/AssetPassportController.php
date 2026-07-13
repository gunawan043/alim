<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Services\Sarpras\AssetPassportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssetPassportController extends SarprasBaseController
{
    public function __construct(public AssetPassportService $passportService)
    {
    }

    /**
     * Asset Passport — full lifecycle view for a single asset.
     * URL: /sarpras/assets/{uuid}/passport
     */
    public function show(Request $request, string $uuid)
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
    public function json(Request $request, string $uuid)
    {
        $asset = Asset::findOrFail($uuid);
        $this->authorizeAssetAccess($asset, $request);

        return $this->ok($this->passportService->buildFull($asset));
    }

    /**
     * Lookup by QR scan — dispatches event and returns redirect to passport or contextual page.
     */
    public function scan(Request $request, string $code)
    {
        $user = $request->user();

        try {
            $scanHistory = \App\Models\QrScanHistory::create([
                'asset_id' => null,
                'scanned_by' => $user?->id,
                'scanned_at' => now(),
                'source' => $request->header('X-QR-Source', 'web-scanner'),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'raw_code' => (string) $code,
            ]);
        } catch (\Throwable $e) {
            Log::warning('QR scan history recording failed', ['code' => $code, 'error' => $e->getMessage()]);
            $scanHistory = null;
        }

        // Resolve asset by code or UUID
        $asset = Asset::where('id', $code)
            ->orWhere('asset_code', $code)
            ->first();

        if (! $asset) {
            return view('sarpras.passport.scan_missing', ['code' => $code]);
        }

        if ($scanHistory) {
            $scanHistory->update(['asset_id' => $asset->id]);
        }

        // Dispatch the existing event for analytics/audit pipelines
        try {
            \App\Events\Sarpras\AssetQrScanned::dispatch($asset, $scanHistory, $user);
        } catch (\Throwable $e) {
            Log::warning('AssetQrScanned event dispatch failed', ['asset' => $asset->id, 'error' => $e->getMessage()]);
        }

        if (! $asset->is_active || $asset->status === 'dihapus') {
            return view('sarpras.passport.scan_disposed', ['asset' => $asset]);
        }

        if (! $asset->is_active) {
            return view('sarpras.passport.scan_inactive', ['asset' => $asset]);
        }

        return redirect()->route('sarpras.assets.passport', $asset->id);
    }
}