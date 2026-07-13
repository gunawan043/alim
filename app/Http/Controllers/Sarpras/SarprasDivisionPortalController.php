<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\DivisionInventory;
use App\Services\Sarpras\DivisionPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Division Portal — used by division heads/managers to view their assigned assets,
 * report issues, and request maintenance/withdrawals.
 */
class SarprasDivisionPortalController extends SarprasBaseController
{
    public function __construct(protected DivisionPortalService $service) {}

    public function dashboard(Request $request)
    {
        $divisionId = $this->resolveDivisionId($request);
        $overview = $this->service->overview($divisionId);

        return view('sarpras.division.dashboard', compact('overview'));
    }

    public function assets(Request $request)
    {
        $divisionId = $this->resolveDivisionId($request);
        $assets = Asset::whereHas('divisionInventory', fn ($q) => $q->where('division_id', $divisionId))
            ->with(['room', 'healthMetric'])
            ->paginate(20);

        return view('sarpras.division.assets', compact('assets'));
    }

    public function reportIssue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|uuid',
            'severity' => 'required|in:low,medium,high,critical',
            'description' => 'required|string|max:2000',
        ]);

        $issue = $this->service->reportIssue(Auth::user(), $data);

        return response()->json(['success' => true, 'issue' => $issue], 201);
    }

    public function requestMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|uuid',
            'priority' => 'required|in:low,medium,high,urgent',
            'reason' => 'required|string',
        ]);

        $req = $this->service->requestMaintenance(Auth::user(), $data);

        return response()->json(['success' => true, 'request' => $req], 201);
    }

    public function history(Request $request)
    {
        $divisionId = $this->resolveDivisionId($request);
        $history = $this->service->history($divisionId);

        return view('sarpras.division.history', compact('history'));
    }

    protected function resolveDivisionId(Request $request): string
    {
        return $request->user()->getAttribute('division_id')
            ?? $request->input('division_id')
            ?? 'default';
    }
}