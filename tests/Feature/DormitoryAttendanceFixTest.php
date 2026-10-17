<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryAttendanceController;
use App\Http\Requests\Dormitory\StoreAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
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
 * Regresi presensi asrama: payload form (student_id/room_id/selected)
 * tersimpan dengan benar dan rekap bulanan memakai data nyata.
 */
class DormitoryAttendanceFixTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private DormitoryResident $resident;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Asrama', 'code' => 'UUA',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '99998888', 'name' => 'Sekolah Uji Asrama',
        ]);

        $ay = AcademicYear::create(['name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true]);

        $this->user = User::create([
            'name' => 'Musyrif Uji', 'email' => 'musyrif@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'ASR-FIX', 'name' => 'Asrama Fix', 'gender' => 'putra',
            'capacity' => 20, 'is_active' => true,
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id, 'code' => 'W1', 'name' => 'Gedung 1',
            'floor' => 1, 'gender' => 'putra', 'capacity' => 20, 'total_rooms' => 1, 'is_active' => true,
        ]);

        $this->room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id, 'wing_id' => $wing->id,
            'code' => 'R-01', 'name' => 'Kamar 01', 'floor' => 1,
            'gender' => 'putra', 'capacity' => 10, 'room_type' => 'reguler', 'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Asrama',
            'nisn' => '9876543210', 'gender' => 'L', 'status' => 'active',
        ]);

        $this->resident = DormitoryResident::create([
            'student_id' => $this->student->id,
            'room_id' => $this->room->id,
            'dormitory_id' => $this->dormitory->id,
            'academic_year_id' => $ay->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        $this->actingAs($this->user);
    }

    public function test_presensi_tersimpan_dan_rekap_terisi(): void
    {
        $payload = [
            'attendance_date' => now()->toDateString(),
            'session' => 'malam',
            'attendances' => [
                $this->resident->id => [
                    'selected' => '1',
                    'student_id' => $this->student->id,
                    'room_id' => $this->room->id,
                    'status' => 'hadir',
                    'notes' => 'Tes',
                ],
            ],
        ];

        $request = new class($payload) extends StoreAttendanceRequest
        {
            public function __construct(private array $payload)
            {
                parent::__construct();
            }

            public function validated($key = null, $default = null)
            {
                return $this->payload;
            }
        };
        $request->setUserResolver(fn () => $this->user);

        app(DormitoryAttendanceController::class)->store($request, $this->user->id, $this->dormitory->id);

        $this->assertDatabaseHas('dormitory_attendances', [
            'student_id' => $this->student->id,
            'room_id' => $this->room->id,
            'dormitory_id' => $this->dormitory->id,
            'status' => 'hadir',
            'session' => 'malam',
        ]);

        $recapRequest = Request::create('/rekap', 'GET', [
            'month' => now()->month,
            'year' => now()->year,
        ]);
        $recapRequest->setUserResolver(fn () => $this->user);

        $view = app(DormitoryAttendanceController::class)->recap($recapRequest, $this->user->id, $this->dormitory->id);
        $data = $view->getData();

        $this->assertSame(1, $data['summary']['hadir']);
        $this->assertSame(1, $data['summary']['total']);
        $this->assertSame($this->student->name, $data['studentRecap']->first()['name']);
    }
}
