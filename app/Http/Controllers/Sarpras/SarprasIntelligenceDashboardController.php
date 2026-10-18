<?php

namespace App\Http\Controllers\Sarpras;

use App\Services\Sarpras\SarprasIntelligenceDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SarprasIntelligenceDashboardController extends SarprasBaseController
{
    public function __construct(private readonly SarprasIntelligenceDashboard $dashboard) {}

    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolContextId($request);
        $months = (int) $request->get('months', 6);

        $data = $this->dashboard->build($schoolId, $months);

        return view('sarpras.dashboard.intelligence', ['data' => $data, 'months' => $months]);
    }

    public function json(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolContextId($request);
        $data = $this->dashboard->build($schoolId, (int) $request->get('months', 6));

        return response()->json(['ok' => true, 'data' => $data]);
    }

}
