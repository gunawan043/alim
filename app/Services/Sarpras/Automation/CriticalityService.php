<?php

namespace App\Services\Sarpras\Automation;

use App\Models\Asset;
use App\Models\AssetCriticality;

class CriticalityService
{
    /**
     * Compute criticality for an asset. Returns the model.
     */
    public function recompute(Asset $asset): AssetCriticality
    {
        $score = 0;
        $factors = [];

        // Factor 1: location (gedung critical has higher weight)
        $location = strtolower($asset->building?->name ?? '');
        if (str_contains($location, 'server') || str_contains($location, 'listrik')) {
            $score += 25;
            $factors['location'] = 25;
        } elseif (str_contains($location, 'kelas') || str_contains($location, 'kantor')) {
            $score += 15;
            $factors['location'] = 15;
        } else {
            $score += 5;
            $factors['location'] = 5;
        }

        // Factor 2: function/role
        $function = strtolower($asset->function_label ?? $asset->asset_name ?? '');
        if (str_contains($function, 'server') || str_contains($function, 'pln')) {
            $score += 25;
            $factors['function'] = 25;
        } elseif (str_contains($function, 'pc') || str_contains($function, 'komputer') || str_contains($function, 'proyektor')) {
            $score += 15;
            $factors['function'] = 15;
        } else {
            $score += 5;
            $factors['function'] = 5;
        }

        // Factor 3: replacement cost (assume cost stored in procurement.price)
        try {
            $replacementCost = (float) ($asset->procurement?->price ?? 0);
        } catch (\Throwable $e) {
            $replacementCost = 0;
        }
        if ($replacementCost >= 25_000_000) {
            $score += 25;
            $factors['replacement_cost'] = 25;
        } elseif ($replacementCost >= 5_000_000) {
            $score += 15;
            $factors['replacement_cost'] = 15;
        } else {
            $score += 5;
            $factors['replacement_cost'] = 5;
        }

        // Factor 4: availability (only 1 unit => high impact)
        $siblings = Asset::where('asset_name', $asset->asset_name)
            ->where('id', '!=', $asset->id)
            ->count();
        $avail = $siblings === 0 ? 25 : ($siblings === 1 ? 15 : 5);
        $score += $avail;
        $factors['availability'] = $avail;

        $criticality = AssetCriticality::fromScore($score);

        return AssetCriticality::updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'criticality' => $criticality,
                'score' => $score,
                'factors' => $factors,
            ],
        );
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
}
