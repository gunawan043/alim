<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Menu Guru adalah "topi pendidik": apa pun jabatan strukturalnya, guru
 * hanya melihat mapel/rombel/buku yang ia ampu. Akses lintas guru tetap
 * tersedia lewat menu jabatan (lingkup=semua / monitoring).
 */
class GuruWorkspaceScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $groupA;

    private StudyGroup $groupB;

    private Subject $math;

    private Subject $fisika;

    private User $waka;

    private User $guru;

    private TeacherAdminBook $bookWaka;

    private TeacherAdminBook $bookGuru;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Scope Guru',
            'code' => 'UUSG',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88889999',
            'name' => 'Sekolah Uji Scope Guru',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->groupA = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->groupB = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7B',
            'code' => '7B',
            'is_active' => true,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 4,
            'is_active' => true,
        ]);

        $this->fisika = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FIS',
            'name' => 'Fisika',
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        $this->waka = $this->makeUser('Waka Kurikulum', 'waka@test.local', 'Satuan Pendidikan', 'WAKIL KEPALA SATUAN PENDIDIKAN');
        $this->guru = $this->makeUser('Guru Fisika', 'guru@test.local', 'Guru', 'Guru Mapel');

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/SCOPE/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji Scope)',
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
            'teacher_id' => $this->waka->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupA->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupB->id,
            'subject_id' => $this->fisika->id,
            'weekly_hours' => 3,
            'status' => 'active',
        ]);

        // Observer TeachingAssignment sudah membuat buku; firstOrCreate agar
        // pengujian tidak bentrok dengan unique key.
        $this->bookWaka = TeacherAdminBook::firstOrCreate([
            'teacher_id' => $this->waka->id,
            'subject_id' => $this->math->id,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ], [
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $this->bookGuru = TeacherAdminBook::firstOrCreate([
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->fisika->id,
            'study_group_id' => $this->groupB->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ], [
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
    }

    public function test_buku_nilai_menu_guru_hanya_menampilkan_buku_sendiri(): void
    {
        $response = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/schools/guru-mapel');

        $response->assertOk();
        $response->assertSee('Matematika');
        $response->assertDontSee('Fisika');
    }

    public function test_jurnal_menu_guru_hanya_menampilkan_buku_sendiri(): void
    {
        $response = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/realisasi');

        $response->assertOk();
        $response->assertSee('Matematika');
        $response->assertDontSee('Fisika');
    }

    public function test_monitoring_jurnal_hanya_untuk_tim_kurikulum(): void
    {
        $this->actingAs($this->guru)
            ->get('/'.$this->guru->id.'/kurikulum/realisasi/monitoring')
            ->assertForbidden();

        $response = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/realisasi/monitoring');

        $response->assertOk();
        $response->assertSee('Matematika');
        $response->assertSee('Fisika');
        $response->assertSee('Monitoring semua guru');
    }

    public function test_halaman_kurikulum_default_hanya_mapel_diampu_dan_semua_untuk_menu_jabatan(): void
    {
        $personal = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/cp');

        $personal->assertOk();
        $personal->assertSee('Matematika');
        $personal->assertDontSee('Fisika');

        $broad = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/cp?lingkup=semua');

        $broad->assertOk();
        $broad->assertSee('Matematika');
        $broad->assertSee('Fisika');
    }

    public function test_waka_tanpa_penugasan_tidak_mendapat_menu_guru(): void
    {
        TeachingAssignment::where('teacher_id', $this->waka->id)->delete();

        $response = $this->actingAs($this->waka->fresh())
            ->get('/'.$this->waka->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();
        $response->assertDontSee('Menu Guru');
        $response->assertDontSee('sp_sec_guru_absensi', false);
    }

    public function test_menu_guru_tidak_lagi_memuat_absensi_kelas_saya(): void
    {
        $response = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();
        $response->assertSee('Menu Guru');
        $response->assertDontSee('Absensi Kelas Saya');
    }

    public function test_jurnal_guru_melihat_buku_yang_dia_ajar_bersama(): void
    {
        // Guru Fisika juga ditugaskan pada mapel+rombel yang sama dengan Waka
        // (co-teacher). Buku hanya satu, jadi jurnal harus tetap terlihat.
        TeachingAssignment::create([
            'decree_id' => TeachingAssignment::where('teacher_id', $this->waka->id)->value('decree_id'),
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupA->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->guru)
            ->get('/'.$this->guru->id.'/kurikulum/realisasi');

        $response->assertOk();
        $response->assertSee('Fisika');
        $response->assertSee('Matematika');
    }

    public function test_import_kurikulum_mengikuti_lingkup_menu(): void
    {
        $personal = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/import');

        $personal->assertOk();
        $personal->assertSee('mapel yang Anda ampu');

        $broad = $this->actingAs($this->waka)
            ->get('/'.$this->waka->id.'/kurikulum/import?lingkup=semua');

        $broad->assertOk();
        $broad->assertDontSee('mapel yang Anda ampu');
    }

    public function test_guru_tahfidz_yang_juga_mengajar_tetap_mendapat_menu_nilai(): void
    {
        $tahfidz = $this->makeUser('Guru Tahfidz', 'tahfidz@test.local', 'Satuan Pendidikan', 'Guru Tahfidz');

        TeachingAssignment::create([
            'decree_id' => TeachingAssignment::where('teacher_id', $this->waka->id)->value('decree_id'),
            'teacher_id' => $tahfidz->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->groupA->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $response = $this->actingAs($tahfidz->fresh())
            ->get('/'.$tahfidz->id.'/dashboard/satuan-pendidikan');

        $response->assertOk();
        // Menu tahfidz tidak lagi dibungkus satu grup; menu harian tampil
        // langsung, menu pendukung dikelompokkan per fungsi.
        $response->assertSee('Absensi Halaqah');
        $response->assertSee('id="sp_sec_guru_tahfidz_santri"', false);
        $response->assertSee('id="sp_sec_guru_tahfidz_nilai"', false);
        $response->assertSee('sp_sec_guru_nilai', false);
        $response->assertSee('Buku Nilai Saya');
    }

    private function makeUser(string $name, string $email, string $roleName, string $jabatan): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'jabatan' => $jabatan,
            'status_kepegawaian' => 'GTY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedPermissionSnapshot($user, ['jadwalkbm.read']);

        return $user->fresh();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function seedPermissionSnapshot(User $user, array $permissions): void
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
            'permissions' => json_encode(array_values($permissions)),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
