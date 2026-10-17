<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryPostController;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_darurat_terkirim_ke_wali(): void
    {
        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Broadcast', 'code' => 'UBR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '78787878', 'name' => 'Sekolah Broadcast']);
        $ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $admin = User::create([
            'name' => 'Admin Broadcast', 'email' => 'admin.broadcast@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'BC-01', 'name' => 'Asrama Broadcast', 'gender' => 'putra',
            'capacity' => 10, 'is_active' => true,
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id, 'code' => 'W1', 'name' => 'Gedung 1',
            'floor' => 1, 'gender' => 'putra', 'capacity' => 10, 'total_rooms' => 1, 'is_active' => true,
        ]);

        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id, 'wing_id' => $wing->id,
            'code' => 'B-01', 'name' => 'Kamar B1', 'floor' => 1,
            'gender' => 'putra', 'capacity' => 10, 'room_type' => 'reguler', 'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Broadcast',
            'nisn' => '77889900', 'gender' => 'L', 'status' => 'active',
        ]);

        DormitoryResident::create([
            'student_id' => $student->id, 'room_id' => $room->id, 'dormitory_id' => $dorm->id,
            'academic_year_id' => $ay->id, 'bed_number' => 1, 'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        $wali = User::create([
            'name' => 'Wali Broadcast', 'email' => 'wali.broadcast@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        WaliSantri::create([
            'user_id' => $wali->id, 'student_id' => $student->id,
            'school_id' => $school->id, 'role' => 'wali', 'is_primary' => true, 'status' => 'active',
        ]);

        $this->actingAs($admin);
        $request = Request::create('/broadcast', 'POST', [
            'title' => 'Siaga Banjir',
            'content' => 'Seluruh santri tetap di asrama.',
            'severity' => 'emergency',
            'broadcast_via' => 'all',
            'ack_required' => 1,
        ]);
        $request->setUserResolver(fn () => $admin);

        app(DormitoryPostController::class)->broadcastStore($request, $admin->id, $dorm->id);

        $this->assertDatabaseHas('dormitory_emergency_broadcasts', [
            'dormitory_id' => $dorm->id,
            'title' => 'Siaga Banjir',
        ]);
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $wali->id,
            'action' => 'emergency_broadcast',
        ]);
    }
}
