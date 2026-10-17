<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryActivityController;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryActivity;
use App\Models\DormitoryActivityAttendance;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Presensi kegiatan asrama: dibuat Kepala/Wakil, diisi Staf/Wali Kamar.
 */
class DormitoryActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $leader;

    private User $staff;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private DormitoryResident $resident;

    private AcademicYear $ay;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Kegiatan', 'code' => 'UUKG',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '34343434', 'name' => 'Sekolah Kegiatan',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $this->leader = User::create([
            'name' => 'Kepala Asrama', 'email' => 'kepala.asrama@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->staff = User::create([
            'name' => 'Staf Asrama', 'email' => 'staf.asrama@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'KEG-01', 'name' => 'Asrama Kegiatan', 'gender' => 'putra',
            'capacity' => 20, 'is_active' => true,
        ]);

        DormitoryStaffAssignment::create([
            'user_id' => $this->staff->id,
            'dormitory_id' => $this->dormitory->id,
            'assigned_by_id' => $this->leader->id,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id, 'code' => 'W1', 'name' => 'Gedung 1',
            'floor' => 1, 'gender' => 'putra', 'capacity' => 20, 'total_rooms' => 1, 'is_active' => true,
        ]);

        $this->room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id, 'wing_id' => $wing->id,
            'code' => 'K-01', 'name' => 'Kamar 01', 'floor' => 1,
            'gender' => 'putra', 'capacity' => 10, 'room_type' => 'reguler', 'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Kegiatan',
            'nisn' => '1122334455', 'gender' => 'L', 'status' => 'active',
        ]);

        $this->resident = DormitoryResident::create([
            'student_id' => $student->id, 'room_id' => $this->room->id,
            'dormitory_id' => $this->dormitory->id, 'academic_year_id' => $this->ay->id,
            'bed_number' => 1, 'is_active' => true, 'check_in_date' => now()->subMonth()->toDateString(),
        ]);
    }

    public function test_kegiatan_dibuat_leader_dan_presensi_diisi_staf(): void
    {
        $controller = app(DormitoryActivityController::class);

        $this->actingAs($this->leader);
        $controller->store($this->request('POST', [
            'name' => 'Muhadhoroh Malam',
            'activity_date' => now()->toDateString(),
            'start_time' => '20:00',
            'end_time' => '21:30',
            'location' => 'Aula',
            'description' => 'Latihan pidato',
        ]), $this->leader->id, $this->dormitory->id);

        $activity = DormitoryActivity::firstOrFail();
        $this->assertSame('open', $activity->status);
        $this->assertSame('Muhadhoroh Malam', $activity->name);

        // Staf asrama mengisi presensi.
        $this->actingAs($this->staff);
        $controller->attendanceStore($this->request('POST', [
            'attendances' => [
                $this->resident->id => [
                    'selected' => '1',
                    'status' => 'hadir',
                    'notes' => 'Tepat waktu',
                ],
            ],
        ]), $this->staff->id, $this->dormitory->id, $activity->id);

        $this->assertDatabaseHas('dormitory_activity_attendances', [
            'activity_id' => $activity->id,
            'resident_id' => $this->resident->id,
            'student_id' => $this->resident->student_id,
            'room_id' => $this->room->id,
            'status' => 'hadir',
            'recorded_by' => $this->staff->id,
        ]);

        // Rekap di halaman detail.
        $view = $controller->show($this->request('GET'), $this->leader->id, $this->dormitory->id, $activity->id);
        $data = $view->getData();
        $this->assertSame(1, $data['summary']['hadir']);
        $this->assertSame(1, $data['summary']['total']);

        // Tutup kegiatan → presensi terkunci.
        $this->actingAs($this->leader);
        $controller->close($this->request('POST'), $this->leader->id, $this->dormitory->id, $activity->id);
        $this->assertSame('closed', $activity->fresh()->status);

        $this->actingAs($this->staff);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->attendanceStore($this->request('POST', [
            'attendances' => [
                $this->resident->id => ['selected' => '1', 'status' => 'alpa'],
            ],
        ]), $this->staff->id, $this->dormitory->id, $activity->id);
    }

    private function request(string $method, array $data = []): Request
    {
        $request = Request::create('/kegiatan', $method, $data);
        $request->setUserResolver(fn () => auth()->user());

        return $request;
    }
}
