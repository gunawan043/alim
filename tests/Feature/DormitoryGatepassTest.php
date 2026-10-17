<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryGatepassController;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryGatepass;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryGatepassTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_gatepass_keluar_lalu_masuk(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Gatepass', 'code' => 'UGP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '91919191', 'name' => 'Sekolah Gatepass']);
        $ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);
        $user = User::create([
            'name' => 'Petugas Gatepass', 'email' => 'gatepass@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'GP-01', 'name' => 'Asrama Gatepass', 'gender' => 'putra',
            'capacity' => 10, 'is_active' => true,
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id, 'code' => 'W1', 'name' => 'Gedung 1',
            'floor' => 1, 'gender' => 'putra', 'capacity' => 10, 'total_rooms' => 1, 'is_active' => true,
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id, 'wing_id' => $wing->id,
            'code' => 'G-01', 'name' => 'Kamar G1', 'floor' => 1,
            'gender' => 'putra', 'capacity' => 10, 'room_type' => 'reguler', 'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Gatepass',
            'nisn' => '91919100', 'gender' => 'L', 'status' => 'active',
        ]);
        DormitoryResident::create([
            'student_id' => $student->id, 'room_id' => $room->id, 'dormitory_id' => $dorm->id,
            'academic_year_id' => $ay->id, 'bed_number' => 1, 'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        $this->actingAs($user);
        $token = Crypt::encryptString($dorm->id.'|'.$student->id);
        $controller = app(DormitoryGatepassController::class);

        $makeRequest = function () use ($user, $token) {
            $r = Request::create('/gatepass/scan', 'POST', ['token' => $token]);
            $r->setUserResolver(fn () => $user);

            return $r;
        };

        $controller->process($makeRequest(), $user->id, $dorm->id);
        $this->assertDatabaseHas('dormitory_gatepasses', ['student_id' => $student->id, 'type' => 'keluar']);

        $controller->process($makeRequest(), $user->id, $dorm->id);
        $this->assertSame(2, DormitoryGatepass::where('student_id', $student->id)->count());
        $this->assertDatabaseHas('dormitory_gatepasses', ['student_id' => $student->id, 'type' => 'masuk']);
    }
}
