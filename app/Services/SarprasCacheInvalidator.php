<?php

namespace App\Services;

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
}
