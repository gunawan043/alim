<?php

namespace Tests\Feature\Sarpras;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetQRScanTest extends TestCase
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
    }

    /** @test */
    public function technician_can_scan_valid_qr()
    {
        $user = $this->createUser('Admin');
        $asset = $this->createAsset();

        $response = $this->actingAs($user)
            ->postJson('/sarpras/scan/process', ['asset_code' => $asset->asset_code])
            ->assertOk();

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function invalid_qr_returns_error()
    {
        $user = $this->createUser('Admin');

        $response = $this->actingAs($user)
            ->postJson('/sarpras/scan/process', ['asset_code' => 'INVALID-CODE-9999'])
            ->assertOk();

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function empty_asset_code_fails_validation()
    {
        $user = $this->createUser('Admin');

        $this->actingAs($user)
            ->postJson('/sarpras/scan/process', ['asset_code' => ''])
            ->assertStatus(422);
    }
}
