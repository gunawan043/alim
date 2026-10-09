<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Smoke UI halaman master area Kurikulum (pola gtk/index):
 * Tahun Ajaran, Mata Pelajaran, Data Kelas, Rombel,
 * Plotting Guru Mengajar, Jadwal Pelajaran, Jam Pelajaran.
 */
class KurikulumMasterUiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $subject;

    private User $kurikulum;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();
    }

    public function test_halaman_tahun_ajaran_tampil_dengan_pola_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/academic-years")
            ->assertOk()
            ->assertSee('Daftar Tahun Ajaran')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('2026/2027');
    }

    public function test_halaman_mata_pelajaran_tampil_dengan_pola_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/subjects")
            ->assertOk()
            ->assertSee('Daftar Mata Pelajaran')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Matematika');
    }

    public function test_halaman_data_kelas_tampil_dan_filter_status(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels")
            ->assertOk()
            ->assertSee('Daftar Tingkat Kelas')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Kelas 7');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/grade-levels?is_active=1")
            ->assertOk()
            ->assertSee('Kelas 7');
    }

    public function test_halaman_rombel_tampil_dan_filter_semua_tahun_ajaran(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups")
            ->assertOk()
            ->assertSee('Daftar Rombongan Belajar')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('7A');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/study-groups?semua_ta=1")
            ->assertOk()
            ->assertSee('7A');
    }

    public function test_halaman_plotting_guru_tampil_dan_filter_status(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments")
            ->assertOk()
            ->assertSee('Daftar Plotting Guru Mengajar')
            ->assertSee('card-animate')
            ->assertSee('Guru Matematika');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/teaching-assignments?status=active")
            ->assertOk()
            ->assertSee('Guru Matematika');
    }

    public function test_halaman_jadwal_pelajaran_tampil_dan_filter_terjadwal(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm")
            ->assertOk()
            ->assertSee('Daftar Jadwal per Rombel')
            ->assertSee('Filter Cepat')
            ->assertSee('card-animate')
            ->assertSee('Rombel Aktif');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jadwal-kbm?status=belum")
            ->assertOk()
            ->assertSee('7A');
    }

    public function test_halaman_jam_pelajaran_tampil_dengan_statistik_gtk(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/jam-pelajaran")
            ->assertOk()
            ->assertSee('Pengaturan Jam Pelajaran')
            ->assertSee('Total Slot')
            ->assertSee('Slot KBM')
            ->assertSee('card-animate');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji UI Kurikulum',
            'code' => 'UUIK',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji UI Kurikulum',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'room' => '701',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'subject-all-access', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teaching-assignment-all-access', 'guard_name' => 'web']);

        $this->kurikulum = User::create([
            'name' => 'Koor Kurikulum UI',
            'email' => 'koor.ui@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole($role);

        $this->guru = User::create([
            'name' => 'Guru Matematika',
            'email' => 'guru.ui@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->guru->assignRole($role);

        $this->seedSnapshot($this->kurikulum);
        $this->seedSnapshot($this->guru);

        Schema::disableForeignKeyConstraints();
        try {
            foreach ([
                [$this->kurikulum, 'Pimpinan & Struktural Pendidikan', 'Koordinator Kurikulum'],
                [$this->guru, 'Pendidik / Guru', 'Guru Mapel'],
            ] as [$user, $jenis, $jabatan]) {
                DB::table('gtk_employments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'school_id' => $this->school->id,
                    'status_kepegawaian' => 'GTY',
                    'jenis_gtk' => $jenis,
                    'jabatan' => $jabatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/UI/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji UI Kurikulum',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->subject->id,
            'weekly_hours' => 6,
            'status' => 'active',
        ]);
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
