<?php

namespace App\Observers;

use App\Models\BoardingPolicy;
use Illuminate\Support\Facades\Cache;

/**
 * Bust rules-engine and quota-usage caches whenever a BoardingPolicy is
 * updated so that the next evaluate()/canLeave()/canVisit() call picks
 * up the new policy values.
 */
final class BoardingPolicyObserver
{
    public function updated(BoardingPolicy $policy): void
    {
        $this->bust();
    }

    /**
     * Flush keys belonging to the rules-engine and quota-usage caches.
     * Only effective when using a cache store that supports keys()
     * (currently Redis via predis/predis or illuminate/redis).
     *
     * @see \Illuminate\Cache\RedisTaggedCache::tags() for Redis-only tagged
     *      cache which would be even safer but requires explicit tag wiring
     *      in app/Providers/CacheServiceProvider.php — added later if
     *      needed.
     */
    private function bust(): void
    {
        $store = config('cache.store', 'redis');

        foreach (['rules_engine_*', 'usage_*', 'policy_*'] as $pattern) {
            try {
                $keys = Cache::store($store)->keys($pattern);
                if ($keys instanceof \Illuminate\Support\Collection) {
                    foreach ($keys as $key) {
                        Cache::store($store)->forget($key);
                    }
                }
            } catch (\Throwable $e) {
                // Local file / array stores do not support keys();
                // the policy-fingerprint in the cache key handles correctness.
            }
        }
    }
}
