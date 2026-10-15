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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Increment 2 (item 17) — relasi mapel ↔ kelas:
 * - Mapel yang ditambahkan ke tingkat langsung dipropagasi ke seluruh rombel aktif.
 * - Validasi backend: mapel wajib milik satuan pendidikan yang sama & aktif.
 * - Regresi: kolom `track` pada grade_level_subjects benar-benar tersimpan.
 */
class MapelKelasPropagationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private Subject $ips;

    private StudyGroup $group;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Mapel', 'code' => 'UUM',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '66666666',
            'name' => 'Sekolah Uji Mapel',
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
            'level' => 4,
            'name' => 'Kelas IV',
            'fase' => 'B',
            'is_active' => true,
        ]);

        $this->ips = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'IPS',
            'name' => 'IPS',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '4A', 'code' => '4A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->admin = $this->makeUser('Admin TU Mapel', 'tu.mapel@test.local', 'Staf Tata Usaha');
    }

    public function test_mapel_baru_ke_tingkat_langsung_dipropagasi_ke_rombel_aktif(): void
    {
        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/grade-levels/{$this->grade->id}/subjects/add", [
                'subject_id' => $this->ips->id,
                'allocation_hours' => 4,
            ])
            ->assertRedirect();

        // Baris tingkat tersimpan.
        $this->assertDatabaseHas('grade_level_subjects', [
            'grade_level_id' => $this->grade->id,
            'subject_id' => $this->ips->id,
            'allocation_hours' => 4,
        ]);

        // Propagasi ke rombel aktif: mapel langsung tersedia di Buku Nilai/Jadwal/Leger.
        $this->assertDatabaseHas('study_group_subjects', [
            'study_group_id' => $this->group->id,
            'subject_id' => $this->ips->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => 1,
        ]);
    }

    public function test_mapel_dari_satuan_pendidikan_lain_ditolak(): void
    {
        $otherWorkUnit = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $otherWorkUnit, 'name' => 'Unit Lain', 'code' => 'UL',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $otherSchool = School::create([
            'work_unit_id' => $otherWorkUnit,
            'npsn' => '55555555',
            'name' => 'Sekolah Lain',
        ]);

        $foreign = Subject::create([
            'school_id' => $otherSchool->id,
            'code' => 'BIN',
            'name' => 'Bahasa Indonesia',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->admin->id}/grade-levels/{$this->grade->id}/subjects/add", [
                'subject_id' => $foreign->id,
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseMissing('grade_level_subjects', [
            'grade_level_id' => $this->grade->id,
            'subject_id' => $foreign->id,
        ]);
    }

    public function test_hapus_mapel_dari_tingkat_menonaktifkan_di_rombel_tanpa_menghapus_nilai(): void
    {
        $gls = GradeLevelSubject::create([
            'id' => (string) Str::uuid(),
            'grade_level_id' => $this->grade->id,
            'subject_id' => $this->ips->id,
            'allocation_hours' => 2,
        ]);

        StudyGroupSubject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->ips->id,
            'weekly_hours' => 2,
            'is_active' => true,
        ]);

        // View mengirim id baris GradeLevelSubject pada parameter `subject_id`.
        $this->actingAs($this->admin)
            ->delete("/{$this->admin->id}/grade-levels/{$this->grade->id}/subjects/remove", [
                'subject_id' => $gls->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('grade_level_subjects', ['id' => $gls->id]);

        // Baris rombel TIDAK dihapus (riwayat nilai aman) — hanya dinonaktifkan.
        $this->assertDatabaseHas('study_group_subjects', [
            'study_group_id' => $this->group->id,
            'subject_id' => $this->ips->id,
            'is_active' => 0,
        ]);
    }

    public function test_kolom_track_pada_grade_level_subject_tersimpan(): void
    {
        // Regresi: `track` sebelumnya tidak fillable → nilai dibuang saat insert
        // dan baris duplikat menumpuk karena NULL dianggap unik.
        GradeLevelSubject::updateOrCreate(
            ['grade_level_id' => $this->grade->id, 'subject_id' => $this->ips->id, 'track' => 'ipa'],
            ['allocation_hours' => 2],
        );

        GradeLevelSubject::updateOrCreate(
            ['grade_level_id' => $this->grade->id, 'subject_id' => $this->ips->id, 'track' => 'ipa'],
            ['allocation_hours' => 3],
        );

        $this->assertSame(
            1,
            GradeLevelSubject::where('grade_level_id', $this->grade->id)
                ->where('subject_id', $this->ips->id)
                ->where('track', 'ipa')
                ->count(),
            'updateOrCreate dengan track=ipa tidak boleh menghasilkan baris duplikat.',
        );

        $this->assertSame(3, (int) GradeLevelSubject::first()->allocation_hours);
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
