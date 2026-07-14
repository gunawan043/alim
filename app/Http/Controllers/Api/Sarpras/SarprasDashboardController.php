<?php

namespace App\Http\Controllers\Api\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Building;
use App\Models\RepairCostHistory;
use App\Models\RepairRequest;
use App\Models\SparePart;
use App\Models\StockOpnameSession;
use App\Models\WorkOrder;
use App\Models\WorkUnit;
use App\Services\SarprasCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SarprasDashboardController extends Controller
{
    public function __construct(
        protected SarprasCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * Enterprise Asset Operations Dashboard.
     */
    public function overview(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_all_access')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $user = $request->user();
        $period = $request->query('period', '30d');
        $version = (int) (Cache::get('sarpras_dashboard_version') ?? 1);
        $cacheKey = "sarpras:dashboard:overview:v{$version}:{$user->id}:{$period}";

        return response()->json(
            Cache::remember($cacheKey, 60, function () use ($period) {
                [$fromDate, $toDate] = $this->periodRange($period);

                return [
                    'success' => true,
                    'data' => [
                        'period' => [
                            'from' => $fromDate->toDateString(),
                            'to' => $toDate->toDateString(),
                            'label' => $period,
                        ],
                        'assets' => $this->assetOverview(),
                        'work_orders' => $this->workOrderOverview($fromDate, $toDate),
                        'repairs' => $this->repairOverview($fromDate, $toDate),
                        'costs' => $this->costOverview($fromDate, $toDate),
                        'opname' => $this->opnameOverview(),
                        'spareparts' => $this->sparePartOverview(),
                        'risk' => $this->riskOverview(),
                        'work_units' => $this->workUnitBreakdown(),
                        'buildings' => $this->buildingBreakdown(),
                    ],
                    'generated_at' => now()->toIso8601String(),
                ];
            })
        );
    }

    /* ===================================================================
     *  Sections
     * =================================================================== */

    protected function assetOverview(): array
    {
        $byStatus = Asset::groupBy('status')
            ->selectRaw('status, COUNT(*) as count')->pluck('count', 'status')->toArray();
        $byCondition = Asset::groupBy('condition')
            ->selectRaw('condition, COUNT(*) as count')->pluck('count', 'condition')->toArray();

        $totalValue = Asset::sum('current_value');
        $acquisitionValue = Asset::sum('acquisition_price');
        $depreciation = (float) $acquisitionValue - (float) $totalValue;

        return [
            'total' => Asset::count(),
            'by_status' => $byStatus,
            'by_condition' => $byCondition,
            'total_book_value' => (float) $totalValue,
            'total_acquisition_value' => (float) $acquisitionValue,
            'total_depreciation' => max(0, $depreciation),
            'utilization_rate' => Asset::where('status', 'in_use')->count() / max(Asset::count(), 1) * 100,
        ];
    }

    protected function workOrderOverview($fromDate, $toDate): array
    {
        $base = WorkOrder::query();
        $byStatus = $base->clone()->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')->pluck('count', 'status')->toArray();

        $completed = $base->clone()->where('status', 'closed')->whereBetween('created_at', [$fromDate, $toDate])->count();
        $open = $base->clone()->whereNotIn('status', ['closed', 'cancelled'])->count();
        $overdue = $base->clone()->where('scheduled_date', '<', now())
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])->count();

        return [
            'open' => $open,
            'completed' => $completed,
            'overdue' => $overdue,
            'by_status' => $byStatus,
            'avg_completion_days' => round((float) $base->clone()
                ->whereNotNull('actual_end')
                ->whereNotNull('actual_start')
                ->selectRaw('AVG(DATEDIFF(actual_end, actual_start)) as d')->value('d'), 2),
        ];
    }

    protected function repairOverview($fromDate, $toDate): array
    {
        $base = RepairRequest::query();
        $total = $base->clone()->whereBetween('created_at', [$fromDate, $toDate])->count();
        $byStatus = $base->clone()->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('status')->selectRaw('status, COUNT(*) as count')->pluck('count', 'status')->toArray();
        $byPriority = $base->clone()->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('priority')->selectRaw('priority, COUNT(*) as count')->pluck('count', 'priority')->toArray();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'avg_resolution_hours' => round($base->clone()
                ->whereNotNull('completed_at')->whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as h')->value('h') ?? 0, 2),
        ];
    }

    protected function costOverview($fromDate, $toDate): array
    {
        $base = RepairCostHistory::query();
        $costs = $base->clone()->whereBetween('incurred_date', [$fromDate, $toDate])->get();

        return [
            'total' => (float) $costs->sum('amount'),
            'count' => $costs->count(),
            'by_category' => $costs->groupBy('cost_category')->map(fn ($items, $cat) => [
                'category' => $cat,
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ])->values()->toArray(),
            'ytd_total' => (float) $base->clone()->whereYear('incurred_date', now()->year)->sum('amount'),
        ];
    }

    protected function opnameOverview(): array
    {
        $sessions = StockOpnameSession::query();
        $active = $sessions->clone()->whereIn('status', ['planned', 'in_progress'])->count();
        $closed = $sessions->clone()->where('status', 'closed')->count();

        $latestVariance = $sessions->clone()->where('status', 'closed')
            ->orderByDesc('closed_at')->first()?->variance_percent ?? null;

        return [
            'active_sessions' => $active,
            'closed_sessions' => $closed,
            'latest_variance_percent' => $latestVariance,
        ];
    }

    protected function sparePartOverview(): array
    {
        if (! class_exists(SparePart::class)) {
            return ['available' => 0];
        }

        $base = SparePart::query();

        return [
            'total' => $base->clone()->count(),
            'low_stock' => $base->clone()->whereRaw('stock <= min_stock')->count(),
            'total_value' => (float) $base->clone()->selectRaw('SUM(stock * unit_price) as v')->value('v'),
        ];
    }

    protected function riskOverview(): array
    {
        $assets = Asset::with(['costHistories'])->whereNotNull('current_value');

        $highRisk = 0;
        $totalAssets = 0;
        $riskBreakdown = [];

        foreach ($assets->limit(200)->get() as $asset) {
            $cost = (float) $asset->costHistories->sum('amount');
            $totalAssets++;
            if ($asset->costHistories->count() >= 3 || $cost >= 5_000_000) {
                $highRisk++;
            }
            $riskBreakdown[] = [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'risk_level' => $asset->costHistories->count() >= 5 ? 'high' : ($asset->costHistories->count() >= 3 ? 'medium' : 'low'),
            ];
        }

        return [
            'high_risk_count' => $highRisk,
            'high_risk_percent' => $totalAssets > 0 ? round($highRisk / $totalAssets * 100, 2) : 0,
            'breakdown' => array_slice($riskBreakdown, 0, 10),
        ];
    }

    protected function workUnitBreakdown(): array
    {
        if (! class_exists(WorkUnit::class)) {
            return [];
        }

        return WorkUnit::withCount('assets')
            ->orderByDesc('assets_count')->take(10)
            ->get(['id', 'name', 'unit_code'])
            ->map(fn ($w) => [
                'name' => $w->name,
                'asset_count' => $w->assets_count,
            ])->toArray();
    }

    protected function buildingBreakdown(): array
    {
        if (! class_exists(Building::class)) {
            return [];
        }

        return Building::withCount('assets')
            ->orderByDesc('assets_count')->take(10)
            ->get(['id', 'building_name'])
            ->map(fn ($b) => [
                'name' => $b->building_name,
                'asset_count' => $b->assets_count,
            ])->toArray();
    }

    /**
     * Real-time activity feed (last 50 events).
     */
    public function activityFeed(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $events = DB::table('asset_event_logs')
            ->leftJoin('assets', 'assets.id', '=', 'asset_event_logs.asset_id')
            ->leftJoin('users', 'users.id', '=', 'asset_event_logs.actor_id')
            ->orderByDesc('asset_event_logs.created_at')
            ->limit(50)
            ->get([
                'asset_event_logs.id',
                'asset_event_logs.event_type',
                'asset_event_logs.event_detail',
                'asset_event_logs.created_at',
                'assets.asset_code',
                'assets.asset_name',
                'users.name as actor',
            ]);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Heatmap of asset distribution by category.
     */
    public function categoryHeatmap(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $data = Asset::with('category:id,name')
            ->whereNotNull('category_id')
            ->get()
            ->groupBy('category.id')
            ->map(fn ($items, $catId) => [
                'category_id' => $catId,
                'category_name' => $items->first()?->category?->name ?? '-',
                'total' => $items->count(),
                'total_value' => (float) $items->sum('current_value'),
                'good_condition' => $items->where('condition', 'baik')->count(),
                'damaged' => $items->whereIn('condition', ['rusak_ringan', 'rusak_berat', 'total'])->count(),
            ])->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Top assets by repair cost (top-N leak report).
     */
    public function costLeakReport(Request $request): JsonResponse
    {
        if (! canPermission('sarpras_laporan_view')) {
            return response()->json(['success' => false, 'error' => 'forbidden'], 403);
        }

        $assets = Asset::with('costHistories', 'category:id,name')
            ->get()
            ->filter(fn ($a) => $a->costHistories->isNotEmpty())
            ->sortByDesc(fn ($a) => $a->costHistories->sum('amount'))
            ->take(20)
            ->map(fn ($a) => [
                'asset_id' => $a->id,
                'asset_code' => $a->asset_code,
                'asset_name' => $a->asset_name,
                'category' => $a->category?->name ?? '-',
                'acquisition_price' => (float) $a->acquisition_price,
                'total_repair_cost' => (float) $a->costHistories->sum('amount'),
                'cost_ratio' => $a->acquisition_price > 0
                    ? round($a->costHistories->sum('amount') / $a->acquisition_price * 100, 2)
                    : 0,
                'repair_count' => $a->costHistories->count(),
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $assets]);
    }

    /* ===================================================================
     *  Helpers
     * =================================================================== */

    protected function periodRange(string $period): array
    {
        return match ($period) {
            '7d' => [now()->subDays(7), now()],
            '30d' => [now()->subDays(30), now()],
            '90d' => [now()->subDays(90), now()],
            '1y' => [now()->subYear(), now()],
            default => [now()->subDays(30), now()],
        };
    }
}
