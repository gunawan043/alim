<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\RepairRequest;
use Illuminate\Support\Collection;

class PredictiveMaintenanceService
{
    public function predictForAsset(Asset $asset): array
    {
        $repairs = RepairRequest::where('asset_id', $asset->id)
            ->orderBy('created_at')
            ->get();

        $mtbf = $this->mtbf($repairs);
        $mttr = $this->mttr($repairs);
        $trend = $this->trend($repairs);
        $nextFailureAt = $mtbf > 0 && $repairs->count() > 0
            ? $repairs->last()->created_at->copy()->addDays((int) $mtbf)
            : null;

        return [
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'repair_count' => $repairs->count(),
            'mtbf_days' => $mtbf,
            'mttr_days' => $mttr,
            'trend' => $trend,
            'next_predicted_failure' => $nextFailureAt?->toDateString(),
            'days_until_failure' => $nextFailureAt ? (int) now()->diffInDays($nextFailureAt, false) : null,
            'recommendation' => $this->recommendation($asset, $mtbf, $trend, $repairs->count()),
            'risk_level' => $this->riskLevel($trend, $repairs->count(), $mtbf),
        ];
    }

    public function predictForSchool(int $schoolId): array
    {
        $assets = Asset::where('school_id', $schoolId)->get();
        $predictions = [];
        foreach ($assets as $asset) {
            $pred = $this->predictForAsset($asset);
            if ($pred['repair_count'] >= 1) {
                $predictions[] = $pred;
            }
        }

        return collect($predictions)
            ->sortByDesc(fn ($p) => $this->riskWeight($p['risk_level']))
            ->values()
            ->toArray();
    }

    public function highRiskAssets(int $schoolId, int $limit = 20): array
    {
        return collect($this->predictForSchool($schoolId))
            ->whereIn('risk_level', ['HIGH', 'CRITICAL'])
            ->take($limit)
            ->values()
            ->toArray();
    }

    protected function mtbf(Collection $repairs): float
    {
        if ($repairs->count() < 2) {
            return 0;
        }

        $intervals = [];
        $sorted = $repairs->sortBy('created_at')->values();
        for ($i = 1; $i < $sorted->count(); $i++) {
            $intervals[] = $sorted[$i]->created_at->diffInDays($sorted[$i - 1]->created_at);
        }

        return array_sum($intervals) / count($intervals);
    }

    protected function mttr(Collection $repairs): float
    {
        $resolved = $repairs->whereNotNull('resolved_at');
        if ($resolved->isEmpty()) {
            return 0;
        }

        $total = $resolved->sum(fn ($r) => $r->created_at->diffInDays($r->resolved_at));

        return $total / $resolved->count();
    }

    protected function trend(Collection $repairs): string
    {
        if ($repairs->count() < 2) {
            return 'insufficient_data';
        }

        $half = (int) ceil($repairs->count() / 2);
        $recent = $repairs->sortByDesc('created_at')->take($half);
        $recentInterval = $this->mtbf($recent);
        $older = $repairs->sortBy('created_at')->take($half);
        $olderInterval = $this->mtbf($older->reverse());

        if ($recentInterval <= 0 || $olderInterval <= 0) {
            return 'stable';
        }

        if ($recentInterval < $olderInterval * 0.7) {
            return 'deteriorating';
        }
        if ($recentInterval > $olderInterval * 1.3) {
            return 'improving';
        }

        return 'stable';
    }

    protected function recommendation(Asset $asset, float $mtbf, string $trend, int $repairCount): string
    {
        if ($repairCount === 0) {
            return 'Belum ada data perbaikan — jalankan inspeksi awal.';
        }

        if ($trend === 'deteriorating' && $mtbf > 0 && $mtbf < 60) {
            return sprintf(
                'PERINGATAN: %s makin sering rusak (MTBF ≈ %d hari). Pertimbangkan penggantian.',
                $asset->asset_name,
                (int) $mtbf
            );
        }

        if ($repairCount >= 5) {
            return sprintf(
                'Aset %s sudah diperbaiki %d kali. Evaluasi cost-benefit.',
                $asset->asset_name,
                $repairCount
            );
        }

        if ($trend === 'stable') {
            return sprintf('Kondisi stabil. Lanjutkan pemeliharaan rutin (MTBF %d hari).', (int) $mtbf);
        }

        return 'Pantau terus pada siklus berikutnya.';
    }

    protected function riskLevel(string $trend, int $count, float $mtbf): string
    {
        if ($trend === 'deteriorating' && $count >= 3) {
            return 'CRITICAL';
        }
        if ($trend === 'deteriorating' || $count >= 5) {
            return 'HIGH';
        }
        if ($count >= 2) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    protected function riskWeight(string $level): int
    {
        return match ($level) {
            'CRITICAL' => 4,
            'HIGH' => 3,
            'MEDIUM' => 2,
            default => 1,
        };
    }
}
