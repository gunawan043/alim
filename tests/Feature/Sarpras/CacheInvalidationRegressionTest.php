<?php

namespace Tests\Feature\Sarpras;

use App\Services\SarprasCacheInvalidator;
use App\Services\Sarpras\AssetEventLogger;
use App\Http\Controllers\Sarpras\SarprasAsetController;
use App\Http\Controllers\Sarpras\SarprasBookingController;
use App\Http\Controllers\Sarpras\SarprasGedungController;
use App\Http\Controllers\Sarpras\SarprasRuangController;
use App\Http\Controllers\Sarpras\SarprasLoanController;
use App\Http\Controllers\Sarpras\SarprasProcurementController;
use App\Http\Controllers\Sarpras\SarprasMovementController;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regression: every Sarpras mutation controller must delegate cache
 * invalidation to SarprasCacheInvalidator::invalidateAll() via the
 * shared bumpDashboardCache() helper.
 */
class CacheInvalidationRegressionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * @test
     * @dataProvider controllerProvider
     */
    public function controller_delegates_bump_to_centralized_invalidator(string $class): void
    {
        $controller = app($class);
        $this->assertContains(
            \App\Http\Controllers\Sarpras\SarprasBaseController::class,
            class_parents($controller) ?: []
        );

        // Bump the version to a known value
        Cache::put('sarpras_dashboard_version', 100);

        // Invoke bumpDashboardCache() via reflection
        $reflection = new \ReflectionMethod($controller, 'bumpDashboardCache');
        $reflection->invoke($controller);

        $this->assertEquals(101, Cache::get('sarpras_dashboard_version'));
    }

    public static function controllerProvider(): array
    {
        return [
            'Aset' => [SarprasAsetController::class],
            'Booking' => [SarprasBookingController::class],
            'Gedung' => [SarprasGedungController::class],
            'Ruang' => [SarprasRuangController::class],
            'Loan' => [SarprasLoanController::class],
            'Procurement' => [SarprasProcurementController::class],
            'Movement' => [SarprasMovementController::class],
        ];
    }

    /** @test */
    public function central_invalidator_is_registered_as_singleton()
    {
        $a = app(SarprasCacheInvalidator::class);
        $b = app(SarprasCacheInvalidator::class);
        $this->assertSame($a, $b);
    }

    /** @test */
    public function version_counter_starts_at_2_for_first_bump()
    {
        $invalidator = app(SarprasCacheInvalidator::class);
        $invalidator->invalidateAll();
        // First bump creates key with value 2
        $this->assertEquals(2, (int) Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function second_bump_increments_above_2()
    {
        $invalidator = app(SarprasCacheInvalidator::class);
        $invalidator->invalidateAll();  // -> 2
        $invalidator->invalidateAll();  // -> 3
        $this->assertEquals(3, (int) Cache::get('sarpras_dashboard_version'));
    }
}
