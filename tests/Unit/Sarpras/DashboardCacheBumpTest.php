<?php

namespace Tests\Unit\Sarpras;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for Sarpras dashboard cache invalidation.
 */
class DashboardCacheBumpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function invalidate_all_creates_version_key_when_missing()
    {
        Cache::forget('sarpras_dashboard_version');

        $invalidator = app(\App\Services\SarprasCacheInvalidator::class);
        $invalidator->invalidateAll();

        $this->assertTrue(Cache::has('sarpras_dashboard_version'));
        $this->assertEquals(2, Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function invalidate_all_increments_on_each_call()
    {
        $invalidator = app(\App\Services\SarprasCacheInvalidator::class);

        // First call: creates key with value 2
        $invalidator->invalidateAll();
        $this->assertEquals(2, Cache::get('sarpras_dashboard_version'));

        // Subsequent calls increment
        $invalidator->invalidateAll();
        $this->assertEquals(3, Cache::get('sarpras_dashboard_version'));

        $invalidator->invalidateAll();
        $this->assertEquals(4, Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function invalidate_does_not_affect_other_cache_keys()
    {
        Cache::put('other:key', 'preserved');

        app(\App\Services\SarprasCacheInvalidator::class)->invalidateAll();

        $this->assertEquals('preserved', Cache::get('other:key'));
        $this->assertNotNull(Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function controller_bump_delegates_to_invalidator()
    {
        Cache::put('sarpras_dashboard_version', 10);

        $controller = new \App\Http\Controllers\Sarpras\SarprasAsetController(
            app(\App\Services\Sarpras\AssetEventLogger::class)
        );
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('bumpDashboardCache');
        $method->invoke($controller);

        // bumpDashboardCache delegates to SarprasCacheInvalidator
        $this->assertEquals(11, Cache::get('sarpras_dashboard_version'));
    }
}
