<?php

namespace App\Http\Controllers\Api\Mobile\V1\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Sarpras\PredictiveMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetPredictiveApiController extends Controller
{
    public function __construct(private readonly PredictiveMaintenanceService $predictive) {}

    public function show(Request $request, string $assetId): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::findOrFail($assetId);

        return response()->json([
            'success' => true,
            'data' => $this->predictive->predictForAsset($asset),
        ]);
    }

    public function highRisk(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $schoolId = $request->attributes->get('schoolContextId');

        return response()->json([
            'success' => true,
            'data' => $this->predictive->highRiskAssets((int) $schoolId, (int) $request->get('limit', 10)),
        ]);
    }
}
