<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzGradebookService;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Buku Nilai Halaqah memakai pola buku nilai sumatif:
 * data tidak dimuat sebelum halaqah dipilih, pencarian difilter di server,
 * dan nilai tersimpan otomatis lewat JSON per perubahan sel.
 */
class TahfidzPenilaianPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private WorkUnit $unit;

    private AcademicYear $year;

    private StudyGroup $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create(['name' => 'Unit Tahfidz', 'code' => 'UT-PN', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $this->unit->id, 'npsn' => '70707071', 'name' => 'Sekolah Penilaian']);
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

    public function test_halaman_tidak_memuat_santri_sebelum_halaqah_dipilih(): void
    {
        $teacher = $this->makeTeacher('Guru Pilih', 'guru.pilih@test.local');
        $this->makeGroup('Halaqah Pilih', $teacher, ['Santri Satu']);

        $this->actingAs($teacher)
            ->get(route('user.tahfidz.penilaian.index', ['userId' => $teacher->id]))
            ->assertOk()
            ->assertSee('Pilih Halaqah')
            ->assertSee('Pilih halaqah untuk mulai menilai')
            ->assertDontSee('Santri Satu');
    }

    public function test_filter_halaqah_menampilkan_santri_halaqah_itu(): void
    {
        $teacher = $this->makeTeacher('Guru Filter', 'guru.filter@test.local');
        $group = $this->makeGroup('Halaqah Filter', $teacher, ['Santri Filter A', 'Santri Filter B']);

        $this->actingAs($teacher)
            ->get(route('user.tahfidz.penilaian.index', ['userId' => $teacher->id, 'group_id' => $group->id]))
            ->assertOk()
            ->assertSee('Halaqah Filter')
            ->assertSee('Santri Filter A')
            ->assertSee('Santri Filter B')
            ->assertSee('Ziyadah')
            ->assertSee('Rabt')
            ->assertSee('Bobot NR Final');
    }

    public function test_pencarian_menyaring_santri_di_server(): void
    {
        $teacher = $this->makeTeacher('Guru Cari', 'guru.cari@test.local');
        $group = $this->makeGroup('Halaqah Cari', $teacher, ['Ahmad Fauzi', 'Bilal Rahman']);

        $this->actingAs($teacher)
            ->get(route('user.tahfidz.penilaian.index', [
                'userId' => $teacher->id,
                'group_id' => $group->id,
                'search' => 'fauzi',
            ]))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertDontSee('Bilal Rahman');
    }

    public function test_autosave_menyimpan_nilai_dan_mengembalikan_json(): void
    {
        $teacher = $this->makeTeacher('Guru Autosave', 'guru.autosave@test.local');
        $group = $this->makeGroup('Halaqah Autosave', $teacher, ['Santri Autosave']);

        $service = app(TahfidzGradebookService::class);
        $book = $service->booksForTeacher($teacher->id, $this->school->id, $this->year->id)->flatten()->first();
        $studentId = $group->members()->first()->student_id;

        $response = $this->actingAs($teacher)
            ->postJson(route('user.tahfidz.penilaian.store', ['userId' => $teacher->id]), [
                'tahfidz_group_id' => $group->id,
                'tahfidz_gradebook_id' => $book->id,
                'semester' => 'ganjil',
                'scores' => [
                    $studentId => ['ziyadah' => 80, 'rabt' => 70, 'sts' => 90, 'sas' => 85],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $response->assertJsonPath('saved_rows.0.student_id', (string) $studentId);
        $response->assertJsonPath('saved_rows.0.nr_final', 81);

        $this->assertSame(81.0, TahfidzGradebook::find($book->id)->scores()->first()->nr_final);
    }

    public function test_autosave_dengan_bobot_tidak_valid_menolak_tetapi_nilai_tetap_tersimpan(): void
    {
        $teacher = $this->makeTeacher('Guru Bobot', 'guru.bobot@test.local');
        $group = $this->makeGroup('Halaqah Bobot', $teacher, ['Santri Bobot']);

        $service = app(TahfidzGradebookService::class);
        $book = $service->booksForTeacher($teacher->id, $this->school->id, $this->year->id)->flatten()->first();
        $studentId = $group->members()->first()->student_id;

        $this->actingAs($teacher)
            ->postJson(route('user.tahfidz.penilaian.store', ['userId' => $teacher->id]), [
                'tahfidz_group_id' => $group->id,
                'tahfidz_gradebook_id' => $book->id,
                'semester' => 'ganjil',
                'scores' => [$studentId => ['ziyadah' => 75]],
                'weights' => ['ziyadah' => 60, 'rabt' => 20, 'sts' => 20, 'sas' => 20],
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame(75.0, TahfidzGradebook::find($book->id)->scores()->first()->ziyadah);
        $this->assertSame(40.0, TahfidzGradebook::find($book->id)->weight_ziyadah);
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

    /**
     * @param  array<int, string>  $studentNames
     */
    private function makeGroup(string $name, User $teacher, array $studentNames): TahfidzGroup
    {
        $group = TahfidzGroup::create([
            'work_unit_id' => $this->unit->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->class->id,
            'name' => $name, 'gender' => 'putra',
            'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        foreach ($studentNames as $i => $studentName) {
            $student = Student::create([
                'school_id' => $this->school->id, 'name' => $studentName,
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
