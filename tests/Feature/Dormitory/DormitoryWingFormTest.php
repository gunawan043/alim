<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AssetBuilding;
use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DormitoryWingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Dormitory $dormitory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $this->dormitory = Dormitory::factory()->create([
            'is_active' => true,
        ]);
    }

    private function makeBuilding(string $type = 'asrama', string $name = 'Abu Bakar', string $code = 'GD-PA-01'): AssetBuilding
    {
        return AssetBuilding::create([
            'work_unit_id' => WorkUnit::factory()->create()->id,
            'school_id' => $this->dormitory->school_id,
            'building_code' => $code,
            'building_name' => $name,
            'building_type' => $type,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_asrama_tidak_punya_route_tambah_gedung(): void
    {
        $this->assertFalse(Route::has('user.asrama.wings.buildings.store'));
    }

    public function test_form_lantai_blok_menampilkan_gedung_dari_sarpras(): void
    {
        $this->makeBuilding('asrama', 'Abu Bakar As Siddiq', 'GD-PA-01');

        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Abu Bakar As Siddiq')
            ->assertDontSee('Tambah gedung baru');
    }

    public function test_form_tanpa_gedung_tetap_render_dengan_hint_urt(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Belum ada gedung asrama');
    }

    public function test_gedung_non_asrama_tidak_muncul_di_pilihan(): void
    {
        $this->makeBuilding('uks', 'Uks Putra', 'GD-PA-09');

        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertDontSee('Uks Putra');
    }

    public function test_lantai_blok_dibuat_dari_gedung_sarpras(): void
    {
        $building = $this->makeBuilding();

        $this->actingAs($this->admin)
            ->post(route('user.asrama.wings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'asset_building_id' => $building->id,
                'floor' => 2,
            ])
            ->assertRedirect();

        $wing = DormitoryWing::where('dormitory_id', $this->dormitory->id)->first();

        $this->assertNotNull($wing);
        $this->assertSame($building->id, $wing->asset_building_id);
        $this->assertSame('Abu Bakar — Lantai 2', $wing->name);
        $this->assertSame('GD-PA-01', $wing->code);
        $this->assertSame('Abu Bakar', $wing->building_name);
    }

    public function test_daftar_blok_menampilkan_nama_gedung_sarpras(): void
    {
        $building = $this->makeBuilding('asrama', 'Umar Bin Khattab', 'GD-PA-02');

        DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id,
            'asset_building_id' => $building->id,
            'code' => 'GD-PA-02',
            'name' => 'Umar Bin Khattab — Lantai 3',
            'floor' => 3,
            'gender' => 'putra',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.index', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Umar Bin Khattab')
            ->assertSee('Lantai 3');
    }

    public function test_gedung_wajib_dipilih(): void
    {
        $this->actingAs($this->admin)
            ->post(route('user.asrama.wings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'floor' => 1,
            ])
            ->assertSessionHasErrors('asset_building_id');
    }
}
