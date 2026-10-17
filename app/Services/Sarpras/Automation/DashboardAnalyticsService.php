<?php

namespace App\Services\Sarpras\Automation;

use App\Models\Asset;
use App\Models\AssetHealthMetric;
use App\Models\MaintenanceHistory;
use App\Models\SlaTracker;
use App\Models\TechnicianAvailability;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * Build a unified automation-aware dashboard payload.
     */
    public function build(): array
    {
        return [
            'top_critical_assets' => $this->topCriticalAssets(),
            'sla_violations' => $this->slaViolations(),
            'technician_utilization' => $this->technicianUtilization(),
            'average_repair_time' => $this->averageRepairTime(),
            'asset_health_distribution' => $this->healthDistribution(),
            'maintenance_compliance' => $this->maintenanceCompliance(),
        ];
    }

    public function topCriticalAssets(int $limit = 10): array
    {
        $rows = Asset::query()
            ->select('assets.id', 'assets.asset_code', 'assets.asset_name', 'assets.condition')
            ->with(['category'])
            ->leftJoin('asset_criticalities as c', 'c.asset_id', '=', 'assets.id')
            ->leftJoin('asset_health_metrics as h', 'h.asset_id', '=', 'assets.id')
            ->orderByDesc('c.score')
            ->orderBy('h.health_score')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($r) => [
            'asset_id' => $r->id,
            'asset_code' => $r->asset_code,
            'asset_name' => $r->asset_name,
            'condition' => $r->condition,
            'category' => $r->category?->name,
            'criticality' => $r->criticalityScore?->criticality ?? 'medium',
            'health_score' => $r->healthScore?->health_score ?? null,
        ])->toArray();
    }

    public function slaViolations(): array
    {
        $overdue = SlaTracker::where('status', 'overdue')
            ->whereNull('completed_at')
            ->get();

        $escalated = SlaTracker::where('status', 'escalated')
            ->whereNull('completed_at')
            ->get();

        return [
            'overdue_count' => $overdue->count(),
            'escalated_count' => $escalated->count(),
            'oldest_overdue_minutes' => $overdue->max('overdue_minutes') ?? 0,
            'by_priority' => $overdue->groupBy('priority')->map->count()->toArray(),
        ];
    }

    public function technicianUtilization(): array
    {
        $rows = TechnicianAvailability::query()
            ->with('user:id,name')
            ->orderByDesc('current_active_orders')
            ->limit(10)
            ->get();

        return $rows->map(fn ($a) => [
            'user_id' => $a->user_id,
            'name' => $a->user?->name,
            'status' => $a->status,
            'workload_ratio' => $a->workloadRatio(),
            'active_orders' => $a->current_active_orders,
            'max_orders' => $a->max_concurrent_orders,
        ])->toArray();
    }

    public function averageRepairTime(): array
    {
        $row = DB::table('work_orders')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_minutes,
                COUNT(*) as sample_size
            ')
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('created_at', '>=', now()->subDays(90))
            ->first();

        return [
            'avg_minutes' => (int) ($row->avg_minutes ?? 0),
            'sample_size' => (int) ($row->sample_size ?? 0),
            'window_days' => 90,
        ];
    }

    public function healthDistribution(): array
    {
        return AssetHealthMetric::query()
            ->select('grade', DB::raw('COUNT(*) as total'))
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('total', 'grade')
            ->toArray();
    }

    public function maintenanceCompliance(): array
    {
        $total = MaintenanceHistory::query()->count();
        $completed = MaintenanceHistory::query()->where('status', 'completed')->count();
        $overdue = MaintenanceHistory::query()->whereIn('status', ['due', 'overdue'])->count();

        $ratio = $total === 0 ? 100.0 : round(($completed / $total) * 100, 1);

        return [
            'total' => $total,
            'completed' => $completed,
            'overdue' => $overdue,
            'compliance_pct' => $ratio,
        ];
    }
}
