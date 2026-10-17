<?php

namespace App\Http\Controllers\Api\Mobile\V1\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetRepairVsReplaceApiController extends Controller
{
    public function __construct(private readonly RepairVsReplaceService $rvr) {}

    public function show(Request $request, string $assetId): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $asset = Asset::findOrFail($assetId);
        $evaluation = $this->rvr->evaluate($asset);

        return response()->json([
            'success' => true,
            'data' => [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'recommendation' => $evaluation['recommendation'] ?? null,
                'score' => $evaluation['score'] ?? null,
                'rationale' => $evaluation['rationale'] ?? [],
            ],
        ]);
    }

    public function listHighPriority(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_aset_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $limit = (int) $request->get('limit', 10);
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Asset::query();
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $assets = $query->whereHas('repairVsReplaceEvaluation', function ($q) {
            $q->where('is_current', true)->whereIn('recommendation', ['REPLACE', 'REPAIR']);
        })->with(['repairVsReplaceEvaluation' => fn ($q) => $q->where('is_current', true)])
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assets->map(fn ($a) => [
                'asset_id' => $a->id,
                'asset_code' => $a->asset_code,
                'name' => $a->asset_name,
                'recommendation' => $a->repairVsReplaceEvaluation->recommendation ?? null,
                'score' => $a->repairVsReplaceEvaluation->score ?? null,
            ])->values(),
        ]);
    }
}
