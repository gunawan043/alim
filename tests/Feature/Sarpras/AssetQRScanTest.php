<?php

namespace Tests\Feature\Sarpras;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetQRScanTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    /** @test */
    public function technician_can_scan_valid_qr()
    {
        $user = User::factory()->create(['name' => 'Admin', 'is_system_admin' => true]);
        $asset = $this->createAsset();

        $response = $this->actingAs($user)
            ->postJson("/{$user->id}/sarpras/qr/lookup", ['code' => $asset->asset_code])
            ->assertOk();

        $this->assertTrue($response->json('success'));
        $this->assertEquals($asset->asset_code, $response->json('data.asset.asset_code'));
        $this->assertDatabaseHas('qr_scan_histories', [
            'asset_id' => $asset->id,
            'scanned_by' => $user->id,
        ]);
    }

    /** @test */
    public function invalid_qr_returns_error()
    {
        $user = User::factory()->create(['name' => 'Admin', 'is_system_admin' => true]);

        $response = $this->actingAs($user)
            ->postJson("/{$user->id}/sarpras/qr/lookup", ['code' => 'INVALID-CODE-9999'])
            ->assertNotFound();

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function empty_asset_code_fails_validation()
    {
        $user = User::factory()->create(['name' => 'Admin', 'is_system_admin' => true]);

        $this->actingAs($user)
            ->postJson("/{$user->id}/sarpras/qr/lookup", ['code' => ''])
            ->assertStatus(422);
    }
}
