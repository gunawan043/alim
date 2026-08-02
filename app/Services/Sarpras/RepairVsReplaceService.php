<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetRecommendation;
use App\Models\MaintenanceHistory;
use App\Models\RepairCostHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Decide between REPAIR and REPLACE for an asset.
 *
 * Score 0–100 (higher = healthier). Thresholds:
 *   ≥ 80  GOOD
 *   ≥ 60  MONITOR
 *   ≥ 40  REPAIR
 *   ≥ 20  REPLACE
 *   <  20 CRITICAL
 *
 * Weighted factors:
 *   - Condition (35%) — categorical health of the asset right now
 *   - Repair frequency (20%) — repairs per year; more = worse
 *   - Repair cost ratio (20%) — historical repair / replacement value; > 50% = REPLACE
 *   - Age (15%) — relative to its expected useful life
 *   - Downtime (10%) — unavailable days / period
 */
class RepairVsReplaceService
{
    public const CONDITION_WEIGHT = 35;

    public const FREQUENCY_WEIGHT = 20;

    public const COST_RATIO_WEIGHT = 20;

    public const AGE_WEIGHT = 15;

    public const DOWNTIME_WEIGHT = 10;

    /** Replacement value = purchase price + 30% inflation factor if older than 2 years. */
    public const REPLACEMENT_INFLATION_FACTOR = 1.30;

    /** A repair cost ratio above this triggers REPLACE regardless of other factors. */
    public const REPLACE_COST_RATIO = 0.50;

    public function evaluate(Asset $asset): array
    {
        $factors = $this->gatherFactors($asset);
        $score = $this->scoreFactors($factors);
        $recommendation = $this->recommend($score, $factors);

        return [
            'asset_id' => $asset->id,
            'recommendation' => $recommendation,
            'score' => $score,
            'factors' => $factors,
            'evaluated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Evaluate and persist to asset_recommendations.
     */
    public function persist(Asset $asset): AssetRecommendation
    {
        $result = $this->evaluate($asset);
        $factors = $result['factors'];

        return AssetRecommendation::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'recommendation' => $result['recommendation'],
                'score' => $result['score'],
                'repair_cost_ratio' => $factors['cost_ratio'],
                'estimated_repair_cost' => $factors['historical_repair_cost'],
                'replacement_value' => $factors['replacement_value'],
                'age_years' => $factors['age_years'],
                'damage_count' => $factors['repair_count'],
                'downtime_minutes' => $factors['downtime_days'] * 1440,
                'availability_pct' => $factors['availability_pct'],
                'criticality' => $factors['criticality'],
                'health_score' => $result['score'],
                'factor_breakdown' => $factors,
                'rationale' => $this->buildRationale($result['recommendation'], $factors),
                'computed_at' => now(),
            ]
        );
    }

    /**
     * Bulk evaluate all active assets in a school — used by artisan command.
     */
    public function evaluateSchool(int $schoolId): int
    {
        $count = 0;
        Asset::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->chunkById(100, function ($assets) use (&$count) {
                foreach ($assets as $asset) {
                    $this->persist($asset);
                    $count++;
                }
            });

        return $count;
    }

    protected function gatherFactors(Asset $asset): array
    {
        $repairCount = RepairCostHistory::where('asset_id', $asset->id)->count();
        $historicalRepairCost = (float) RepairCostHistory::where('asset_id', $asset->id)->sum('amount');
        $maintenanceCount = MaintenanceHistory::where('asset_id', $asset->id)->count();
        $damageCount = (int) DB::table('asset_damage_reports')
            ->where('asset_id', $asset->id)
            ->count();

        $acquiredAt = $asset->acquisition_date
            ? Carbon::parse($asset->acquisition_date)
            : ($asset->created_at ?? now());

        $ageYears = max(0, (int) Carbon::now()->diffInYears($acquiredAt));
        $expectedLife = $this->expectedLifeYears($asset);
        $ageRatio = $expectedLife > 0 ? min(1.0, $ageYears / $expectedLife) : 0;

        $purchasePrice = (float) ($asset->acquisition_price ?? 0);
        $replacementValue = $ageYears >= 2
            ? $purchasePrice * self::REPLACEMENT_INFLATION_FACTOR
            : $purchasePrice;

        $costRatio = $replacementValue > 0
            ? round($historicalRepairCost / $replacementValue, 3)
            : 0;

        $repairsPerYear = $ageYears > 0 ? $repairCount / $ageYears : $repairCount;
        $maintenancePerYear = $ageYears > 0 ? $maintenanceCount / $ageYears : $maintenanceCount;

        $periodDays = max(1, $ageYears * 365);
        $downtimeDays = 0;
        if ($asset->last_used_at && $asset->status === 'tidak_tersedia') {
            $downtimeDays = (int) Carbon::parse($asset->last_used_at)->diffInDays(now());
        }
        $availabilityPct = max(0, min(100, (int) round((1 - $downtimeDays / $periodDays) * 100)));

        $criticality = $this->criticalityScore($asset);

        return [
            'condition' => $asset->condition ?? 'baik',
            'age_years' => $ageYears,
            'expected_life_years' => $expectedLife,
            'age_ratio' => round($ageRatio, 3),
            'repair_count' => $repairCount,
            'maintenance_count' => $maintenanceCount,
            'damage_count' => $damageCount,
            'repairs_per_year' => round($repairsPerYear, 2),
            'maintenance_per_year' => round($maintenancePerYear, 2),
            'historical_repair_cost' => $historicalRepairCost,
            'replacement_value' => $replacementValue,
            'cost_ratio' => $costRatio,
            'downtime_days' => $downtimeDays,
            'availability_pct' => $availabilityPct,
            'criticality' => $criticality,
        ];
    }

    protected function scoreFactors(array $f): int
    {
        $conditionScore = $this->scoreCondition($f['condition']);
        $frequencyScore = $this->scoreFrequency($f['repairs_per_year'], $f['maintenance_per_year']);
        $costRatioScore = $this->scoreCostRatio($f['cost_ratio']);
        $ageScore = $this->scoreAge($f['age_ratio']);
        $downtimeScore = $this->scoreDowntime($f['availability_pct']);

        $weighted = (
            ($conditionScore * self::CONDITION_WEIGHT)
            + ($frequencyScore * self::FREQUENCY_WEIGHT)
            + ($costRatioScore * self::COST_RATIO_WEIGHT)
            + ($ageScore * self::AGE_WEIGHT)
            + ($downtimeScore * self::DOWNTIME_WEIGHT)
        ) / 100;

        return (int) max(0, min(100, round($weighted)));
    }

    protected function scoreCondition(string $condition): int
    {
        return match ($condition) {
            'baik' => 100,
            'rusak_ringan' => 60,
            'rusak_berat' => 20,
            default => 50,
        };
    }

    protected function scoreFrequency(float $repairsPerYear, float $maintenancePerYear): int
    {
        $combined = $repairsPerYear + ($maintenancePerYear * 0.5);

        return match (true) {
            $combined <= 1 => 100,
            $combined <= 2 => 80,
            $combined <= 4 => 55,
            $combined <= 6 => 30,
            default => 10,
        };
    }

    protected function scoreCostRatio(float $ratio): int
    {
        return match (true) {
            $ratio <= 0.10 => 100,
            $ratio <= 0.25 => 75,
            $ratio <= 0.50 => 45,
            $ratio <= 0.75 => 20,
            default => 0,
        };
    }

    protected function scoreAge(float $ageRatio): int
    {
        return match (true) {
            $ageRatio <= 0.30 => 100,
            $ageRatio <= 0.50 => 85,
            $ageRatio <= 0.75 => 60,
            $ageRatio <= 0.90 => 35,
            default => 10,
        };
    }

    protected function scoreDowntime(int $availabilityPct): int
    {
        return max(0, min(100, $availabilityPct));
    }

    protected function recommend(int $score, array $f): string
    {
        // Hard override: if repair cost ratio is excessive, REPLACE immediately.
        if ($f['cost_ratio'] >= self::REPLACE_COST_RATIO && $f['historical_repair_cost'] > 0) {
            return 'REPLACE';
        }

        // Hard override: critical condition is always CRITICAL.
        if ($f['condition'] === 'rusak_berat' && $f['age_ratio'] >= 0.7) {
            return 'CRITICAL';
        }

        return match (true) {
            $score >= 80 => 'GOOD',
            $score >= 60 => 'MONITOR',
            $score >= 40 => 'REPAIR',
            $score >= 20 => 'REPLACE',
            default => 'CRITICAL',
        };
    }

    protected function expectedLifeYears(Asset $asset): int
    {
        $cat = $asset->category;
        if ($cat && isset($cat->expected_life_years)) {
            return max(1, (int) $cat->expected_life_years);
        }

        // Sensible defaults by category code prefix.
        $code = strtolower($cat->code ?? '');

        return match (true) {
            str_contains($code, 'elektron') => 7,
            str_contains($code, 'kendaraan') => 10,
            str_contains($code, 'bangunan') => 20,
            str_contains($code, 'mebel'), str_contains($code, 'furn') => 8,
            default => 8,
        };
    }

    protected function criticalityScore(Asset $asset): int
    {
        $score = 1;
        if ($asset->room_id) {
            $score += 1;
        }
        if ($asset->asset_category_id && $asset->category && stripos($asset->category->name ?? '', 'elektron') !== false) {
            $score += 1;
        }
        if ((float) ($asset->acquisition_price ?? 0) >= 5_000_000) {
            $score += 1;
        }

        return min(5, $score);
    }

    protected function buildRationale(string $recommendation, array $f): array
    {
        $reasons = [];
        $reasons[] = "Kondisi aset: {$f['condition']}.";
        $reasons[] = "Usia: {$f['age_years']} tahun dari estimasi masa pakai {$f['expected_life_years']} tahun.";
        $reasons[] = "{$f['repair_count']} perbaikan dengan total biaya Rp ".number_format($f['historical_repair_cost'], 0, ',', '.').'.';
        $reasons[] = 'Rasio biaya perbaikan vs harga penggantian: '.round($f['cost_ratio'] * 100, 1).'%.';
        $reasons[] = "Ketersediaan: {$f['availability_pct']}%.";

        switch ($recommendation) {
            case 'GOOD':
                $reasons[] = 'Aset dalam kondisi prima — lanjutkan pemeliharaan rutin.';
                break;
            case 'MONITOR':
                $reasons[] = 'Aset masih layak — perlu pemantauan lebih ketat.';
                break;
            case 'REPAIR':
                $reasons[] = 'Disarankan perbaikan terjadwal untuk mengembalikan performa.';
                break;
            case 'REPLACE':
                $reasons[] = 'Biaya perbaikan sudah mendekati harga pengganti — pertimbangkan置换.';
                break;
            case 'CRITICAL':
                $reasons[] = 'Aset dalam kondisi kritis — tindakan segera diperlukan.';
                break;
        }

        return $reasons;
    }
}
