<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Lab\LabAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LabAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_task_scoped_to_school_and_year_is_recognized(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Laboratorium IPA',
            'code' => 'LAB-TEST',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99999991',
            'name' => 'Sekolah Uji Lab',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $user = User::create([
            'name' => 'Koordinator Lab Uji',
            'email' => 'lab.uji@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        OtherTeacherTask::create([
            'teacher_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'work_unit_id' => $workUnit->id,
            'task_name' => 'Koordinator Laboratorium',
            'task_code' => 'koordinator-laboratorium',
            'weekly_hours' => 2,
            'is_active' => true,
        ]);

        $access = app(LabAccess::class);

        $this->assertTrue($access->isCoordinator($user, $school->id, $year->id));
        $this->assertFalse($access->isCoordinator($user, $school->id, (string) Str::uuid()));
    }

    public function test_other_task_does_not_grant_lab_access(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Guru',
            'code' => 'GURU-TEST',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99999992',
            'name' => 'Sekolah Uji Guru',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $user = User::create([
            'name' => 'Guru Uji',
            'email' => 'guru.uji@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        OtherTeacherTask::create([
            'teacher_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'work_unit_id' => $workUnit->id,
            'task_name' => 'Wali Kelas',
            'task_code' => 'wali-kelas',
            'weekly_hours' => 2,
            'is_active' => true,
        ]);

        $this->assertFalse(app(LabAccess::class)->isCoordinator($user, $school->id, $year->id));
    }
}
