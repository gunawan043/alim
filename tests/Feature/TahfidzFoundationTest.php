<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\TahfidzAssessmentRubric;
use App\Models\TahfidzCoordinator;
use App\Models\TahfidzCurriculum;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupTeacher;
use App\Models\TahfidzPackage;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzAccess;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TahfidzFoundationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private TahfidzGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $schoolUnit = WorkUnit::create([
            'name' => 'Sekolah Uji Tahfidz',
            'code' => 'SCH-THF',
            'type' => 'Unit Akademik',
            'is_active' => true,
        ]);

        $this->school = School::create([
            'work_unit_id' => $schoolUnit->id,
            'npsn' => '50505050',
            'name' => 'Sekolah Uji Tahfidz',
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $this->seed(TahfidzFoundationSeeder::class);

        $package = TahfidzPackage::firstOrFail();
        $department = WorkUnit::where('code', 'DEP-TAH')->firstOrFail();

        $curriculum = TahfidzCurriculum::create([
            'work_unit_id' => $department->id,
            'school_id' => $this->school->id,
            'tahfidz_package_id' => $package->id,
            'grade_level_id' => $grade->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'target_juz_start' => 1,
            'target_juz_end' => 1,
            'target_halaman' => 20,
            'target_hadits' => 0,
            'lines_per_page' => 15,
            'kkm_score' => 60,
            'is_active' => true,
            'created_by' => User::create([
                'name' => 'Pembuat Kurikulum', 'email' => 'kurikulum.tahfidz@test.local',
                'password' => bcrypt('password'), 'is_active' => true,
            ])->id,
        ]);

        $halaqahTeacher = $this->user('Guru Halaqah Utama', 'guru.halaqah.utama@test.local');

        $this->group = TahfidzGroup::create([
            'work_unit_id' => $schoolUnit->id,
            'school_id' => $this->school->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => $package->id,
            'curriculum_id' => $curriculum->id,
            'name' => 'Halaqah 7A Putra',
            'code' => '7A-PA',
            'gender' => 'putra',
            'teacher_id' => $halaqahTeacher->id,
            'is_active' => true,
        ]);
    }

    public function test_migrasi_dan_seeder_fondasi_tahfidz_idempoten(): void
    {
        $this->assertTrue(Schema::hasColumn('tahfidz_groups', 'study_group_id'));
        $this->assertTrue(Schema::hasTable('tahfidz_group_teachers'));
        $this->assertTrue(Schema::hasTable('tahfidz_coordinators'));
        $this->assertTrue(Schema::hasTable('tahfidz_assessment_rubrics'));
        $this->assertTrue(Schema::hasColumn('students', 'origin_school_id'));
        $this->assertTrue(Schema::hasColumn('tahfidz_setorans', 'verification_status'));

        $this->seed(TahfidzFoundationSeeder::class);

        $this->assertSame(1, TahfidzPackage::count());
        $this->assertSame(1, TahfidzAssessmentRubric::count());
        $this->assertSame('DEP-TAH', WorkUnit::where('code', 'DEP-TAH')->value('code'));
        $this->assertSame(15, TahfidzPackage::first()->lines_per_page);
        $this->assertSame(604, TahfidzPackage::first()->total_pages);
    }

    public function test_koordinator_sekolah_mengelola_dan_memverifikasi_halaqah(): void
    {
        $coordinator = $this->user('Koordinator Tahfidz', 'koordinator.tahfidz@test.local');
        TahfidzCoordinator::create([
            'school_id' => $this->school->id,
            'user_id' => $coordinator->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);

        $access = app(TahfidzAccess::class);

        $this->assertTrue($access->canManageGroup($coordinator, $this->group));
        $this->assertTrue($access->canInputSetoran($coordinator, $this->group));
        $this->assertTrue($access->canVerifySetoran($coordinator, $this->group));
        $this->assertTrue($access->canViewGroup($coordinator, $this->group));
        $this->assertFalse($access->isDepartment($coordinator));
    }

    public function test_guru_halaqah_hanya_input_dan_tidak_verifikasi(): void
    {
        $teacher = $this->user('Guru Tahfidz', 'guru.tahfidz@test.local');
        TahfidzGroupTeacher::create([
            'tahfidz_group_id' => $this->group->id,
            'user_id' => $teacher->id,
            'role' => 'guru_utama',
            'can_input_setoran' => true,
            'is_active' => true,
        ]);

        $access = app(TahfidzAccess::class);

        $this->assertTrue($access->canInputSetoran($teacher, $this->group));
        $this->assertFalse($access->canVerifySetoran($teacher, $this->group));
        $this->assertTrue($access->canViewGroup($teacher, $this->group));
    }

    public function test_departemen_melihat_semua_tetapi_tidak_input_kecuali_ditugaskan(): void
    {
        $role = Role::firstOrCreate(['name' => 'Departemen Tahfidz', 'guard_name' => 'web']);
        $department = $this->user('Staf Departemen', 'departemen.tahfidz@test.local');
        $department->assignRole($role);

        $access = app(TahfidzAccess::class);

        $this->assertTrue($access->isDepartment($department));
        $this->assertTrue($access->canViewAllSchools($department));
        $this->assertTrue($access->canManageCurriculum($department));
        $this->assertTrue($access->canViewGroup($department, $this->group));
        $this->assertTrue($access->canVerifySetoran($department, $this->group));
        $this->assertFalse($access->canManageGroup($department, $this->group));
        $this->assertFalse($access->canInputSetoran($department, $this->group));

        TahfidzGroupTeacher::create([
            'tahfidz_group_id' => $this->group->id,
            'user_id' => $department->id,
            'role' => 'guru_pendamping',
            'can_input_setoran' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($access->canInputSetoran($department, $this->group));
    }

    public function test_guru_tidak_terhubung_tidak_boleh_melihat_atau_input(): void
    {
        $outsider = $this->user('Guru Lain', 'guru.lain.tahfidz@test.local');
        $access = app(TahfidzAccess::class);

        $this->assertFalse($access->canViewGroup($outsider, $this->group));
        $this->assertFalse($access->canInputSetoran($outsider, $this->group));
        $this->assertFalse($access->canManageGroup($outsider, $this->group));
    }

    public function test_super_admin_memiliki_akses_penuh(): void
    {
        $admin = $this->user('Super Admin', 'superadmin.tahfidz@test.local', true);
        $access = app(TahfidzAccess::class);

        $this->assertTrue($access->isSuperAdmin($admin));
        $this->assertTrue($access->canManageCurriculum($admin));
        $this->assertTrue($access->canManageGroup($admin, $this->group));
        $this->assertTrue($access->canInputSetoran($admin, $this->group));
        $this->assertTrue($access->canVerifySetoran($admin, $this->group));
    }

    private function user(string $name, string $email, bool $systemAdmin = false): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => $systemAdmin,
        ]);
    }
}
