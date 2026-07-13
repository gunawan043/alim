<?php

namespace Tests\Feature\Sarpras;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class DashboardAggregationTest extends TestCase
{
    use CreatesSarprasFixtures;

    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
        Cache::flush();
    }

    /** @test */
    public function dashboard_returns_aggregation_data()
    {
        $admin = $this->createUser('Admin Sarpras');

        $this->actingAs($admin)
            ->get('/sarpras')
            ->assertOk()
            ->assertViewIs('sarpras.dashboard.index');
    }

    /** @test */
    public function dashboard_loads_in_under_500ms()
    {
        $admin = $this->createUser('Admin Sarpras');
        $this->createAsset();

        $start = microtime(true);
        $this->actingAs($admin)->get('/sarpras');
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Dashboard should load in under 500ms');
    }

    /** @test */
    public function asset_update_invalidates_dashboard_version()
    {
        $admin = $this->createUser('Admin Sarpras');
        $asset = $this->createAsset();
        Cache::forever('sarpras_dashboard_version', 1);

        $asset->update(['condition' => 'rusak_ringan']);

        $this->assertGreaterThan(1, Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function non_admin_role_cannot_access_sarpras_dashboard()
    {
        $user = $this->createUser('Other');

        $this->actingAs($user)->get('/sarpras')
            ->assertStatus(403);
    }
}