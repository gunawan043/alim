<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Osipp\OsippAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OsippAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_osipp_coordinator_is_scoped_by_school_and_academic_year(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Satuan Pendidikan Uji',
            'code' => 'OSIPP-ACC',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99990001',
            'name' => 'Sekolah Uji OSIPP',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $otherYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'genap',
            'is_active' => false,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);
        $user = User::create([
            'name' => 'Guru Koordinator OSIPP Uji',
            'email' => 'osipp.acc@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        OtherTeacherTask::create([
            'teacher_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'work_unit_id' => $workUnit->id,
            'task_name' => 'Guru Koordinator OSIPP',
            'task_code' => 'guru-koordinator-osipp',
            'weekly_hours' => 2,
            'is_active' => true,
        ]);

        $access = app(OsippAccess::class);

        $this->assertTrue($access->isCoordinator($user, $school->id, $year->id));
        $this->assertFalse($access->isCoordinator($user, $school->id, $otherYear->id));
        $this->assertFalse($access->isCoordinator($user, (string) Str::uuid(), $year->id));

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Guru',
            'jabatan' => 'Guru Koordinator OSIPP',
        ]);

        $user->forceFill(['is_system_admin' => true])->save();

        $this->actingAs($user)
            ->get(route('osipp.dashboard', ['userId' => $user->id]))
            ->assertOk();
    }

    public function test_other_task_does_not_grant_osipp_access(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Satuan Pendidikan Uji',
            'code' => 'OSIPP-NOACC',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99990002',
            'name' => 'Sekolah Uji OSIPP 2',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $user = User::create([
            'name' => 'Wali Kelas Uji',
            'email' => 'wali.osipp@example.test',
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

        $this->assertFalse(app(OsippAccess::class)->isCoordinator($user, $school->id, $year->id));
    }
}
