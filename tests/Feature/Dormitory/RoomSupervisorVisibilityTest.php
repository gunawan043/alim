<?php

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\RoomSupervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi: kolom tanggal bertipe date tetapi Eloquent menyimpannya sebagai
 * "Y-m-d H:i:s". Di SQLite perbandingan string membuat
 * '2026-11-02 00:00:00' <= '2026-11-02' bernilai SALAH, sehingga wali kamar
 * aktif tidak terbaca (hanya terjadi di produksi yang memakai SQLite).
 */
class RoomSupervisorVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(User $admin): array
    {
        $dormitory = Dormitory::factory()->create([
            'is_active' => true,
            'head_id' => $admin->id,
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $dormitory->id,
            'code' => 'A',
            'name' => 'Blok A',
            'floor' => 1,
            'gender' => 'putri',
            'capacity' => 40,
            'total_rooms' => 2,
            'is_active' => true,
        ]);

        $room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'A-01',
            'name' => 'Kamar A-01',
            'floor' => 1,
            'gender' => 'putri',
            'capacity' => 20,
            'room_type' => 'reguler',
            'is_active' => true,
        ]);

        return [$dormitory, $room];
    }

    private function makeSupervisor(Dormitory $dormitory, DormitoryRoom $room, User $user): RoomSupervisor
    {
        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);

        return RoomSupervisor::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'dormitory_id' => $dormitory->id,
            'academic_year_id' => $academicYear->id,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'status' => 'active',
        ]);
    }

    public function test_wali_kamar_aktif_terbaca_walau_tanggal_mulai_disimpan_sebagai_datetime(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'is_system_admin' => true]);
        [$dormitory, $room] = $this->makeRoom($admin);
        $supervisorUser = User::factory()->create(['name' => 'Anisa Aulia Putri', 'is_active' => true]);

        $this->makeSupervisor($dormitory, $room, $supervisorUser);

        // Pastikan nilainya benar-benar tersimpan dengan komponen jam.
        $rawStart = \DB::table('room_supervisors')->value('start_date');
        $this->assertStringContainsString(' 00:00:00', (string) $rawStart);

        $this->assertNotNull($room->fresh()->activeSupervisor);
        $this->assertSame(1, RoomSupervisor::query()->active()->count());
    }

    public function test_halaman_detail_kamar_menampilkan_wali_kamar_dan_tombol_ubah(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'is_system_admin' => true]);
        [$dormitory, $room] = $this->makeRoom($admin);
        $supervisorUser = User::factory()->create(['name' => 'Anisa Aulia Putri', 'is_active' => true]);

        $this->makeSupervisor($dormitory, $room, $supervisorUser);

        $this->actingAs($admin)
            ->get(route('user.asrama.rooms.show', [
                'userId' => $admin->id,
                'asramaUuid' => $dormitory->id,
                'roomUuid' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('Anisa Aulia Putri')
            ->assertSee('Ubah Wali Kamar')
            ->assertDontSee('Kamar ini belum memiliki Wali Kamar');
    }

    public function test_halaman_detail_kamar_menawarkan_penetapan_bila_belum_ada_wali(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'is_system_admin' => true]);
        [$dormitory, $room] = $this->makeRoom($admin);

        $this->actingAs($admin)
            ->get(route('user.asrama.rooms.show', [
                'userId' => $admin->id,
                'asramaUuid' => $dormitory->id,
                'roomUuid' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('Tetapkan Wali Kamar');
    }

    public function test_form_tetapkan_wali_kamar_memilih_kamar_yang_sedang_dibuka(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'is_system_admin' => true]);
        [$dormitory, $room] = $this->makeRoom($admin);

        $this->actingAs($admin)
            ->get(route('user.asrama.room-supervisors.create', [
                'userId' => $admin->id,
                'asramaUuid' => $dormitory->id,
                'room_id' => $room->id,
            ]))
            ->assertOk()
            ->assertSee('value="'.$room->id.'" selected', false);
    }
}
