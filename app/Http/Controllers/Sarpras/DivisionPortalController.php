<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Divisi;
use App\Models\RepairRequest;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

/**
 * Division-level dashboard portal.
 * Shows assets managed BY the logged-in user's division only.
 */
class DivisionPortalController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Get divisis the user belongs to
        $divisis = $currentUser->divisiSubscriptions()->pluck('divisis.id')->toArray();

        if (empty($divisis)) {
            return view('sarpras.division.index', [
                'divisis' => [],
                'stats' => [
                    'total_assets' => 0,
                    'good' => 0,
                    'maintenance' => 0,
                    'broken' => 0,
                    'active_count' => 0,
                ],
                'recentAssets' => collect(),
                'pendingWOs' => collect(),
                'openRepairs' => collect(),
                'slaAlerts' => collect(),
            ]);
        }

        // Assets via work_units belonging to these divisis
        // Since workunits link to divisis, and assets link to rooms/workunits...
        $assets = $this->getDivisionAssets($divisis);
        $stats = $this->calculateStats($assets);

        // Recent assets
        $recentAssets = $assets->with('room.building', 'category')
            ->where('is_active', true)
            ->latest()
            ->take(10)
            ->get();

        // Pending work orders for division assets
        $woQuery = WorkOrder::whereIn('asset_id', $assets->pluck('id'));
        $pendingWOs = $woQuery->whereIn('status', ['assigned', 'accepted', 'in_progress'])
            ->with(['asset' => function ($q) {
                $q->select('id', 'asset_name', 'asset_code', 'asset_category_id');
            }, 'technician.profile'])
            ->orderBy('due_date')
            ->take(20)
            ->get();

        // Open repair requests
        $openRepairs = RepairRequest::whereIn('asset_id', $assets->pluck('id'))
            ->whereIn('status', ['pending', 'approved', 'in_progress'])
            ->with(['asset.category'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        // SLA breaches / alerts
        $slaAlerts = collect();
        foreach ($pendingWOs as $wo) {
        }

        return view('sarpras.division.index', compact(
            'divisis',
            'stats',
            'recentAssets',
            'pendingWOs',
            'openRepairs',
            'slaAlerts'
        ));
    }

    public function assets(Request $request)
    {
        $currentUser = $request->user();
        $divisis = $currentUser->divisiSubscriptions()->pluck('divisis.id')->toArray();

        $assets = $this->getDivisionAssets($divisis);

        // Filter by status
        if ($request->filled('status')) {
            $assets->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $assets->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Search
        if ($request->filled('search')) {
            $q = $request->search;
            $assets->where(function ($q2) use ($q) {
                $q2->where('asset_name', 'like', "%{$q}%")
                    ->orWhere('asset_code', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%");
            });
        }

        return view('sarpras.division.assets', [
            'assets' => $assets->paginate(20),
            'divisis' => $divisis,
        ]);
    }

    public function showAsset(string $userId, $assetId)
    {
        // Check ownership
        $asset = Asset::with(['category', 'room'])->find($assetId);

        if (! $asset || ! $this->userOwnsAsset($asset)) {
            abort(403, 'Aset tidak ada di division Anda');
        }

        return view('sarpras.division.asset_detail', compact('asset'));
    }

    /**
     * Get assets that belong to the given divisis.
     * Assumes room has work_unit, work_unit links to divisi.
     */
    private function getDivisionAssets(array $divisiIds)
    {
        return Asset::where(function ($q) use ($divisiIds) {
            $q->whereHas('workUnit', fn ($q2) => $q2->whereIn('divisi_id', $divisiIds))
                ->orWhereHas('room.workUnit', fn ($q2) => $q2->whereIn('divisi_id', $divisiIds));
        });
    }

    private function userOwnsAsset(Asset $asset): bool
    {
        $currentUser = auth()->user();
        $divisis = $currentUser->divisiSubscriptions()->pluck('divisis.id')->toArray();

        return $asset->room?->workUnit?->divisi_id
            ? in_array($asset->room->workUnit->divisi_id, $divisis)
            : false;
    }

    private function calculateStats($assets): array
    {
        return [
            'total_assets' => (clone $assets)->count(),
            'good' => (clone $assets)->where('condition', 'baik')->count(),
            'maintenance' => (clone $assets)->whereIn('condition', ['rusak_ringan', 'rusak_sedang'])->count(),
            'broken' => (clone $assets)->where('condition', 'rusak_berat')->count(),
            'active_count' => (clone $assets)->where('is_active', true)->count(),
        ];
    }
}
