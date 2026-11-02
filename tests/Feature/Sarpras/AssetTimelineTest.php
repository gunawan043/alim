<?php

namespace Tests\Feature\Sarpras;

use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class AssetTimelineTest extends TestCase
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
    public function halaman_timeline_dapat_dibuka(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/aset/{$asset->id}/timeline")
            ->assertOk()
            ->assertSee('Timeline Kegiatan')
            ->assertSee('Tambah Baris');
    }

    /** @test */
    public function baris_timeline_baru_tersimpan(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/aset/{$asset->id}/timeline", [
                'rows' => [
                    [
                        'performed_date' => '2026-10-01',
                        'maintenance_type' => 'corrective',
                        'work_description' => 'Ganti lampu TL 36W',
                        'performed_by_name' => 'Pak Andi',
                        'cost' => 150000,
                        'condition_before' => 'rusak_ringan',
                        'condition_after' => 'baik',
                        'next_due_date' => '2027-04-01',
                        'notes' => 'Stok lampu terbatas',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('maintenance_histories', [
            'asset_id' => $asset->id,
            'work_description' => 'Ganti lampu TL 36W',
            'notes' => 'Stok lampu terbatas',
        ]);
    }

    /** @test */
    public function baris_timeline_dapat_diperbarui_dan_dihapus(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $row = MaintenanceHistory::create([
            'asset_id' => $asset->id,
            'maintenance_type' => 'preventive',
            'performed_date' => '2026-09-01',
            'work_description' => 'Bersihkan filter AC',
            'cost' => 0,
        ]);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/aset/{$asset->id}/timeline", [
                'rows' => [
                    [
                        'id' => $row->id,
                        'performed_date' => '2026-09-02',
                        'maintenance_type' => 'preventive',
                        'work_description' => 'Bersihkan filter AC (revisi)',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('Bersihkan filter AC (revisi)', $row->fresh()->work_description);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/aset/{$asset->id}/timeline", [
                'rows' => [
                    ['id' => $row->id, 'performed_date' => '2026-09-02', 'maintenance_type' => 'preventive', 'delete' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('maintenance_histories', ['id' => $row->id]);
    }

    /** @test */
    public function tanggal_wajib_diisi(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/aset/{$asset->id}/timeline", [
                'rows' => [
                    ['maintenance_type' => 'corrective', 'work_description' => 'Tanpa tanggal'],
                ],
            ])
            ->assertSessionHasErrors('rows.0.performed_date');
    }

    /** @test */
    public function timeline_dapat_diekspor_csv(): void
    {
        $admin = $this->admin();
        $asset = $this->createAsset();

        MaintenanceHistory::create([
            'asset_id' => $asset->id,
            'maintenance_type' => 'corrective',
            'performed_date' => '2026-10-05',
            'work_description' => 'Perbaikan engsel pintu',
            'cost' => 50000,
        ]);

        $response = $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/aset/{$asset->id}/timeline/export");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
