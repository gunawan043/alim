<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryRoomStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $waliKamar;

    private Dormitory $dormitory;

    private DormitoryWing $wing;

    protected function setUp(): void
    {
        parent::setUp();

        AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $this->waliKamar = User::factory()->create(['is_active' => true]);

        $this->dormitory = Dormitory::factory()->create([
            'gender' => 'putri',
            'is_active' => true,
        ]);

        $this->wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id,
            'code' => 'A',
            'name' => 'Hajar — Lantai 3',
            'floor' => 3,
            'gender' => 'putri',
            'capacity' => 40,
            'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'wing_id' => $this->wing->id,
            'code' => 'A-101',
            'name' => 'Kamar A-101',
            'capacity' => 4,
            'room_type' => 'reguler',
            'wali_kamar_user_id' => $this->waliKamar->id,
            'is_active' => 1,
        ], $overrides);
    }

    public function test_lantai_kosong_mengikuti_lantai_blok(): void
    {
        $this->actingAs($this->admin)
            ->post(route('user.asrama.rooms.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->payload())
            ->assertRedirect();

        $room = DormitoryRoom::where('dormitory_id', $this->dormitory->id)->first();

        $this->assertNotNull($room, 'Kamar gagal dibuat.');
        $this->assertSame(3, (int) $room->floor);
        $this->assertSame('putri', $room->gender);
    }

    public function test_lantai_manual_tetap_dipakai(): void
    {
        $this->actingAs($this->admin)
            ->post(route('user.asrama.rooms.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->payload(['floor' => 5]))
            ->assertRedirect();

        $room = DormitoryRoom::where('dormitory_id', $this->dormitory->id)->first();

        $this->assertNotNull($room);
        $this->assertSame(5, (int) $room->floor);
    }

    public function test_tipe_kamar_khusus_dan_isolasi_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('user.asrama.rooms.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->dormitory->id,
            ]), $this->payload(['room_type' => 'khusus']))
            ->assertSessionHasErrors('room_type');

        $this->assertDatabaseMissing('dormitory_rooms', [
            'dormitory_id' => $this->dormitory->id,
            'code' => 'A-101',
        ]);
    }
}
