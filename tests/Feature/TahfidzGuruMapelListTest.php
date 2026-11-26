<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guru tahfidz memakai Buku Nilai HALAQAH, bukan buku mapel per rombel.
 *
 * Yang dijaga:
 *  1. Daftar "Buku Nilai Saya" menampilkan halaqah guru tersebut.
 *  2. Guru lain tidak melihat halaqah yang bukan miliknya.
 *  3. Daftar halaqah tidak dicampur blok buku mapel umum.
 *  4. Halaman Data Nilai (departemen) mengarahkan guru halaqah ke buku nilainya.
 */
class TahfidzGuruMapelListTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private WorkUnit $unit;

    private AcademicYear $year;

    private StudyGroup $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create(['name' => 'Unit Tahfidz', 'code' => 'UT-GM', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $this->unit->id, 'npsn' => '90909091', 'name' => 'Sekolah Halaqah']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $this->class = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
    }

    public function test_daftar_buku_menampilkan_halaqah_guru(): void
    {
        $teacher = $this->makeTeacher('Guru Halaqah', 'guru.halaqah@test.local');
        $this->makeGroup('Halaqah Umar', $teacher, 2);

        $response = $this->actingAs($teacher)
            ->get(route('user.schools.guru-mapel.index', ['userId' => $teacher->id]));

        $response->assertOk()
            ->assertSee('Buku Nilai Halaqah')
            ->assertSee('Halaqah Umar')
            ->assertSee('2 santri');
    }

    public function test_guru_lain_tidak_melihat_halaqah_orang(): void
    {
        $owner = $this->makeTeacher('Pemilik Halaqah', 'owner@test.local');
        $other = $this->makeTeacher('Guru Lain', 'lain@test.local');

        $this->makeGroup('Halaqah Rahasia', $owner, 1);

        $this->actingAs($other)
            ->get(route('user.schools.guru-mapel.index', ['userId' => $other->id]))
            ->assertOk()
            ->assertDontSee('Halaqah Rahasia');
    }

    public function test_guru_tahfidz_tidak_melihat_blok_buku_mapel_umum(): void
    {
        $teacher = $this->makeTeacher('Guru Tahfidz', 'guru.thf@test.local');
        $this->makeGroup('Halaqah Aisyah', $teacher, 1);

        $this->actingAs($teacher)
            ->get(route('user.schools.guru-mapel.index', ['userId' => $teacher->id]))
            ->assertOk()
            ->assertSee('Halaqah Aisyah')
            ->assertDontSee('Total Buku Admin');
    }

    public function test_data_nilai_mengarahkan_guru_halaqah_ke_buku_nilainya(): void
    {
        $teacher = $this->makeTeacher('Guru Halaqah', 'guru.halaqah2@test.local');
        $this->makeGroup('Halaqah Redirect', $teacher, 1);

        $this->actingAs($teacher)
            ->get(route('user.schools.nilai.index', ['userId' => $teacher->id]))
            ->assertRedirect(route('user.schools.guru-mapel.index', ['userId' => $teacher->id]));
    }

    private function makeTeacher(string $name, string $email): User
    {
        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        // Middleware validasi akun mengalihkan pengguna tanpa kepegawaian atau
        // tanpa peran yang dikenali, jadi guru uji dilengkapi keduanya.
        $roleId = \Illuminate\Support\Facades\DB::table('roles')
            ->where('name', 'Satuan Pendidikan')
            ->value('id');

        // Database tes dimulai kosong — peran dasar dibuat bila belum ada.
        if (! $roleId) {
            $roleId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\DB::table('roles')->insert([
                'id' => $roleId,
                'name' => 'Satuan Pendidikan',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        \Illuminate\Support\Facades\DB::table('gtk_employments')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Pendidik',
            'jabatan' => 'Guru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function makeGroup(string $name, User $teacher, int $memberCount): TahfidzGroup
    {
        $group = TahfidzGroup::create([
            'work_unit_id' => $this->unit->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->class->id,
            'name' => $name, 'gender' => 'putra',
            'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        for ($i = 0; $i < $memberCount; $i++) {
            $student = Student::create([
                'school_id' => $this->school->id, 'name' => $name.' Santri '.$i,
                'nis' => $name.'-'.$i, 'nisn' => $name.'-'.$i,
                'gender' => 'L', 'status' => 'active',
            ]);

            StudentClassHistory::create([
                'student_id' => $student->id, 'study_group_id' => $this->class->id,
                'academic_year_id' => $this->year->id, 'is_active' => true,
            ]);

            TahfidzGroupMember::create([
                'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
                'academic_year_id' => $this->year->id,
                'status' => 'aktif', 'join_date' => '2026-07-15',
            ]);
        }

        return $group;
    }
}
