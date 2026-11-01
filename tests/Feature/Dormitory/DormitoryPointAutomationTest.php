<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\Dormitory;
use App\Models\DormitoryActivity;
use App\Models\DormitoryActivityAttendance;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryPointLedger;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryViolation;
use App\Models\DormitoryWing;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Dormitory\DormitoryViolationAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryPointAutomationTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private DormitoryResident $resident;

    private AcademicYear $year;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DormitoryPointSeeder::class);

        $this->actor = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->dormitory = Dormitory::factory()->create([
            'is_active' => true,
            'gender' => 'putra',
            'head_id' => $this->actor->id,
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W-01',
            'name' => 'Gedung 01',
            'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-01',
            'name' => 'Kamar 01',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $this->dormitory->school_id,
            'nisn' => '9999200022',
            'name' => 'Santri Poin Otomatis',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->resident = DormitoryResident::create([
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $student->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);
    }

    public function test_absensi_asrama_alpa_membuat_poin_otomatis_dan_batal_saat_diubah_hadir(): void
    {
        $this->actingAs($this->actor);

        $attendance = DormitoryAttendance::create([
            'dormitory_id' => $this->dormitory->id,
            'room_id' => $this->room->id,
            'student_id' => $this->resident->student_id,
            'academic_year_id' => $this->year->id,
            'recorded_by' => $this->actor->id,
            'attendance_date' => now()->toDateString(),
            'session' => 'malam',
            'status' => 'alpa',
        ]);

        $violation = DormitoryViolation::where('source_type', DormitoryViolationAutomationService::SOURCE_DORM_ATTENDANCE_ALPA)
            ->where('source_id', $attendance->id)
            ->first();

        $this->assertNotNull($violation);
        $this->assertTrue($violation->is_automatic);
        $this->assertSame(5, $violation->points);
        $this->assertDatabaseHas('dormitory_point_ledger', [
            'reference_type' => 'dormitory_violation',
            'reference_id' => $violation->id,
            'points' => -5,
        ]);

        $attendance->update(['status' => 'hadir']);

        $this->assertDatabaseMissing('dormitory_violations', [
            'source_type' => DormitoryViolationAutomationService::SOURCE_DORM_ATTENDANCE_ALPA,
            'source_id' => $attendance->id,
        ]);
        $this->assertSame(0, DormitoryPointLedger::where('reference_id', $violation->id)->count());
    }

    public function test_kegiatan_asrama_alpa_membuat_poin_otomatis(): void
    {
        $this->actingAs($this->actor);

        $activity = DormitoryActivity::create([
            'dormitory_id' => $this->dormitory->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Kajian malam',
            'activity_date' => now()->toDateString(),
            'status' => 'open',
            'created_by' => $this->actor->id,
        ]);

        $attendance = DormitoryActivityAttendance::create([
            'activity_id' => $activity->id,
            'resident_id' => $this->resident->id,
            'student_id' => $this->resident->student_id,
            'room_id' => $this->room->id,
            'status' => 'alpa',
            'recorded_by' => $this->actor->id,
            'recorded_at' => now(),
        ]);

        $this->assertDatabaseHas('dormitory_violations', [
            'source_type' => DormitoryViolationAutomationService::SOURCE_ACTIVITY_ALPA,
            'source_id' => $attendance->id,
            'student_id' => $this->resident->student_id,
            'is_automatic' => 1,
        ]);
    }

    public function test_halaman_katalog_menampilkan_pengaturan_integrasi_poin(): void
    {
        $this->actingAs($this->actor)
            ->get(route('user.asrama.point-catalog.index', [
                'userId' => $this->actor->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Integrasi Poin Otomatis')
            ->assertSee('Absensi asrama: alpa');
    }

    public function test_kehadiran_kelas_alpa_membuat_poin_otomatis_untuk_penghuni_asrama(): void
    {
        $this->actingAs($this->actor);

        $grade = GradeLevel::create([
            'school_id' => $this->dormitory->school_id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $this->dormitory->school_id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'capacity' => 32,
            'curriculum_type' => 'merdeka',
            'shift' => 'pagi',
            'is_active' => true,
        ]);

        $attendance = AdminPresensiHarian::create([
            'study_group_id' => $group->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'attendance_date' => now()->toDateString(),
            'student_id' => $this->resident->student_id,
            'status' => 'alpa',
        ]);

        $this->assertDatabaseHas('dormitory_violations', [
            'source_type' => DormitoryViolationAutomationService::SOURCE_ACADEMIC_ATTENDANCE_ALPA,
            'source_id' => $attendance->id,
            'student_id' => $this->resident->student_id,
            'is_automatic' => 1,
        ]);
    }
}
