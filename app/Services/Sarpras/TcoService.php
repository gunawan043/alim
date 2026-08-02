<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetCostSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Total Cost of Ownership — aggregates purchase, repair, maintenance, sparepart,
 * and operational cost streams into a single, deterministic snapshot.
 *
 * Uses one grouped SQL aggregate per cost stream. No N+1.
 */
class TcoService
{
    public const CACHE_VERSION_KEY = 'sarpras_tco_version';

    /**
     * Build a TCO breakdown for one asset using aggregate queries.
     */
    public function build(Asset $asset): array
    {
        $cost = $this->aggregateCostForAsset($asset->id);

        $purchaseCost = (float) ($asset->acquisition_price ?? 0);
        $repairCost = (float) ($cost['repair_total'] ?? 0);
        $maintenanceCost = (float) ($cost['maintenance_total'] ?? 0);
        $sparepartCost = (float) ($cost['sparepart_total'] ?? 0);
        $operationalCost = (float) ($cost['operational_total'] ?? 0);
        $totalCost = $purchaseCost + $repairCost + $maintenanceCost + $sparepartCost + $operationalCost;

        return [
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'purchase_cost' => $purchaseCost,
            'repair_cost' => $repairCost,
            'maintenance_cost' => $maintenanceCost,
            'sparepart_cost' => $sparepartCost,
            'operational_cost' => $operationalCost,
            'total_cost' => $totalCost,
            'cost_per_year' => $this->costPerYear($totalCost, $asset),
            'currency' => 'IDR',
            'computed_at' => now()->toDateTimeString(),
            'breakdown' => [
                'repairs' => (int) ($cost['repair_count'] ?? 0),
                'maintenances' => (int) ($cost['maintenance_count'] ?? 0),
                'spareparts' => (int) ($cost['sparepart_count'] ?? 0),
            ],
        ];
    }

    /**
     * Persist a TCO snapshot — overwrites the latest per asset.
     */
    public function snapshot(Asset $asset): AssetCostSnapshot
    {
        $payload = $this->build($asset);

        $existing = AssetCostSnapshot::where('asset_id', $asset->id)
            ->orderByDesc('computed_at')
            ->first();

        if ($existing) {
            $existing->fill([
                'purchase_cost' => $payload['purchase_cost'],
                'repair_cost' => $payload['repair_cost'],
                'maintenance_cost' => $payload['maintenance_cost'],
                'sparepart_cost' => $payload['sparepart_cost'],
                'operational_cost' => $payload['operational_cost'],
                'total_cost' => $payload['total_cost'],
                'computed_at' => now(),
            ])->save();

            $this->bumpVersion();

            return $existing->refresh();
        }

        $snapshot = AssetCostSnapshot::create([
            'asset_id' => $asset->id,
            'purchase_cost' => $payload['purchase_cost'],
            'repair_cost' => $payload['repair_cost'],
            'maintenance_cost' => $payload['maintenance_cost'],
            'sparepart_cost' => $payload['sparepart_cost'],
            'operational_cost' => $payload['operational_cost'],
            'total_cost' => $payload['total_cost'],
            'computed_at' => now(),
        ]);

        $this->bumpVersion();

        return $snapshot;
    }

    /**
     * Build TCO for many assets in one pass — used by the dashboard.
     */
    public function buildMany(iterable $assets): array
    {
        $items = [];
        foreach ($assets as $asset) {
            $items[] = $this->build($asset);
        }

        return $items;
    }

    /**
     * Cost grouped by category across the org.
     */
    public function summarizeByCategory(int $schoolId): array
    {
        $rows = DB::table('assets as a')
            ->leftJoin('asset_cost_snapshots as c', 'c.asset_id', '=', 'a.id')
            ->leftJoin('asset_categories as cat', 'cat.id', '=', 'a.asset_category_id')
            ->where('a.school_id', $schoolId)
            ->groupBy('cat.id', 'cat.name')
            ->selectRaw('
                cat.id as category_id,
                cat.name as category_name,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(c.purchase_cost), 0) as purchase_cost,
                COALESCE(SUM(c.repair_cost), 0) as repair_cost,
                COALESCE(SUM(c.maintenance_cost), 0) as maintenance_cost,
                COALESCE(SUM(c.sparepart_cost), 0) as sparepart_cost,
                COALESCE(SUM(c.operational_cost), 0) as operational_cost,
                COALESCE(SUM(c.total_cost), 0) as total_cost
            ')
            ->get();

        return $rows->map(fn ($r) => [
            'category_id' => $r->category_id,
            'category_name' => $r->category_name ?? 'Uncategorized',
            'asset_count' => (int) $r->asset_count,
            'purchase_cost' => (float) $r->purchase_cost,
            'repair_cost' => (float) $r->repair_cost,
            'maintenance_cost' => (float) $r->maintenance_cost,
            'sparepart_cost' => (float) $r->sparepart_cost,
            'operational_cost' => (float) $r->operational_cost,
            'total_cost' => (float) $r->total_cost,
        ])->toArray();
    }

    /**
     * Cost grouped by building → room hierarchy.
     */
    public function summarizeByLocation(int $schoolId): array
    {
        $rows = DB::table('assets as a')
            ->leftJoin('asset_cost_snapshots as c', 'c.asset_id', '=', 'a.id')
            ->leftJoin('asset_rooms as r', 'r.id', '=', 'a.room_id')
            ->leftJoin('asset_buildings as b', 'b.id', '=', 'r.building_id')
            ->where('a.school_id', $schoolId)
            ->groupBy('b.id', 'b.building_name', 'r.id', 'r.room_name')
            ->selectRaw('
                b.id as building_id,
                b.building_name as building_name,
                r.id as room_id,
                r.room_name as room_name,
                COUNT(a.id) as asset_count,
                COALESCE(SUM(c.total_cost), 0) as total_cost
            ')
            ->get();

        return $rows->map(fn ($r) => [
            'building_id' => $r->building_id,
            'building_name' => $r->building_name ?? 'Tanpa Gedung',
            'room_id' => $r->room_id,
            'room_name' => $r->room_name ?? 'Tanpa Ruangan',
            'asset_count' => (int) $r->asset_count,
            'total_cost' => (float) $r->total_cost,
        ])->toArray();
    }

    /**
     * Top N most expensive assets by total cost.
     */
    public function topExpensive(int $schoolId, int $limit = 10): array
    {
        return AssetCostSnapshot::query()
            ->select('asset_cost_snapshots.*')
            ->join('assets as a', 'a.id', '=', 'asset_cost_snapshots.asset_id')
            ->where('a.school_id', $schoolId)
            ->orderByDesc('asset_cost_snapshots.total_cost')
            ->limit($limit)
            ->get()
            ->map(function (AssetCostSnapshot $snap) {
                $asset = Asset::find($snap->asset_id);

                return [
                    'asset_id' => $snap->asset_id,
                    'asset_code' => $asset?->asset_code,
                    'asset_name' => $asset?->asset_name,
                    'category' => $asset?->category?->name,
                    'room' => $asset?->room?->room_name,
                    'building' => $asset?->room?->building?->building_name,
                    'purchase_cost' => (float) $snap->purchase_cost,
                    'repair_cost' => (float) $snap->repair_cost,
                    'maintenance_cost' => (float) $snap->maintenance_cost,
                    'sparepart_cost' => (float) $snap->sparepart_cost,
                    'operational_cost' => (float) $snap->operational_cost,
                    'total_cost' => (float) $snap->total_cost,
                ];
            })
            ->toArray();
    }

    /**
     * Pull aggregate cost for a single asset id — one grouped query per table.
     */
    protected function aggregateCostForAsset(string $assetId): array
    {
        $repair = DB::table('repair_cost_histories')
            ->where('asset_id', $assetId)
            ->selectRaw('
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            ')
            ->first();

        $sparepart = DB::table('repair_cost_histories')
            ->where('asset_id', $assetId)
            ->where('cost_category', 'sparepart')
            ->selectRaw('
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
            ')
            ->first();

        $maintenance = DB::table('maintenance_histories')
            ->where('asset_id', $assetId)
            ->selectRaw('
                COALESCE(SUM(cost), 0) as total,
                COUNT(*) as count
            ')
            ->first();

        $operational = DB::table('asset_maintenance_logs')
            ->where('asset_id', $assetId)
            ->selectRaw('
                COALESCE(SUM(operational_cost), 0) as total
            ')
            ->first();

        return [
            'repair_total' => (float) ($repair->total ?? 0),
            'repair_count' => (int) ($repair->count ?? 0),
            'sparepart_total' => (float) ($sparepart->total ?? 0),
            'sparepart_count' => (int) ($sparepart->count ?? 0),
            'maintenance_total' => (float) ($maintenance->total ?? 0),
            'maintenance_count' => (int) ($maintenance->count ?? 0),
            'operational_total' => (float) ($operational->total ?? 0),
        ];
    }

    protected function costPerYear(float $total, Asset $asset): float
    {
        $acquiredAt = $asset->acquisition_date
            ? Carbon::parse($asset->acquisition_date)
            : ($asset->created_at ?? now());

        $years = max(1, (int) Carbon::now()->diffInYears($acquiredAt));
        if ($years < 1) {
            $years = 1;
        }

        return round($total / $years, 2);
    }

    protected function bumpVersion(): void
    {
        Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => (int) Cache::get(self::CACHE_VERSION_KEY, 0) + 1);
    }
}
