<?php

namespace Tests\Feature\Sarpras;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetDisposalTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** Admin sistem: lolos EnsureSarprasAccess sekaligus canPermission. */
    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Sarpras',
            'is_system_admin' => true,
        ]);
    }

    /** @test */
    public function admin_can_view_disposal_queue()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/disposal/pending")
            ->assertOk()
            ->assertSee($asset->asset_name);
    }

    /** @test */
    public function admin_can_approve_disposal()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $response = $this->actingAs($admin)
            ->postJson("/{$admin->id}/sarpras/disposal/{$asset->id}/approve", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Sudah tidak layak pakai',
            ]);

        $asset->refresh();
        $this->assertEquals('scrap', $asset->disposal_method);
    }

    /** @test */
    public function admin_can_reject_disposal()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $response = $this->actingAs($admin)
            ->postJson("/{$admin->id}/sarpras/disposal/{$asset->id}/reject", [
                'reason' => 'Aset masih bisa digunakan',
            ])
            ->assertOk();

        $asset->refresh();
        $this->assertNull($asset->disposal_method);
        $this->assertNotEquals('dihapus', $asset->condition);
    }

    /** @test */
    public function validation_fails_for_missing_method()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->postJson("/{$admin->id}/sarpras/disposal/{$asset->id}/approve", [
                'disposal_reason' => 'No method provided',
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function non_admin_cannot_approve_disposal()
    {
        $user = $this->createUser('Other');
        $asset = $this->createAsset();

        $this->actingAs($user)
            ->postJson("/{$user->id}/sarpras/disposal/{$asset->id}/approve", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Test reason',
            ])
            ->assertStatus(403);
    }
}
