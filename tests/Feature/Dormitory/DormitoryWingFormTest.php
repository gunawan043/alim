<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\Dormitory;
use App\Models\DormitoryWing;
use App\Models\SarprasBuilding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_gedung_baru_bisa_dibuat_dari_form_asrama(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('user.asrama.wings.buildings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'name' => 'Gedung Abu Bakar',
                'gender' => 'putra',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'code', 'gender']);

        $this->assertDatabaseHas('sarpras_buildings', [
            'name' => 'Gedung Abu Bakar',
            'gender' => 'putra',
            'school_id' => $this->dormitory->school_id,
            'is_active' => true,
        ]);
    }

    public function test_gedung_baru_kode_otomatis_jika_dikosongkan(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('user.asrama.wings.buildings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'name' => 'Gedung Umar',
            ]);

        $response->assertStatus(201);

        $building = SarprasBuilding::where('name', 'Gedung Umar')->first();

        $this->assertNotNull($building);
        $this->assertNotSame('', $building->code);
    }

    public function test_gedung_tanpa_nama_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('user.asrama.wings.buildings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_form_lantai_blok_menampilkan_gedung_yang_tersedia(): void
    {
        SarprasBuilding::create([
            'school_id' => $this->dormitory->school_id,
            'code' => 'GB',
            'name' => 'Gedung Abu Bakar',
            'gender' => 'putra',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Gedung Abu Bakar');
    }

    public function test_form_lantai_blok_tanpa_gedung_tetap_render_dengan_hints(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.asrama.wings.create', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Belum ada gedung');
    }

    public function test_lantai_blok_bisa_dibuat_setelah_gedung_dibuat(): void
    {
        $createBuilding = $this->actingAs($this->admin)
            ->postJson(route('user.asrama.wings.buildings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'name' => 'Gedung Abu Bakar',
                'gender' => 'putra',
            ]);

        $createBuilding->assertStatus(201);
        $buildingId = $createBuilding->json('id');

        $this->actingAs($this->admin)
            ->post(route('user.asrama.wings.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'sarpras_building_id' => $buildingId,
                'floor' => 1,
            ])
            ->assertRedirect();

        $wing = DormitoryWing::where('dormitory_id', $this->dormitory->id)->first();

        $this->assertNotNull($wing);
        $this->assertSame($buildingId, $wing->sarpras_building_id);
        $this->assertSame('Gedung Abu Bakar — Lantai 1', $wing->name);
    }
}
