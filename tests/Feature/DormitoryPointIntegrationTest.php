<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryPointLedger;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Models\DormitoryRoom;
use App\Models\DormitoryViolation;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Dormitory\DormitoryPointService;
use Database\Seeders\DormitoryPointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryPointIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_violation_writes_negative_ledger_and_applies_threshold_rule(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $room, $student, $year] = $this->fixtures();

        $catalog = DormitoryPointCatalog::where('code', '111')->firstOrFail();

        $first = $this->recordViolation($admin, $dorm, $room, $student, $year, $catalog);
        $this->assertSame('Binaan Sedang', $first->pointRule?->name);
        $this->assertSame('auto', $first->action_status);
        $this->assertSame(-25, (int) DormitoryPointLedger::where('reference_id', $first->id)->value('points'));

        $second = $this->recordViolation($admin, $dorm, $room, $student, $year, $catalog);
        $this->assertSame('Peringatan Berat', $second->pointRule?->name);
        $this->assertSame('pending', $second->action_status);
        $this->assertSame('Surat peringatan / rekomendasi skorsing', $second->recommended_action);

        $service = app(DormitoryPointService::class);
        $this->assertSame(-50, $service->totalsForStudent($student->id, $dorm->id, $year->id));
    }

    public function test_reward_writes_positive_ledger_and_snapshots_points(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $room, $student, $year] = $this->fixtures();

        $catalog = DormitoryPointCatalog::where('code', 'R-01')->firstOrFail();
        $reward = DormitoryReward::create([
            'student_id' => $student->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'title' => 'Placeholder',
            'category' => $catalog->rewardCategoryValue(),
            'level' => $catalog->rewardLevelValue(),
            'point_catalog_id' => $catalog->id,
            'given_by' => $admin->id,
            'awarded_date' => now()->toDateString(),
        ]);

        app(DormitoryPointService::class)->recordReward($reward);
        $reward->refresh();

        $this->assertSame(25, $reward->points);
        $this->assertContains($reward->semester, ['ganjil', 'genap']);
        $this->assertNotNull($reward->ledger_recorded_at);
        $this->assertDatabaseHas('dormitory_point_ledger', [
            'reference_type' => 'dormitory_reward',
            'reference_id' => $reward->id,
            'kind' => 'reward',
            'points' => 25,
        ]);
    }

    private function fixtures(): array
    {
        $workUnitId = (string) Str::uuid();
        \DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Poin', 'code' => 'UPN',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '90909090',
            'name' => 'Sekolah Poin',
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $admin = User::create([
            'name' => 'Admin Poin',
            'email' => 'admin.poin@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $school->id,
            'code' => 'PN-01',
            'name' => 'Asrama Poin',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id,
            'code' => 'W1',
            'name' => 'Gedung 1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'total_rooms' => 1,
            'is_active' => true,
        ]);

        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id,
            'wing_id' => $wing->id,
            'code' => 'P-01',
            'name' => 'Kamar P1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'room_type' => 'reguler',
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Poin',
            'nisn' => '11223344',
            'gender' => 'L',
            'status' => 'active',
        ]);

        DormitoryResident::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        $this->actingAs($admin);

        return [$admin, $dorm, $room, $student, $year];
    }

    private function recordViolation(
        User $admin,
        Dormitory $dorm,
        DormitoryRoom $room,
        Student $student,
        AcademicYear $year,
        DormitoryPointCatalog $catalog
    ): DormitoryViolation {
        $violation = DormitoryViolation::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'recorded_by' => $admin->id,
            'violation_date' => now()->toDateString(),
            'violation_category' => $catalog->severityCategory(),
            'violation_type' => $catalog->name,
            'points' => $catalog->points_min,
            'point_catalog_id' => $catalog->id,
        ]);

        app(DormitoryPointService::class)->recordViolation($violation);

        return $violation->fresh(['pointRule']);
    }
}
