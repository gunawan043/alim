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


    public function test_tab_mapel_tahfidz_memakai_enam_kolom_dan_menyimpan_ke_halaqah(): void
    {
        $tahfidz = $this->makeSubject('THF', "Tahfidzul Qur'an", TahfidzSubjectResolver::FORMAT_TAHFIDZ);
        $book = TeacherAdminBook::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->user->id,
            'subject_id' => $tahfidz->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id, 'name' => 'Halaqah Mapel',
            'gender' => 'putra', 'teacher_id' => $this->user->id, 'is_active' => true,
        ]);
        $gradebook = TahfidzGradebook::create([
            'school_id' => $this->school->id, 'tahfidz_group_id' => $group->id,
            'subject_id' => $tahfidz->id, 'academic_year_id' => $this->year->id,
            'semester' => 'ganjil', 'is_active' => true,
        ]);

        $student = Student::where('nis', 'PG-1')->first();
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $this->year->id, 'status' => 'aktif', 'join_date' => '2026-07-15',
        ]);

        // Halaman tab Mapel: format tahfidz, bukan Sumatif Harian S1–S6.
        $response = $this->actingAs($this->user)->get(route('user.schools.nilai-kelas.sts', [
            'userId' => $this->user->id,
            'studyGroupId' => $this->class->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'tab' => 'mapel',
            'admin_book_id' => $book->id,
        ]));

        $response->assertOk()
            ->assertSee('Kelola Bobot')
            ->assertSee('Nilai Tahfidz')
            ->assertSee('NR Final')
            ->assertDontSee('Asesmen Sumatif Harian');

        // Tampilan & fitur sama dengan mapel umum: tabel spreadsheet yang
        // bisa tempel dari Excel (pemicu Undo/Redo dan pencarian nama).
        $response->assertSee('id="tab-mapel" data-paste-grid', false);
        $response->assertSee('title="Jumlah = NR Final × Bobot"', false);

        // Enam kolom, sama dengan leger.
        foreach (['NPT', 'Ziyadah', 'Rabt', 'Tikrar', 'STS', 'SAS'] as $label) {
            $response->assertSee($label);
            $response->assertSee('name="weights['.strtolower($label).']"', false);
        }

        // Simpan nilai: masuk ke buku halaqah santri tersebut.
        $this->actingAs($this->user)->post(
            route('user.schools.nilai-kelas.sts.store', ['userId' => $this->user->id, 'studyGroupId' => $this->class->id]),
            [
                'tab' => 'mapel_tahfidz',
                'admin_book_id' => $book->id,
                'semester' => 'ganjil',
                'academic_year_id' => $this->year->id,
                'weights' => ['npt' => 10, 'ziyadah' => 30, 'rabt' => 15, 'tikrar' => 15, 'sts' => 15, 'sas' => 15],
                'nilai' => [$student->id => [
                    'npt' => 100, 'ziyadah' => 90, 'rabt' => 70, 'tikrar' => 60, 'sts' => 80, 'sas' => 100,
                ]],
            ],
            ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'],
        )->assertOk()->assertJson(['success' => true]);

        $score = $gradebook->scores()->where('student_id', $student->id)->first();

        $this->assertNotNull($score, 'Nilai tahfidz harus masuk ke buku halaqah.');
        $this->assertEqualsWithDelta(90, (float) $score->ziyadah, 0.01);
        $this->assertEqualsWithDelta(100, (float) $score->npt, 0.01);
        $this->assertEqualsWithDelta(60, (float) $score->tikrar, 0.01);

        $gradebook->refresh();
        // Bobot dikirim 10/30/15/15/15/15, total tetap 100.
        $this->assertEqualsWithDelta(10, (float) $gradebook->weight_npt, 0.01);
        $this->assertEqualsWithDelta(30, (float) $gradebook->weight_ziyadah, 0.01);
        $this->assertEqualsWithDelta(15, (float) $gradebook->weight_tikrar, 0.01);
        // (100×10 + 90×30 + 70×15 + 60×15 + 80×15 + 100×15) ÷ 100 = 83.5
        $this->assertEqualsWithDelta(83.5, (float) $score->fresh()->nr_final, 0.01);
    }

    public function test_mapel_umum_tetap_memakai_sumatif_harian(): void
    {
        $umum = $this->makeSubject('MTK', 'Matematika', TahfidzSubjectResolver::FORMAT_UMUM);
        $book = TeacherAdminBook::create([
            'school_id' => $this->school->id, 'teacher_id' => $this->user->id,
            'subject_id' => $umum->id, 'study_group_id' => $this->class->id,
            'academic_year_id' => $this->year->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);

        $this->actingAs($this->user)->get(route('user.schools.nilai-kelas.sts', [
            'userId' => $this->user->id,
            'studyGroupId' => $this->class->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'tab' => 'mapel',
            'admin_book_id' => $book->id,
        ]))
            ->assertOk()
            ->assertSee('Asesmen Sumatif Harian')
            ->assertDontSee('Format Tahfidz — nilai per halaqah');
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
