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
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman nilai-kelas (tab Leger) harus terbuka untuk rombel yang memuat
 * mapel tahfidz — kolom NPT/Ziyadah/Rabt/Tikrar/STS/SAS ikut terender.
 *
 * Regresi: halaman ini pernah 500 "Undefined variable $selectedAy" karena
 * objek tahun ajaran tidak pernah disiapkan di method sts().
 */
class TahfidzLegerPageTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $class;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Halaman', 'code' => 'UT-PG', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create([
            'work_unit_id' => $unit->id, 'npsn' => '80808093', 'name' => 'Sekolah Halaman',
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->user = $this->makeUser('Wali Kelas Halaman', 'wali.halaman@test.local');

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);
        $this->class = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A',
            'homeroom_teacher_id' => $this->user->id, 'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Halaman',
            'nis' => 'PG-1', 'nisn' => 'PG-1', 'gender' => 'L', 'status' => 'active',
        ]);
        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'is_active' => true,
        ]);
    }

    public function test_tab_leger_terbuka_dengan_kolom_tahfidz(): void
    {
        $tahfidz = $this->makeSubject('THF', "Tahfidzul Qur'an", TahfidzSubjectResolver::FORMAT_TAHFIDZ);
        $umum = $this->makeSubject('MTK', 'Matematika', TahfidzSubjectResolver::FORMAT_UMUM);

        $book = TeacherAdminBook::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->user->id,
            'subject_id' => $tahfidz->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);
        TeacherAdminBook::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->user->id,
            'subject_id' => $umum->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id, 'name' => 'Halaqah Halaman',
            'gender' => 'putra', 'teacher_id' => $this->user->id, 'is_active' => true,
        ]);
        TahfidzGradebook::create([
            'school_id' => $this->school->id, 'tahfidz_group_id' => $group->id,
            'subject_id' => $tahfidz->id, 'academic_year_id' => $this->year->id,
            'semester' => 'ganjil', 'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('user.schools.nilai-kelas.sts', [
            'userId' => $this->user->id,
            'studyGroupId' => $this->class->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'tab' => 'leger',
            'admin_book_id' => $book->id,
            'leger' => 'sts',
        ]));

        $response->assertOk();

        foreach (['NPT', 'Ziyadah', 'Rabt', 'Tikrar', 'STS', 'SAS'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee('Mapel tahfidz memakai kolom');
    }

    private function makeSubject(string $code, string $name, string $format): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id, 'code' => $code, 'name' => $name,
            'category' => 'nasional', 'is_active' => true, 'penilaian_format' => $format,
        ]);
    }

    private function makeUser(string $name, string $email): User
    {
        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $roleId = DB::table('roles')->where('name', 'Satuan Pendidikan')->value('id');
        if (! $roleId) {
            $roleId = (string) Str::uuid();
            DB::table('roles')->insert([
                'id' => $roleId, 'name' => 'Satuan Pendidikan',
                'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId, 'model_id' => $user->id, 'model_type' => User::class,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id,
            'school_id' => $this->school->id, 'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Pendidik', 'jabatan' => 'Guru',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user;
    }
}
