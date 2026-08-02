<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Services\Sarpras\AssetPassportService;
use Illuminate\Http\Request;
use PDF;

class AssetPassportPdfController extends SarprasBaseController
{
    public function __construct(private readonly AssetPassportService $passportService) {}

    public function download(Request $request, string $uuid)
    {
        $asset = Asset::with([
            'room', 'room.building',
            'category', 'creator', 'workUnit',
            'photos', 'healthMetric',
        ])->findOrFail($uuid);

        $this->authorizeAssetAccess($asset, $request);

        $version = $request->get('version', '1');
        $passport = $version === '2'
            ? $this->passportService->buildPassportV2($asset)
            : $this->passportService->buildFull($asset);

        $pdf = PDF::loadView('sarpras.passport.pdf', [
            'asset' => $asset,
            'passport' => $passport,
            'version' => $version,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $filename = sprintf('asset-passport-%s-v%s.pdf', $asset->asset_code ?? $asset->id, $version);

        return $pdf->download($filename);
    }

    public function stream(Request $request, string $uuid)
    {
        $asset = Asset::with([
            'room', 'room.building',
            'category', 'creator', 'workUnit',
            'photos', 'healthMetric',
        ])->findOrFail($uuid);

        $this->authorizeAssetAccess($asset, $request);

        $passport = $this->passportService->buildPassportV2($asset);

        $pdf = PDF::loadView('sarpras.passport.pdf', [
            'asset' => $asset,
            'passport' => $passport,
            'version' => '2',
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->stream(sprintf('passport-%s.pdf', $asset->asset_code ?? $asset->id));
    }
}
