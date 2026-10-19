<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\TahfidzDashboardController;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\TahfidzGroup;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class TahfidzDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_tahfidz_dapat_mengakses_dashboard_tahfidz(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Dashboard', 'code' => 'SCH-DSB', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '19191919', 'name' => 'Sekolah Dashboard']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create([
            'name' => 'Guru Dashboard', 'email' => 'guru.dashboard@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Dashboard', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        $controller = app(TahfidzDashboardController::class);
        $method = new ReflectionMethod($controller, 'canAccessDashboard');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, $teacher));
    }
}
