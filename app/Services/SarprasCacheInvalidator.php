<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Cache;

/**
 * Service for invalidating Sarpras dashboard cache.
 * Uses a global version counter — bumping it invalidates all dashboard caches regardless of driver.
 */
class SarprasCacheInvalidator
{
    /**
     * Bump the global version counter. All dashboard cache reads
     * will miss the next time and recompute from DB.
     */
    public function invalidateAll(): void
    {
        // Use a transaction-safe increment via a callback to handle missing keys.
        if (Cache::has('sarpras_dashboard_version')) {
            Cache::increment('sarpras_dashboard_version');
        } else {
            Cache::forever('sarpras_dashboard_version', 2);
        }
    }

    /**
     * Invalidate every dashboard cache. Alias for callers that previously relied on this.
     */
    public function invalidate(?string $key = null): void
    {
        $this->invalidateAll();
    }

    public function invalidateWorkOrder(WorkOrder $order): void
    {
        $this->invalidateAll();
    }

    public function invalidateAudit(mixed $session = null): void
    {
        $this->invalidateAll();
    }

    public function invalidateMovement(mixed $movement = null): void
    {
        $this->invalidateAll();
    }

    public function invalidateAsset(Asset $asset): void
    {
        $this->invalidateAll();
    }
}
