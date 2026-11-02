<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AssetBuilding;
use App\Models\AssetRoom;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\User;
use App\Services\Asrama\DormitoryRoomAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kamar asrama harus otomatis muncul sebagai ruang di Sarpras — perilaku yang
 * sama dengan rombel → ruang kelas pada satuan pendidikan.
 */
class DormitoryRoomSarprasSyncTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private DormitoryWing $wing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dormitory = Dormitory::factory()->create([
            'is_active' => true,
            'gender' => 'putri',
        ]);

        User::factory()->create(['is_active' => true]);

        $this->wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'code' => 'A',
            'name' => 'Blok A',
            'floor' => 2,
            'gender' => 'putri',
            'is_active' => true,
        ]);
    }

    private function makeRoom(array $overrides = []): DormitoryRoom
    {
        return DormitoryRoom::create(array_merge([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $this->wing->id,
            'code' => 'A-01',
            'name' => 'Kamar A-01',
            'floor' => 2,
            'gender' => 'putri',
            'capacity' => 20,
            'room_type' => 'reguler',
            'is_active' => true,
        ], $overrides));
    }

    public function test_kamar_baru_otomatis_membuat_ruang_sarpras(): void
    {
        $room = $this->makeRoom();

        $assetRoom = AssetRoom::where('dormitory_room_id', $room->id)->first();

        $this->assertNotNull($assetRoom, 'Ruang Sarpras harus dibuat otomatis.');
        $this->assertSame($this->dormitory->work_unit_id, $assetRoom->work_unit_id);
        $this->assertSame($this->dormitory->school_id, $assetRoom->school_id);
        $this->assertSame('Kamar A-01', $assetRoom->room_name);
        $this->assertSame(20, (int) $assetRoom->capacity);
        $this->assertSame('lainnya', $assetRoom->room_type);
        $this->assertNotNull($assetRoom->building_id);
    }

    public function test_perubahan_kamar_ikut_memperbarui_ruang_sarpras(): void
    {
        $room = $this->makeRoom();

        $room->update(['name' => 'Kamar A-01 Baru', 'capacity' => 24]);

        $assetRoom = AssetRoom::where('dormitory_room_id', $room->id)->firstOrFail();

        $this->assertSame('Kamar A-01 Baru', $assetRoom->room_name);
        $this->assertSame(24, (int) $assetRoom->capacity);
    }

    public function test_kamar_memakai_gedung_dari_blok_bila_tersedia(): void
    {
        $building = AssetBuilding::create([
            'work_unit_id' => $this->dormitory->work_unit_id,
            'school_id' => $this->dormitory->school_id,
            'building_code' => 'GD-A',
            'building_name' => 'Gedung A',
            'building_type' => 'asrama',
            'structure_condition' => 'baik',
            'ownership_status' => 'milik_sendiri',
            'is_active' => true,
            'created_by' => User::query()->value('id'),
        ]);

        $this->wing->update(['asset_building_id' => $building->id]);

        $room = $this->makeRoom(['code' => 'A-02']);

        $assetRoom = AssetRoom::where('dormitory_room_id', $room->id)->firstOrFail();

        $this->assertSame($building->id, $assetRoom->building_id);
    }

    public function test_menghapus_kamar_menghapus_ruang_sarpras(): void
    {
        $room = $this->makeRoom();

        $room->delete();

        $this->assertDatabaseMissing('asset_rooms', ['dormitory_room_id' => $room->id]);
    }

    public function test_backfill_membuat_ruang_untuk_kamar_lama(): void
    {
        $room = $this->makeRoom();

        // Simulasikan kamar lama yang belum punya ruang Sarpras.
        AssetRoom::where('dormitory_room_id', $room->id)->delete();

        $result = app(DormitoryRoomAssetService::class)->syncAll($this->dormitory->id);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('asset_rooms', ['dormitory_room_id' => $room->id]);
    }
}
