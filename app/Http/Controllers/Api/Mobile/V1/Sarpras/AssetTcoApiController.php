<?php

namespace App\Http\Controllers\Api\Mobile\V1\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Sarpras\TcoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetTcoApiController extends Controller
{
    public function __construct(private readonly TcoService $tco) {}

    public function show(Request $request, string $assetId): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::findOrFail($assetId);
        $snapshot = $this->tco->snapshot($asset);
        $breakdown = $this->tco->breakdown($asset);

        return response()->json([
            'success' => true,
            'data' => [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'snapshot' => $snapshot,
                'breakdown' => $breakdown,
            ],
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $ids = (array) $request->input('asset_ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'asset_ids required'], 422);
        }

        $rows = [];
        foreach ($ids as $id) {
            $asset = Asset::find($id);
            if (! $asset) {
                continue;
            }
            $rows[] = [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->asset_name,
                'tco_total' => $this->tco->snapshot($asset)->tco_total,
                'tco_per_month' => $this->tco->snapshot($asset)->tco_per_month,
            ];
        }

        usort($rows, fn ($a, $b) => $b['tco_total'] <=> $a['tco_total']);

        return response()->json(['success' => true, 'data' => $rows]);
    }
}
