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
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * JP terpusat: diatur dari Peta Kurikulum (`/kurikulum`) maupun menu Mapel,
 * keduanya menulis ke grade_level_subjects lalu menyinkron ke rombel dan
 * pembagian tugas guru.
 */
class JpTerpusatSinkronTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $kelas7;

    private GradeLevel $kelas8;

    private Subject $bind;

    private User $kurikulum;

    private StudyGroup $rombel7;

    private StudyGroup $rombel8;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit JP Terpusat', 'code' => 'UJPT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '11223344',
            'name' => 'Sekolah JP Terpusat',
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

        $this->bind = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BIND',
            'name' => 'Bahasa Indonesia',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        foreach ([$this->kelas7, $this->kelas8] as $grade) {
            GradeLevelSubject::create([
                'id' => (string) Str::uuid(),
                'academic_year_id' => $this->ay->id,
                'grade_level_id' => $grade->id,
                'subject_id' => $this->bind->id,
                'allocation_hours' => 2,
                'is_active' => true,
            ]);
        }

        $this->rombel7 = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas7->id,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->rombel8 = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->kelas8->id,
            'name' => '8A', 'code' => '8A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->kurikulum = $this->makeKurikulumUser();
    }

    private function jp(GradeLevel $grade): int
    {
        return (int) GradeLevelSubject::where('academic_year_id', $this->ay->id)
            ->where('grade_level_id', $grade->id)
            ->where('subject_id', $this->bind->id)
            ->value('allocation_hours');
    }

    private function jpRombel(StudyGroup $group): ?int
    {
        $v = StudyGroupSubject::where('study_group_id', $group->id)
            ->where('subject_id', $this->bind->id)
            ->value('weekly_hours');

        return $v === null ? null : (int) $v;
    }

    public function test_peta_kurikulum_menyimpan_jp_berbeda_per_tingkat(): void
    {
        $this->actingAs($this->kurikulum)
            ->post(route('user.kurikulum.jp.save', ['userId' => $this->kurikulum->id]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [
                    $this->kelas7->id => [$this->bind->id => 4],
                    $this->kelas8->id => [$this->bind->id => 3],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(4, $this->jp($this->kelas7));
        $this->assertSame(3, $this->jp($this->kelas8));
    }

    public function test_simpan_jp_dari_kurikulum_menyinkron_ke_rombel(): void
    {
        $this->actingAs($this->kurikulum)
            ->post(route('user.kurikulum.jp.save', ['userId' => $this->kurikulum->id]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [
                    $this->kelas7->id => [$this->bind->id => 4],
                    $this->kelas8->id => [$this->bind->id => 3],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(4, $this->jpRombel($this->rombel7), 'Rombel kelas 7 harus 4 JP.');
        $this->assertSame(3, $this->jpRombel($this->rombel8), 'Rombel kelas 8 harus 3 JP.');
    }

    public function test_simpan_jp_dari_kurikulum_menyinkron_ke_penugasan_guru(): void
    {
        $guru = User::factory()->create(['is_active' => true]);

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/JP/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji JP',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment = TeachingAssignment::create([
            'id' => (string) Str::uuid(),
            'decree_id' => $decreeId,
            'teacher_id' => $guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->rombel7->id,
            'subject_id' => $this->bind->id,
            'weekly_hours' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($this->kurikulum)
            ->post(route('user.kurikulum.jp.save', ['userId' => $this->kurikulum->id]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [$this->kelas7->id => [$this->bind->id => 4]],
            ])
            ->assertRedirect();

        $this->assertSame(4, (int) $assignment->fresh()->weekly_hours);
    }

    public function test_jp_per_tingkat_bisa_diubah_dari_menu_mapel(): void
    {
        $this->actingAs($this->kurikulum)
            ->post(route('user.subjects.grade-hours.save', [
                'userId' => $this->kurikulum->id,
                'id' => $this->bind->id,
            ]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [
                    $this->kelas7->id => 5,
                    $this->kelas8->id => 2,
                ],
            ])
            ->assertRedirect();

        $this->assertSame(5, $this->jp($this->kelas7));
        $this->assertSame(2, $this->jp($this->kelas8));

        // Sinkron ke rombel.
        $this->assertSame(5, $this->jpRombel($this->rombel7));
        $this->assertSame(2, $this->jpRombel($this->rombel8));
    }

    public function test_halaman_mapel_menampilkan_jp_per_tingkat(): void
    {
        GradeLevelSubject::where('subject_id', $this->bind->id)
            ->where('grade_level_id', $this->kelas7->id)
            ->update(['allocation_hours' => 4]);

        $this->actingAs($this->kurikulum)
            ->get(route('user.subjects.show', ['userId' => $this->kurikulum->id, 'id' => $this->bind->id]))
            ->assertOk()
            ->assertSee('JP per Tingkat')
            ->assertSee('Kelas VII')
            ->assertSee('Kelas VIII');
    }

    public function test_guru_biasa_tidak_boleh_mengubah_jp_per_tingkat(): void
    {
        $guru = User::factory()->create(['is_active' => true]);
        $guru->assignRole('Satuan Pendidikan');

        $this->actingAs($guru)
            ->post(route('user.subjects.grade-hours.save', [
                'userId' => $guru->id,
                'id' => $this->bind->id,
            ]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [$this->kelas7->id => 8],
            ])
            ->assertForbidden();

        $this->assertSame(2, $this->jp($this->kelas7));
    }

    public function test_peta_kurikulum_menolak_tingkat_sekolah_lain(): void
    {
        $otherUnit = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $otherUnit, 'name' => 'Unit Lain', 'code' => 'UL-X',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $schoolLain = School::create([
            'work_unit_id' => $otherUnit, 'npsn' => '99880011', 'name' => 'Sekolah Lain',
        ]);

        $gradeLain = GradeLevel::create([
            'school_id' => $schoolLain->id, 'level' => 7,
            'name' => 'Kelas Lain', 'fase' => 'D', 'is_active' => true,
        ]);

        $this->actingAs($this->kurikulum)
            ->post(route('user.kurikulum.jp.save', ['userId' => $this->kurikulum->id]), [
                'academic_year_id' => $this->ay->id,
                'jp' => [$gradeLain->id => [$this->bind->id => 9]],
            ])
            ->assertForbidden();
    }

    private function makeKurikulumUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 3]);

        $user = User::create([
            'name' => 'Koordinator Kurikulum',
            'email' => 'kuri.jp@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Koordinator Kurikulum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => 'Koordinator Kurikulum',
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        return $user;
    }

    private function withScope(User $user): User
    {
        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: implode(',', $user->fresh()->effectiveRoles()) ?: 'default',
            tenantId: 'public',
        );

        return $user;
    }
}
