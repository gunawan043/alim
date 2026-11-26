<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\TeachingHoursResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Satu mapel boleh berbeda JP antar tingkat (Bahasa Indonesia 4 JP di kelas 7,
 * 3 JP di kelas 8). JP per tingkat diatur di Peta Kurikulum; JP di halaman
 * Mapel hanya nilai bawaan dan tidak boleh menimpa pengaturan itu.
 */
class MapelJpPerTingkatTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $kelas7;

    private GradeLevel $kelas8;

    private Subject $bind;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit JP Tingkat', 'code' => 'UJT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '55554444',
            'name' => 'Sekolah JP Tingkat',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->kelas7 = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7,
            'name' => 'Kelas VII', 'fase' => 'D', 'is_active' => true,
        ]);

        $this->kelas8 = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 8,
            'name' => 'Kelas VIII', 'fase' => 'D', 'is_active' => true,
        ]);

        // JP bawaan di master hanya 2; JP sebenarnya diatur per tingkat.
        $this->bind = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BIND',
            'name' => 'Bahasa Indonesia',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        // Bahasa Indonesia: 4 JP di kelas 7, 3 JP di kelas 8.
        GradeLevelSubject::create([
            'id' => (string) Str::uuid(),
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas7->id,
            'subject_id' => $this->bind->id,
            'allocation_hours' => 4,
            'is_active' => true,
        ]);

        GradeLevelSubject::create([
            'id' => (string) Str::uuid(),
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas8->id,
            'subject_id' => $this->bind->id,
            'allocation_hours' => 3,
            'is_active' => true,
        ]);

        $this->admin = $this->makeUser('Admin JP', 'jp@test.local', 'Staf Tata Usaha');
    }

    private function jpTingkat(GradeLevel $grade): int
    {
        return (int) GradeLevelSubject::where('academic_year_id', $this->ay->id)
            ->where('grade_level_id', $grade->id)
            ->where('subject_id', $this->bind->id)
            ->value('allocation_hours');
    }

    public function test_jp_berbeda_antar_tingkat_tersimpan(): void
    {
        $this->assertSame(4, $this->jpTingkat($this->kelas7));
        $this->assertSame(3, $this->jpTingkat($this->kelas8));
    }

    public function test_edit_jp_master_tidak_menimpa_jp_tingkat(): void
    {
        $this->actingAs($this->admin)
            ->put(route('user.subjects.update', [
                'userId' => $this->admin->id,
                'id' => $this->bind->id,
            ]), [
                'code' => 'BIND',
                'name' => 'Bahasa Indonesia',
                'category' => 'nasional',
                'kelompok' => 'bahasa',
                'credit_hours' => 6,
                'is_active' => 1,
            ])
            ->assertRedirect();

        // Master berubah...
        $this->assertSame(6, (int) $this->bind->fresh()->credit_hours);

        // ...tetapi JP per tingkat TIDAK ikut tertimpa.
        $this->assertSame(4, $this->jpTingkat($this->kelas7), 'Kelas 7 harus tetap 4 JP.');
        $this->assertSame(3, $this->jpTingkat($this->kelas8), 'Kelas 8 harus tetap 3 JP.');
    }

    public function test_edit_jp_master_mengisi_tingkat_yang_belum_punya_jp(): void
    {
        $kelas9 = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 9,
            'name' => 'Kelas IX', 'fase' => 'D', 'is_active' => true,
        ]);

        GradeLevelSubject::create([
            'id' => (string) Str::uuid(),
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $kelas9->id,
            'subject_id' => $this->bind->id,
            'allocation_hours' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('user.subjects.update', [
                'userId' => $this->admin->id,
                'id' => $this->bind->id,
            ]), [
                'code' => 'BIND',
                'name' => 'Bahasa Indonesia',
                'category' => 'nasional',
                'kelompok' => 'bahasa',
                'credit_hours' => 5,
                'is_active' => 1,
            ])
            ->assertRedirect();

        // Tingkat kosong diisi nilai bawaan.
        $this->assertSame(5, $this->jpTingkat($kelas9));

        // Tingkat yang sudah diatur tetap.
        $this->assertSame(4, $this->jpTingkat($this->kelas7));
        $this->assertSame(3, $this->jpTingkat($this->kelas8));
    }

    public function test_resolver_jp_memakai_jp_tingkat_bukan_jp_master(): void
    {
        $rombel7 = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas7->id,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);

        $rombel8 = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas8->id,
            'name' => '8A', 'code' => '8A', 'capacity' => 30, 'is_active' => true,
        ]);

        $resolver = app(TeachingHoursResolver::class);

        $this->assertSame(4, $resolver->resolve($rombel7, $this->bind));
        $this->assertSame(3, $resolver->resolve($rombel8, $this->bind));
    }

    public function test_jp_rombel_yang_sudah_diatur_tidak_ditimpa(): void
    {
        $rombel7 = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas7->id,
            'name' => '7B', 'code' => '7B', 'capacity' => 30, 'is_active' => true,
        ]);

        // JP khusus rombel sudah ditetapkan guru/operator. Baris rombel-mapel
        // dibuat otomatis saat rombel dihubungkan ke mapel, jadi di sini hanya
        // menetapkan JP-nya.
        StudyGroupSubject::updateOrCreate(
            [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $rombel7->id,
                'subject_id' => $this->bind->id,
            ],
            ['weekly_hours' => 5, 'is_active' => true],
        );

        $this->actingAs($this->admin)
            ->put(route('user.subjects.update', [
                'userId' => $this->admin->id,
                'id' => $this->bind->id,
            ]), [
                'code' => 'BIND',
                'name' => 'Bahasa Indonesia',
                'category' => 'nasional',
                'kelompok' => 'bahasa',
                'credit_hours' => 6,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(5, (int) StudyGroupSubject::where('study_group_id', $rombel7->id)
            ->where('subject_id', $this->bind->id)
            ->value('weekly_hours'));
    }

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 3]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $this->seedSnapshot($user);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => $jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'public',
        );

        $this->actingAs($user)->withSession(['scope_key' => (string) $scopeKey]);
    }
}
