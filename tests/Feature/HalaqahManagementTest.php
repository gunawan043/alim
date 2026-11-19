<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengelolaan halaqah Tahfidz: guru halaqah wajib Guru Tahfidz di sekolah
 * terkait, dan santri halaqah ditarik dari daftar santri sekolah.
 */
class HalaqahManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $studyGroup;

    private User $admin;

    private User $tahfidzTeacher;

    private User $generalTeacher;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create([
            'name' => 'Sekolah Halaqah', 'code' => 'SCH-HLQ', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create([
            'work_unit_id' => $unit->id, 'npsn' => '81818181', 'name' => 'Sekolah Halaqah',
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true,
        ]);
        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin Halaqah', 'email' => 'sa.halaqah@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->tahfidzTeacher = $this->makeEmployee('Guru Tahfidz A', 'tahfidz.a@test.local', 'Guru Tahfidz');
        $this->generalTeacher = $this->makeEmployee('Guru Umum B', 'umum.b@test.local', 'Guru Umum');

        $this->studentA = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Satu', 'nis' => '001',
            'nisn' => '0000000001', 'gender' => 'L', 'status' => 'active',
        ]);
        $this->studentB = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Dua', 'nis' => '002',
            'nisn' => '0000000002', 'gender' => 'L', 'status' => 'active',
        ]);
    }

    public function test_guru_halaqah_harus_guru_tahfidz_pada_sekolah_tersebut(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah", $this->payload([
                'teacher_id' => $this->generalTeacher->id,
            ]))
            ->assertSessionHasErrors('teacher_id');

        $this->assertSame(0, TahfidzGroup::count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah", $this->payload([
                'teacher_id' => $this->tahfidzTeacher->id,
            ]))
            ->assertRedirect();

        $this->assertSame(1, TahfidzGroup::count());
    }

    public function test_dropdown_guru_hanya_berisi_guru_tahfidz(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/halaqah/create")
            ->assertOk()
            ->assertSee('Guru Tahfidz A')
            ->assertDontSee('Guru Umum B');
    }

    public function test_santri_dapat_ditarik_saat_membuat_halaqah(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah", $this->payload([
                'teacher_id' => $this->tahfidzTeacher->id,
                'member_ids' => [$this->studentA->id],
            ]))
            ->assertRedirect();

        $group = TahfidzGroup::firstOrFail();

        $this->assertSame(1, TahfidzGroupMember::where('tahfidz_group_id', $group->id)
            ->where('student_id', $this->studentA->id)
            ->where('status', 'aktif')
            ->count());
    }

    public function test_santri_dari_halaqah_lain_dipindahkan_bukan_ditolak(): void
    {
        $groupA = $this->makeGroup('Halaqah A');
        $groupB = $this->makeGroup('Halaqah B');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah/{$groupA->id}/members", [
                'student_ids' => [$this->studentA->id],
            ])->assertRedirect();

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah/{$groupB->id}/members", [
                'student_ids' => [$this->studentA->id],
            ])->assertRedirect();

        $this->assertSame('pindah_halaqah', TahfidzGroupMember::where('tahfidz_group_id', $groupA->id)
            ->where('student_id', $this->studentA->id)
            ->value('status'));

        $this->assertSame('aktif', TahfidzGroupMember::where('tahfidz_group_id', $groupB->id)
            ->where('student_id', $this->studentA->id)
            ->value('status'));
    }

    public function test_santri_halaqah_lain_tetap_muncul_di_daftar_tarik_santri(): void
    {
        $groupA = $this->makeGroup('Halaqah A');
        $groupB = $this->makeGroup('Halaqah B');

        TahfidzGroupMember::create([
            'tahfidz_group_id' => $groupA->id,
            'student_id' => $this->studentA->id,
            'academic_year_id' => $this->year->id,
            'join_date' => now()->toDateString(),
            'status' => 'aktif',
        ]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/halaqah/{$groupB->id}")
            ->assertOk()
            ->assertSee('Santri Satu');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->studyGroup->id,
            'name' => 'Halaqah 7A Putra',
            'code' => '7A-PA',
            'gender' => 'putra',
            'teacher_id' => $this->tahfidzTeacher->id,
            'max_members' => 20,
            'is_active' => 1,
        ], $overrides);
    }

    private function makeGroup(string $name): TahfidzGroup
    {
        return TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => $name,
            'gender' => 'putra',
            'teacher_id' => $this->tahfidzTeacher->id,
            'is_active' => true,
        ]);
    }

    private function makeEmployee(string $name, string $email, string $jabatan): User
    {
        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => $jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
