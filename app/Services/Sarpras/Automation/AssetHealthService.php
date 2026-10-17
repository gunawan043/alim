<?php

namespace App\Services\Sarpras\Automation;

use App\Models\Asset;
use App\Models\AssetHealthMetric;
use App\Models\MaintenanceHistory;
use App\Models\RepairCostHistory;
use App\Models\RepairRequest;
use Carbon\Carbon;

class AssetHealthService
{
    /**
     * Recompute health for one asset and persist.
     */
    public function recompute(Asset $asset): AssetHealthMetric
    {
        $repairs = RepairRequest::where('asset_id', $asset->id)->get();
        $costs = RepairCostHistory::where('asset_id', $asset->id)->sum('amount');
        $totalDowntime = $this->computeDowntime($asset);
        $maintenanceOverdue = MaintenanceHistory::where('asset_id', $asset->id)
            ->whereIn('status', ['due', 'overdue'])
            ->count();

        $auditFailures = 0;
        try {
            $auditFailures = \DB::table('asset_audits')
                ->where('asset_id', $asset->id)
                ->where('result', 'fail')
                ->count();
        } catch (\Throwable $e) {
            $auditFailures = 0;
        }

        $ageYears = $asset->procurement_date
            ? (int) Carbon::parse($asset->procurement_date)->diffInYears(now())
            : 1;

        $score = 100;
        $score -= min(30, $repairs->count() * 3);
        $score -= min(25, (int) ($costs / 200000));
        $score -= min(20, (int) ($totalDowntime / 1440));
        $score -= min(15, $maintenanceOverdue * 5);
        $score -= min(10, $auditFailures * 2);
        $score -= min(15, $ageYears * 1);

        $score = max(0, min(100, $score));

        $metric = AssetHealthMetric::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'health_score' => $score,
                'grade' => $this->gradeFromScore($score),
                'repair_count' => $repairs->count(),
                'lifetime_repair_cost' => $costs,
                'total_downtime_minutes' => $totalDowntime,
                'maintenance_overdue_count' => $maintenanceOverdue,
                'audit_failures_count' => $auditFailures,
                'age_years' => $ageYears,
                'last_computed_at' => now(),
            ],
        );

        return $metric;
    }

    public function computeDowntime(Asset $asset): int
    {
        try {
            $downtimes = \DB::table('asset_downtimes')
                ->where('asset_id', $asset->id)
                ->get(['started_at', 'ended_at']);
            $totalMinutes = 0;
            foreach ($downtimes as $row) {
                $started = Carbon::parse($row->started_at);
                $ended = $row->ended_at ? Carbon::parse($row->ended_at) : now();
                $totalMinutes += $started->diffInMinutes($ended);
            }

            return $totalMinutes;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Bulk recompute.
     */
    public function recomputeAll(?int $limit = 200): int
    {
        $count = 0;
        Asset::orderBy('id')->chunk($limit ?? 200, function ($assets) use (&$count) {
            foreach ($assets as $asset) {
                $this->recompute($asset);
                $count++;
            }
        });

        return $count;
    }

    public function gradeFromScore(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 50 => 'C',
            $score >= 25 => 'D',
            default => 'E',
        };
    }
}
