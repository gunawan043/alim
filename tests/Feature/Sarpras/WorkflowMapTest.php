<?php

namespace Tests\Feature\Sarpras;

use App\Models\CleaningArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreatesSarprasFixtures;

class WorkflowMapTest extends TestCase
{
    use CreatesSarprasFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Sarpras',
            'is_system_admin' => true,
        ]);
    }

    /** @test */
    public function admin_can_open_the_workflow_map()
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/alur")
            ->assertOk()
            ->assertSee('Prosedur Pendataan Aset')
            ->assertSee('Prosedur Pemeliharaan Sarpras')
            ->assertSee('Prosedur Penghapusan Aset')
            ->assertSee('Prosedur Pemeliharaan Taman');
    }

    /** @test */
    public function admin_can_open_the_assignment_hub()
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/alur/penugasan")
            ->assertOk()
            ->assertSee('Penugasan')
            ->assertSee('Area Kerja');
    }

    /** @test */
    public function unit_can_propose_disposal_and_it_enters_the_queue()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/disposal/{$asset->id}/propose", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Rusak berat dan tidak ekonomis diperbaiki',
            ])
            ->assertRedirect(route('sarpras.disposal.pending'));

        $asset->refresh();
        $this->assertNotNull($asset->disposal_date);
        $this->assertSame('scrap', $asset->disposal_method);
        $this->assertSame('baik', $asset->condition, 'Usulan tidak boleh langsung menghapus aset.');

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/disposal/pending")
            ->assertOk()
            ->assertSee($asset->asset_name);
    }

    /** @test */
    public function approved_disposal_leaves_the_queue()
    {
        $admin = $this->makeAdmin();
        $asset = $this->createAsset([
            'disposal_date' => now(),
            'disposal_method' => 'scrap',
            'disposal_reason' => 'Rusak berat',
        ]);

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/disposal/{$asset->id}/approve", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Disetujui',
            ])
            ->assertRedirect();

        $asset->refresh();
        $this->assertSame('dihapus', $asset->condition);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/disposal/pending")
            ->assertOk()
            ->assertDontSee($asset->asset_name);
    }

    /** @test */
    public function admin_can_add_area_task_and_assignment_from_the_hub()
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/kebersihan/area", [
                'name' => 'Koridor Gedung A',
                'area_type' => 'jalan',
                'location_notes' => 'Lantai 1',
            ])
            ->assertRedirect();

        $area = CleaningArea::where('name', 'Koridor Gedung A')->firstOrFail();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/kebersihan/area/{$area->id}/task", [
                'title' => 'Menyapu koridor',
                'standard' => 'Bersih dan tidak berdebu',
                'frequency' => 'harian',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post("/{$admin->id}/sarpras/kebersihan/area/{$area->id}/assignment", [
                'user_id' => $admin->id,
                'role' => 'petugas',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cleaning_tasks', ['area_id' => $area->id, 'title' => 'Menyapu koridor']);
        $this->assertDatabaseHas('cleaning_assignments', ['area_id' => $area->id, 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get("/{$admin->id}/sarpras/alur/penugasan")
            ->assertOk()
            ->assertSee('Koridor Gedung A')
            ->assertSee('Menyapu koridor')
            ->assertSee('Tambah Petugas')
            ->assertSee('Tambah Area')
            ->assertSee('Tambah Uraian')
            ->assertSee('Tugaskan');
    }

    /** @test */
    public function urt_role_can_open_the_workflow_map_and_sidebar_entry()
    {
        $user = $this->createUser('Unit Rumah Tangga');

        $this->actingAs($user)
            ->get("/{$user->id}/sarpras/alur")
            ->assertOk()
            ->assertSee('Alur Kerja URT');
    }

    /** @test */
    public function user_from_another_unit_cannot_propose_disposal()
    {
        $asset = $this->createAsset();
        $outsider = $this->createUser('');

        $this->actingAs($outsider)
            ->post("/{$outsider->id}/sarpras/disposal/{$asset->id}/propose", [
                'disposal_method' => 'scrap',
                'disposal_reason' => 'Coba hapus aset unit lain',
            ])
            ->assertForbidden();
    }
}
