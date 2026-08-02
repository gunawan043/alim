<?php

namespace App\Http\Controllers\Sarpras;

use App\Services\Sarpras\PredictiveMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SarprasPredictiveMaintenanceController extends SarprasBaseController
{
    public function __construct(private readonly PredictiveMaintenanceService $service) {}

    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        $level = $request->get('level');
        $predictions = $this->service->predictForSchool($schoolId);

        if ($level) {
            $predictions = array_values(array_filter($predictions, fn ($p) => $p['risk_level'] === strtoupper($level)));
        }

        return view('sarpras.predictive.index', [
            'predictions' => $predictions,
            'filter' => $level,
        ]);
    }

    public function show(Request $request, string $assetId)
    {
        $schoolId = $this->resolveSchoolId($request);
        $asset = \App\Models\Asset::with(['category', 'room.building'])
            ->where('school_id', $schoolId)
            ->findOrFail($assetId);

        return view('sarpras.predictive.show', [
            'asset' => $asset,
            'prediction' => $this->service->predictForAsset($asset),
        ]);
    }

    public function highRisk(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);

        return response()->json([
            'ok' => true,
            'high_risk' => $this->service->highRiskAssets($schoolId, 25),
        ]);
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
