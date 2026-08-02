<?php

namespace Tests\Feature\Sarpras;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetDisposalTest extends TestCase
{
    use CreatesSarprasFixtures;

    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$migrated = true;
        }
        Cache::flush();
    }

    /** @test */
    public function admin_can_view_disposal_queue()
    {
        $admin = $this->createUser('Admin Sarpras');
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $this->actingAs($admin)
            ->get('/sarpras/disposal/pending')
            ->assertOk()
            ->assertSee($asset->asset_name);
    }

    /** @test */
    public function admin_can_approve_disposal()
    {
        $admin = $this->createUser('Admin Sarpras');
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $response = $this->actingAs($admin)
            ->postJson("/sarpras/disposal/{$asset->id}/approve", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Sudah tidak layak pakai',
            ]);

        $asset->refresh();
        $this->assertEquals('scrap', $asset->disposal_method);
    }

    /** @test */
    public function admin_can_reject_disposal()
    {
        $admin = $this->createUser('Admin Sarpras');
        $asset = $this->createAsset(['condition' => 'dihapus']);

        $response = $this->actingAs($admin)
            ->postJson("/sarpras/disposal/{$asset->id}/reject", [
                'reason' => 'Aset masih bisa digunakan',
            ])
            ->assertOk();

        $this->assertNotEquals('dihapus', $asset->fresh()->condition);
    }

    /** @test */
    public function validation_fails_for_missing_method()
    {
        $admin = $this->createUser('Admin Sarpras');
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->postJson("/sarpras/disposal/{$asset->id}/approve", [
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
            ->postJson("/sarpras/disposal/{$asset->id}/approve", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Test reason',
            ])
            ->assertStatus(403);
    }
}
