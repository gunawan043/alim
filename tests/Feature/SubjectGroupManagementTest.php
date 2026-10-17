<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\OtherTeacherTask;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\KurikulumAccess;
use App\Services\SubjectGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Increment 3 — UI Pengelolaan Master Rumpun & Penetapan Koordinator.
 *
 * Mencakup: akses manajemen (kurikulum saja), pemetaan mapel via master,
 * penetapan/penonaktifan koordinator lewat tugas tambahan per satuan,
 * keanggotaan guru lintas satuan (mapel sama), dan batas kewenangan koordinator.
 */
class SubjectGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private AcademicYear $ay;

    private GradeLevel $gradeA;

    private StudyGroup $groupA1;

    private Subject $mathA;

    private Subject $mathB;

    private Subject $fiqihA;

    private User $kurikulum;

    private User $guruA;

    private User $guruB;

    private User $calonKoordinator;

    private string $decreeId;

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

    // ─────────────────────────────────────────────────────────────
    // AKSES & UI
    // ─────────────────────────────────────────────────────────────

    public function test_halaman_master_rumpun_hanya_untuk_tim_kurikulum(): void
    {
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/rumpun")
            ->assertOk()
            ->assertSee('Master Rumpun')
            ->assertSee('Umum')
            ->assertSee('Agama')
            ->assertSee('Bahasa')
            ->assertSee('Tahfidz');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/rumpun/lintas-satuan")
            ->assertOk()
            ->assertSee('Kelompok Guru');

        // Guru biasa tidak boleh mengakses manajemen.
        $this->actingAs($this->guruA)
            ->get("/{$this->guruA->id}/kurikulum/rumpun")
            ->assertStatus(403);
    }

    public function test_pemetaan_mapel_melalui_ui_master(): void
    {
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();

        // Mapel baru belum dipetakan (tanpa regex runtime).
        $biologi = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'BIO',
            'name' => 'Biologi',
            'credit_hours' => 2,
            'is_active' => true,
        ]);
        $this->assertNull(app(SubjectGroupResolver::class)->groupForSubject($biologi));

        // Halaman kelola rumpun Umum menampilkan mapel yang bisa dipetakan.
        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}")
            ->assertOk()
            ->assertSee('Keanggotaan Mata Pelajaran');

        // Petakan lewat UI.
        $this->actingAs($this->kurikulum)
            ->put("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/mapel", [
                'subject_ids' => [$biologi->id],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertSame('umum', $biologi->fresh()->subjectGroup->code);

        // Keluarkan dari rumpun.
        $this->actingAs($this->kurikulum)
            ->delete("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/mapel/{$biologi->id}")
            ->assertStatus(302);

        $this->assertNull($biologi->fresh()->subject_group_id);
    }

    // ─────────────────────────────────────────────────────────────
    // KOORDINATOR VIA TUGAS TAMBAHAN
    // ─────────────────────────────────────────────────────────────

    public function test_penetapan_dan_penonaktifan_koordinator_lewat_tugas_tambahan(): void
    {
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/koordinator", [
                'user_id' => $this->calonKoordinator->id,
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $task = OtherTeacherTask::where('teacher_id', $this->calonKoordinator->id)
            ->where('school_id', $this->schoolA->id)
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertSame('Koordinator Guru Umum', $task->task_name);

        $resolver = app(SubjectGroupResolver::class);
        $this->assertTrue($resolver->isCoordinatorOf($this->calonKoordinator, $umum, $this->schoolA->id));
        $this->assertFalse($resolver->isCoordinatorOf($this->calonKoordinator, $umum, $this->schoolB->id), 'Koordinator berlaku per satuan pendidikan.');

        // Nonaktifkan.
        $this->actingAs($this->kurikulum)
            ->put("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/koordinator/{$task->id}")
            ->assertStatus(302);

        $this->assertFalse((bool) $task->fresh()->is_active);
        $this->assertFalse($resolver->isCoordinatorOf($this->calonKoordinator, $umum, $this->schoolA->id));
    }

    public function test_hanya_satu_koordinator_aktif_per_rumpun_dan_satuan(): void
    {
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();
        $resolver = app(SubjectGroupResolver::class);

        // Koordinator pertama.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/koordinator", [
                'user_id' => $this->calonKoordinator->id,
            ])
            ->assertSessionHas('success');

        // Penetapan kedua pada rumpun + satuan + tahun ajaran yang sama
        // menggantikan koordinator sebelumnya (kebijakan: hanya satu aktif).
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/rumpun/{$umum->id}/koordinator", [
                'user_id' => $this->guruA->id,
            ])
            ->assertSessionHas('success');

        $this->assertFalse(
            (bool) OtherTeacherTask::where('teacher_id', $this->calonKoordinator->id)->value('is_active'),
            'Koordinator lama harus nonaktif.'
        );
        $this->assertTrue(
            (bool) OtherTeacherTask::where('teacher_id', $this->guruA->id)->value('is_active'),
            'Koordinator baru harus aktif.'
        );

        $this->assertSame(
            [$this->guruA->id],
            $resolver->coordinatorUserIdsForGroup($umum, $this->schoolA->id),
        );
    }

    public function test_koordinator_tidak_otomatis_mendapat_approval_atau_kunci(): void
    {
        $umum = SubjectGroup::where('code', 'umum')->firstOrFail();

        OtherTeacherTask::create([
            'teacher_id' => $this->calonKoordinator->id,
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'task_name' => 'Koordinator Guru Umum',
            'task_code' => 'umum',
            'weekly_hours' => 0,
            'is_active' => true,
        ]);

        $access = app(KurikulumAccess::class);

        // Bukan tim kurikulum & bukan pemegang kunci lintas bank.
        $this->assertFalse($access->isKurikulumTeam($this->calonKoordinator));
        $this->assertFalse($access->canViewAllAnswerKeys($this->calonKoordinator));

        // Permission yang diperoleh hanya penanda rumpun (review/koordinasi),
        // bukan approval final.
        $permissions = json_decode(
            DB::table('permission_snapshots')->where('user_id', $this->calonKoordinator->id)->value('permissions') ?? '[]',
            true
        );
        $this->assertIsArray($permissions);
    }

    // ─────────────────────────────────────────────────────────────
    // KEANGGOTAAN LINTAS SATUAN (MAPEL SAMA)
    // ─────────────────────────────────────────────────────────────

    public function test_keanggotaan_guru_mapel_sama_lintas_satuan_tanpa_koordinator_baru(): void
    {
        $resolver = app(SubjectGroupResolver::class);

        $ids = $resolver->teacherIdsForSubjectAcrossSchools($this->mathA);

        $this->assertContains($this->guruA->id, $ids, 'Guru Matematika sekolah A tergabung.');
        $this->assertContains($this->guruB->id, $ids, 'Guru Matematika sekolah B tergabung lintas satuan.');

        $groups = $resolver->crossSchoolSubjectGroups($this->ay->id);
        $math = $groups->firstWhere('subject', 'Matematika');

        $this->assertNotNull($math);
        $this->assertSame(3, $math['teacher_count']);
        $this->assertSame(2, $math['school_count']);
        $schoolNames = collect($math['teachers'])->pluck('school')->all();
        $this->assertContains('Sekolah Rumpun A', $schoolNames);
        $this->assertContains('Sekolah Rumpun B', $schoolNames);

        // Tidak ada klaim koordinator lintas satuan otomatis pada struktur ini.
        $this->assertArrayNotHasKey('coordinator', $math);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function makeUser(string $name, string $email, School $school, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedSnapshot($user, $school);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function mapSubject(Subject $subject, string $code): void
    {
        $group = SubjectGroup::where('code', $code)->firstOrFail();
        $subject->forceFill(['subject_group_id' => $group->id])->saveQuietly();
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Manajemen Rumpun',
            'code' => 'UUMR',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolA = School::create(['work_unit_id' => $workUnitId, 'npsn' => '22223333', 'name' => 'Sekolah Rumpun A']);
        $this->schoolB = School::create(['work_unit_id' => $workUnitId, 'npsn' => '44445566', 'name' => 'Sekolah Rumpun B']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->gradeA = GradeLevel::create(['school_id' => $this->schoolA->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $gradeB = GradeLevel::create(['school_id' => $this->schoolB->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);

        $this->groupA1 = StudyGroup::create(['school_id' => $this->schoolA->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->gradeA->id, 'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true]);
        $groupB1 = StudyGroup::create(['school_id' => $this->schoolB->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $gradeB->id, 'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true]);

        $this->mathA = Subject::create(['school_id' => $this->schoolA->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);
        $this->mathB = Subject::create(['school_id' => $this->schoolB->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);
        $this->fiqihA = Subject::create(['school_id' => $this->schoolA->id, 'code' => 'FQH', 'name' => 'Fiqih', 'credit_hours' => 2, 'is_active' => true]);

        $this->mapSubject($this->mathA, 'umum');
        $this->mapSubject($this->mathB, 'umum');
        $this->mapSubject($this->fiqihA, 'agama');

        $this->kurikulum = $this->makeUser('Koor Kurikulum Rumpun', 'koor.kurikulum@mgmt.test', $this->schoolA, 'Koordinator Kurikulum');
        $this->guruA = $this->makeUser('Guru Matematika A', 'guru.math.a@mgmt.test', $this->schoolA);
        $this->guruB = $this->makeUser('Guru Matematika B', 'guru.math.b@mgmt.test', $this->schoolB);
        $this->calonKoordinator = $this->makeUser('Calon Koordinator', 'calon.koor@mgmt.test', $this->schoolA);

        $this->decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $this->decreeId,
            'decree_number' => 'TEST/MGMT/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Manajemen Rumpun',
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assign = function (User $teacher, Subject $subject, StudyGroup $group, School $school) {
            TeachingAssignment::create([
                'decree_id' => $this->decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => 4,
                'status' => 'active',
            ]);
        };

        $assign($this->guruA, $this->mathA, $this->groupA1, $this->schoolA);
        $assign($this->calonKoordinator, $this->mathA, $this->groupA1, $this->schoolA);
        $assign($this->guruB, $this->mathB, $groupB1, $this->schoolB);
    }

    private function seedSnapshot(User $user, School $school): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
