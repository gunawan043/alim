<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetRecommendation;
use App\Services\Sarpras\RepairVsReplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasRepairVsReplaceController extends SarprasBaseController
{
    public function __construct(private readonly RepairVsReplaceService $engine) {}

    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);

        $query = AssetRecommendation::query()
            ->select('asset_recommendations.*')
            ->join('assets as a', 'a.id', '=', 'asset_recommendations.asset_id')
            ->where('a.school_id', $schoolId)
            ->with(['asset', 'asset.category', 'asset.room', 'asset.room.building']);

        if ($rec = $request->get('recommendation')) {
            $query->where('asset_recommendations.recommendation', strtoupper($rec));
        }
        if ($request->get('critical_only')) {
            $query->whereIn('asset_recommendations.recommendation', ['REPLACE', 'CRITICAL']);
        }

        $recommendations = $query->orderBy('asset_recommendations.score')
            ->paginate(20)
            ->withQueryString();

        return view('sarpras.repair-vs-replace.index', [
            'recommendations' => $recommendations,
            'filters' => $request->only(['recommendation', 'critical_only']),
            'summary' => $this->summary($schoolId),
        ]);
    }

    public function show(Request $request, string $assetId)
    {
        $schoolId = $this->resolveSchoolId($request);
        $asset = Asset::with(['category', 'room.building'])
            ->where('school_id', $schoolId)
            ->findOrFail($assetId);

        $evaluation = $this->engine->evaluate($asset);
        $existing = AssetRecommendation::where('asset_id', $assetId)->first();

        return view('sarpras.repair-vs-replace.show', [
            'asset' => $asset,
            'evaluation' => $evaluation,
            'recommendation' => $existing,
        ]);
    }

    public function evaluate(Request $request, string $assetId): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $asset = Asset::where('school_id', $schoolId)->findOrFail($assetId);
        $rec = $this->engine->persist($asset);

        return response()->json([
            'ok' => true,
            'recommendation' => $rec->recommendation,
            'score' => $rec->health_score,
            'rationale' => $rec->rationale,
        ]);
    }

    public function evaluateSchool(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $count = $this->engine->evaluateSchool($schoolId);

        return response()->json(['ok' => true, 'evaluated' => $count]);
    }

    protected function summary(int $schoolId): array
    {
        $rows = AssetRecommendation::query()
            ->select('asset_recommendations.recommendation', DB::raw('COUNT(*) as total'))
            ->join('assets as a', 'a.id', '=', 'asset_recommendations.asset_id')
            ->where('a.school_id', $schoolId)
            ->groupBy('asset_recommendations.recommendation')
            ->pluck('total', 'recommendation')
            ->toArray();

        return [
            'GOOD' => (int) ($rows['GOOD'] ?? 0),
            'MONITOR' => (int) ($rows['MONITOR'] ?? 0),
            'REPAIR' => (int) ($rows['REPAIR'] ?? 0),
            'REPLACE' => (int) ($rows['REPLACE'] ?? 0),
            'CRITICAL' => (int) ($rows['CRITICAL'] ?? 0),
        ];
    }

    protected function resolveSchoolId(Request $request): int
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            abort(403, 'School context required.');
        }

        return (int) $schoolId;
    }
}
