<?php

namespace Tests\Feature\Sarpras;

use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class DashboardAggregationTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function dashboard_returns_aggregation_data()
    {
        $admin = User::factory()->create(['name' => 'Admin Sarpras', 'is_system_admin' => true]);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras")
            ->assertOk()
            ->assertViewIs('sarpras.dashboard.index');
    }

    /** @test */
    public function dashboard_loads_in_under_500ms()
    {
        $admin = User::factory()->create(['name' => 'Admin Sarpras', 'is_system_admin' => true]);
        $this->createAsset();

        $start = microtime(true);
        $this->actingAs($admin)->get("/{$admin->id}/sarpras");
        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Dashboard should load in under 500ms');
    }

    /** @test */
    public function asset_update_invalidates_dashboard_version()
    {
        $admin = User::factory()->create(['name' => 'Admin Sarpras', 'is_system_admin' => true]);
        $asset = $this->createAsset();
        Cache::forever('sarpras_dashboard_version', 1);

        $asset->update(['condition' => 'rusak_ringan']);

        $this->assertGreaterThan(1, Cache::get('sarpras_dashboard_version'));
    }

    /** @test */
    public function non_admin_role_cannot_access_sarpras_dashboard()
    {
        $user = $this->createUser('Other');

        $this->actingAs($user)->get("/{$user->id}/sarpras")
            ->assertStatus(403);
    }
}
