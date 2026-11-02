<?php

namespace Tests\Feature\Sarpras;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetQrTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Sarpras',
            'is_system_admin' => true,
        ]);
    }

    /** @test */
    public function qr_dibuat_otomatis_saat_aset_dibuat(): void
    {
        $asset = $this->createAsset();

        $this->assertNotNull($asset->fresh()->qr_generated_at);
        $this->assertSame(Asset::QR_MODE_INDIVIDUAL, $asset->fresh()->qr_mode);
        $this->assertNull($asset->fresh()->qr_group_key);
    }

    /** @test */
    public function halaman_detail_aset_menampilkan_qr_dan_tautan_timeline(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/aset/{$asset->id}")
            ->assertOk()
            ->assertSee('QR Code Aset')
            ->assertSee($asset->qrScanUrl())
            ->assertSee('Timeline');
    }

    /** @test */
    public function gambar_qr_aset_tersedia_dalam_format_svg(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/qr/image/{$asset->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    /** @test */
    public function aset_sejenis_di_ruang_sama_berbagi_satu_qr_kelompok(): void
    {
        $admin = $this->admin();
        $school = $this->createSchool();
        $category = $this->createAssetCategory();

        $roomId = (string) Str::uuid();
        DB::table('asset_rooms')->insert([
            'id' => $roomId,
            'school_id' => $school->id,
            'work_unit_id' => $school->work_unit_id,
            'room_name' => 'Ruang Kelas 1',
            'room_code' => 'RK-1',
            'room_type' => 'kelas',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $common = [
            'work_unit_id' => $school->work_unit_id,
            'school_id' => $school->id,
            'room_id' => $roomId,
            'asset_category_id' => $category->id,
            'qr_mode' => Asset::QR_MODE_GROUP,
        ];

        $first = $this->createAsset(array_merge($common, ['asset_name' => 'Kursi Lipat A']));
        $second = $this->createAsset(array_merge($common, ['asset_name' => 'Kursi Lipat B']));

        $this->assertNotNull($first->qr_group_key);
        $this->assertSame($first->qr_group_key, $second->fresh()->qr_group_key);
        $this->assertStringContainsString('/scan/group/'.$first->qr_group_key, $first->qrScanUrl());

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/scan/group/{$first->qr_group_key}")
            ->assertOk()
            ->assertSee('Kursi Lipat A')
            ->assertSee('Kursi Lipat B');

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/aset/{$first->id}")
            ->assertOk()
            ->assertSee('QR seragam');
    }

    /** @test */
    public function mode_qr_dapat_diubah_dari_detail_aset(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/qr/mode/{$asset->id}", ['qr_mode' => Asset::QR_MODE_GROUP])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame(Asset::QR_MODE_GROUP, $asset->qr_mode);
        $this->assertNotNull($asset->qr_group_key);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/qr/mode/{$asset->id}", ['qr_mode' => Asset::QR_MODE_INDIVIDUAL])
            ->assertRedirect();

        $this->assertNull($asset->fresh()->qr_group_key);
    }
}
