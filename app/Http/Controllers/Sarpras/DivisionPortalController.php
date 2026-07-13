<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Divisi;
use App\Models\WorkOrder;
use App\Models\MaintenanceRequest;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'dashboard' => [],
                'stat_widgets' => [],
                'recent_assets' => collect(),
                'pending_wo' => collect(),
                'open_repairs' => collect(),
                'sla_alerts' => collect(),
            ]);
        }

        // Assets via work_units belonging to these divisis
        // Since workunits link to divisis, and assets link to rooms/workunits...
        $assets = $this->getDivisionAssets($divisis);
        $stats = $this->calculateStats($assets);

        // Recent assets
        $recentAssets = $assets->with('room', 'category', 'room.studyGroup', 'location.workUnit')
            ->where('is_active', true)
            ->latest()
            ->take(10)
            ->get();

        // Pending work orders for division assets
        $woQuery = WorkOrder::whereIn('asset_id', $assets->pluck('id'));
        $pendingWOs = $woQuery->whereIn('status', ['assigned', 'accepted', 'in_progress'])
            ->with(['asset' => function ($q) {
                $q->select('id', 'asset_name', 'asset_code', 'category_id');
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
            if ($wo->sla_tracker) {
                if ($wo->sla_tracker->breached) {
                    $slaAlerts->push([
                        'type' => 'breached',
                        'title' => $wo->asset->asset_name ?? 'Unknown',
                        'message' => 'SLA sudah terlewat: ' . $wo->sla_tracker->breach_description,
                        'link' => route('sarpras.work-orders.show', $wo->id),
                    ]);
                } elseif ($wo->sla_tracker->is_imminent) {
                    $slaAlerts->push([
                        'type' => 'warning',
                        'title' => $wo->asset->asset_name ?? 'Unknown',
                        'message' => 'SLA mendekati batas: ' . $wo->sla_tracker->time_remaining_text,
                        'link' => route('sarpras.work-orders.show', $wo->id),
                    ]);
                }
            }
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
                $q->where('kode', $request->category);
            });
        }

        // Search
        if ($request->filled('search')) {
            $q = $request->search;
            $assets->where(function ($q2) use ($q) {
                $q2->where('asset_name', 'ilike', "%{$q}%")
                    ->orWhere('asset_code', 'ilike', "%{$q}%")
                    ->orWhere('merek', 'ilike', "%{$q}%");
            });
        }

        return view('sarpras.division.assets', [
            'assets' => $assets->paginate(20),
            'divisis' => $divisis,
        ]);
    }

    public function showAsset($assetId)
    {
        // Check ownership
        $asset = Asset::with(['category', 'room', 'pic'])->find($assetId);

        if (!$asset || !$this->userOwnsAsset($asset)) {
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
        return Asset::whereHas('room', function ($q) use ($divisiIds) {
            $q->whereHas('workUnit', function ($q2) use ($divisiIds) {
                $q2->whereIn('divisi_id', $divisiIds);
            });
        })->orWhere(function ($q) use ($divisiIds) {
            $q->whereHas('location', function ($q2) use ($divisiIds) {
                $q2->whereIn('divisi_id', $divisiIds);
            });
        })->orWhereIn('divisi_id', $divisiIds); // direct division on asset
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
        $total = $assets->count();
        return [
            'total_assets' => $total,
            'good' => $assets->where('condition', 'baik')->count(),
            'maintenance' => $assets->where('status', 'maintenance')->count(),
            'broken' => $assets->whereIn('status', ['rusak', 'maintenance'])->count(),
            'active_count' => $assets->where('is_active', true)->count(),
        ];
    }
}
