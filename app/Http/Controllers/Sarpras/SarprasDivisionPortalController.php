<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\RepairRequest;
use App\Services\RepairRequestService;
use App\Services\Sarpras\DivisionPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Division Portal — used by division heads/managers to view their assigned assets,
 * report issues, and request maintenance/withdrawals.
 */
class SarprasDivisionPortalController extends SarprasBaseController
{
    public function __construct(
        protected DivisionPortalService $service,
        protected RepairRequestService $repairService,
    ) {}

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

    public function showAsset(Request $request, string $assetId)
    {
        $asset = Asset::with(['room', 'category', 'healthMetric'])->findOrFail($assetId);

        return view('sarpras.division.asset_detail', compact('asset'));
    }

    public function showReportForm(Request $request, string $assetId)
    {
        $asset = Asset::with(['room', 'category'])->findOrFail($assetId);

        return view('sarpras.division.report_form', compact('asset'));
    }

    public function reportIssue(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|uuid',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        $priorityMap = [
            'low' => 'low',
            'medium' => 'medium',
            'high' => 'high',
            'critical' => 'urgent',
        ];

        $repair = $this->repairService->create(
            Auth::id(),
            $data['asset_id'],
            [
                'title' => $data['title'],
                'description' => $data['description'],
                'priority' => $priorityMap[$data['severity']],
            ]
        );

        $this->service->notifyPicOfNewIssue($repair);

        if ($request->wantsJson() && ! $request->has('_redirect')) {
            return response()->json([
                'success' => true,
                'request_number' => $repair->request_number,
                'id' => $repair->id,
            ], 201);
        }

        return redirect()
            ->route('sarpras.divisi.history')
            ->with('success', "Laporan kerusakan {$repair->request_number} berhasil dikirim. Menunggu verifikasi PIC.");
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

        $userId = Auth::id();
        $myReports = RepairRequest::with(['asset', 'workOrders'])
            ->where('reported_by', $userId)
            ->latest()
            ->limit(20)
            ->get();

        return view('sarpras.division.history', [
            'history' => $history,
            'myReports' => $myReports,
        ]);
    }

    protected function resolveDivisionId(Request $request): string
    {
        return $request->user()->getAttribute('division_id')
            ?? $request->input('division_id')
            ?? 'default';
    }
}
