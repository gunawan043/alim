<?php

namespace Tests\Feature;

use App\Events\StudentMutatedOut;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryResidentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_mutasi_keluar_menutup_penghuni_tanpa_menghapus_data(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Mutasi', 'code' => 'UMT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '81818181', 'name' => 'Sekolah Mutasi']);
        $ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);
        $user = User::create([
            'name' => 'Petugas Mutasi', 'email' => 'mutasi@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'MUT-01', 'name' => 'Asrama Mutasi', 'gender' => 'putra',
            'capacity' => 10, 'is_active' => true,
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id, 'code' => 'W1', 'name' => 'Gedung 1',
            'floor' => 1, 'gender' => 'putra', 'capacity' => 10, 'total_rooms' => 1, 'is_active' => true,
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id, 'wing_id' => $wing->id,
            'code' => 'M-01', 'name' => 'Kamar M1', 'floor' => 1,
            'gender' => 'putra', 'capacity' => 10, 'room_type' => 'reguler', 'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Mutasi',
            'nisn' => '81818100', 'gender' => 'L', 'status' => 'active',
        ]);
        $resident = DormitoryResident::create([
            'student_id' => $student->id, 'room_id' => $room->id, 'dormitory_id' => $dorm->id,
            'academic_year_id' => $ay->id, 'bed_number' => 1, 'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        event(new StudentMutatedOut($student, null, 'mutation', now()->toDateString(), $user->id));

        $resident->refresh();
        $this->assertFalse((bool) $resident->is_active);
        $this->assertSame('keluar', $resident->check_out_reason);
        $this->assertNotNull($resident->check_out_date);
        $this->assertStringContainsString('Mutasi keluar', (string) $resident->notes);
        $this->assertDatabaseHas('dormitory_residents', ['id' => $resident->id]);
    }
}
