<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\RepairRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SarprasIntelligenceDashboard
{
    public function __construct(private readonly PredictiveMaintenanceService $predictive) {}

    public function build(string $schoolId, int $months = 6): array
    {
        return [
            'overview' => $this->overview($schoolId),
            'tco' => $this->tcoSnapshot($schoolId),
            'rvr' => $this->rvrDistribution($schoolId),
            'predictive' => $this->predictiveSummary($schoolId),
            'spend_trend' => $this->spendTrend($schoolId, $months),
            'category_breakdown' => $this->categoryBreakdown($schoolId),
            'high_risk_assets' => $this->predictive->highRiskAssets($schoolId, 5),
            'kpis' => $this->kpis($schoolId),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function overview(string $schoolId): array
    {
        $assets = Asset::where('school_id', $schoolId);

        return [
            'total_assets' => (clone $assets)->count(),
            'active_assets' => (clone $assets)->where('is_active', true)->where('status', '!=', 'dihapus')->count(),
            'in_repair' => (clone $assets)->where('status', 'dipinjamkan')->count(),
            'need_attention' => (clone $assets)->whereIn('condition', ['rusak_ringan', 'rusak_berat'])->count(),
        ];
    }

    public function kpis(string $schoolId): array
    {
        $open = RepairRequest::whereHas('asset', fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', [
                RepairRequest::STATUS_VERIFICATION_PENDING,
                RepairRequest::STATUS_APPROVAL_PENDING,
                RepairRequest::STATUS_EXECUTION_PENDING,
                RepairRequest::STATUS_STARTED,
            ])->count();
        $closed30 = RepairRequest::whereHas('asset', fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', [RepairRequest::STATUS_COMPLETED, RepairRequest::STATUS_CLOSED])
            ->where('closed_at', '>=', now()->subDays(30))->count();
        $spend30 = (float) DB::table('repair_cost_histories as r')
            ->join('assets as a', 'a.id', '=', 'r.asset_id')
            ->where('a.school_id', $schoolId)
            ->where('r.incurred_date', '>=', now()->subDays(30))
            ->sum('r.amount');

        return [
            'open_repairs' => $open,
            'closed_repairs_30d' => $closed30,
            'spend_30d' => $spend30,
            'avg_resolution_days' => $this->avgResolutionDays($schoolId),
        ];
    }

    public function tcoSnapshot(string $schoolId): array
    {
        $tco = DB::table('asset_cost_snapshots as s')
            ->join('assets as a', 'a.id', '=', 's.asset_id')
            ->where('a.school_id', $schoolId)
            ->selectRaw('COALESCE(SUM(s.purchase_cost), 0) as acquisition')
            ->selectRaw('COALESCE(SUM(s.maintenance_cost), 0) as maintenance')
            ->selectRaw('COALESCE(SUM(s.repair_cost), 0) as repair')
            ->selectRaw('COALESCE(SUM(s.sparepart_cost + s.operational_cost), 0) as downtime')
            ->selectRaw('COALESCE(SUM(s.total_cost), 0) as total')
            ->first();

        return [
            'acquisition' => (float) ($tco->acquisition ?? 0),
            'maintenance' => (float) ($tco->maintenance ?? 0),
            'repair' => (float) ($tco->repair ?? 0),
            'downtime' => (float) ($tco->downtime ?? 0),
            'total' => (float) ($tco->total ?? 0),
        ];
    }

    public function rvrDistribution(string $schoolId): array
    {
        $rows = DB::table('asset_recommendations as e')
            ->join('assets as a', 'a.id', '=', 'e.asset_id')
            ->where('a.school_id', $schoolId)
            ->select('e.recommendation', DB::raw('COUNT(*) as count'))
            ->groupBy('e.recommendation')
            ->get();

        $distribution = ['GOOD' => 0, 'MONITOR' => 0, 'REPAIR' => 0, 'REPLACE' => 0];
        foreach ($rows as $r) {
            $distribution[$r->recommendation] = (int) $r->count;
        }

        return $distribution;
    }

    public function predictiveSummary(string $schoolId): array
    {
        $predictions = $this->predictive->predictForSchool($schoolId);
        $byLevel = ['LOW' => 0, 'MEDIUM' => 0, 'HIGH' => 0, 'CRITICAL' => 0];
        foreach ($predictions as $p) {
            $byLevel[$p['risk_level']] = ($byLevel[$p['risk_level']] ?? 0) + 1;
        }

        return [
            'tracked_assets' => count($predictions),
            'risk_distribution' => $byLevel,
            'avg_mtbf_days' => $this->avg($predictions, 'mtbf_days'),
            'avg_mttr_days' => $this->avg($predictions, 'mttr_days'),
        ];
    }

    public function spendTrend(string $schoolId, int $months): array
    {
        $rows = DB::table('repair_cost_histories as r')
            ->join('assets as a', 'a.id', '=', 'r.asset_id')
            ->where('a.school_id', $schoolId)
            ->where('r.incurred_date', '>=', now()->subMonths($months))
            ->selectRaw("DATE_FORMAT(r.incurred_date, '%Y-%m') as month, COALESCE(SUM(r.amount),0) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $rows->map(fn ($r) => [
            'month' => $r->month,
            'total' => (float) $r->total,
        ])->toArray();
    }

    public function categoryBreakdown(string $schoolId): array
    {
        return DB::table('assets as a')
            ->leftJoin('asset_categories as c', 'c.id', '=', 'a.asset_category_id')
            ->where('a.school_id', $schoolId)
            ->groupBy('c.name')
            ->selectRaw('c.name as category, COUNT(*) as count')
            ->get()
            ->map(fn ($r) => ['category' => $r->category ?? 'Tanpa Kategori', 'count' => (int) $r->count])
            ->toArray();
    }

    protected function avgResolutionDays(string $schoolId): float
    {
        $resolved = RepairRequest::whereHas('asset', fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', now()->subDays(90))
            ->get(['created_at', 'closed_at']);

        if ($resolved->isEmpty()) {
            return 0;
        }

        $total = $resolved->sum(fn ($r) => Carbon::parse($r->created_at)->diffInDays($r->closed_at));

        return round($total / $resolved->count(), 1);
    }

    protected function avg(array $items, string $field): float
    {
        if (empty($items)) {
            return 0;
        }
        $total = array_sum(array_column($items, $field));

        return round($total / count($items), 1);
    }
}
