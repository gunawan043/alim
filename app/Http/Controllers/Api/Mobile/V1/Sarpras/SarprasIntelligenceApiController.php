<?php

namespace App\Http\Controllers\Api\Mobile\V1\Sarpras;

use App\Http\Controllers\Controller;
use App\Services\Sarpras\SarprasIntelligenceDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SarprasIntelligenceApiController extends Controller
{
    public function __construct(private readonly SarprasIntelligenceDashboard $dashboard) {}

    public function summary(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $schoolId = (int) $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            return response()->json(['success' => false, 'error' => 'school context required'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->build($schoolId, (int) $request->get('months', 6)),
        ]);
    }
}
