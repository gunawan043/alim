<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TahfidzGradebook;
use App\Models\TahfidzGradebookScore;
use App\Models\TahfidzGroup;
use App\Models\TahfidzWeightSetting;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzGradebookService;
use App\Services\TahfidzSubjectResolver;
use App\Services\TahfidzWeightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bobot NR Final tahfidz milik DEPARTEMEN: per sekolah × tahun ajaran,
 * terpisah untuk ganjil (tanpa SAS) dan genap (dengan SAS). Guru tidak
 * dapat mengubahnya, dan buku halaqah lama ikut bobot terbaru.
 */
class TahfidzWeightSettingTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Bobot', 'code' => 'UT-BB', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create([
            'work_unit_id' => $unit->id, 'npsn' => '80808099', 'name' => 'Sekolah Bobot',
        ]);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
    }

    public function test_bobot_bawaan_ganjil_tanpa_sas_dan_genap_dengan_sas(): void
    {
        $service = app(TahfidzWeightService::class);

        $this->assertSame([
            'npt' => 25.0, 'ziyadah' => 20.0, 'rabt' => 20.0,
            'tikrar' => 10.0, 'sts' => 25.0, 'sas' => 0.0,
        ], $service->defaultsFor('ganjil'));

        $this->assertSame([
            'npt' => 25.0, 'ziyadah' => 10.0, 'rabt' => 10.0,
            'tikrar' => 10.0, 'sts' => 20.0, 'sas' => 25.0,
        ], $service->defaultsFor('genap'));
    }

    public function test_simpan_bobot_menulis_dua_semester_dan_menghitung_ulang_buku_lama(): void
    {
        $book = $this->makeGradebook([
            'weight_npt' => 10, 'weight_ziyadah' => 30, 'weight_rabt' => 15,
            'weight_tikrar' => 15, 'weight_sts' => 15, 'weight_sas' => 15,
        ]);
        $score = $this->makeScore($book, ['npt' => 100, 'ziyadah' => 90, 'rabt' => 70, 'tikrar' => 60, 'sts' => 80, 'sas' => 50]);

        $errors = app(TahfidzWeightService::class)->save(
            $this->school->id,
            $this->year->id,
            ['npt' => 25, 'ziyadah' => 20, 'rabt' => 20, 'tikrar' => 10, 'sts' => 25],
            ['npt' => 25, 'ziyadah' => 10, 'rabt' => 10, 'tikrar' => 10, 'sts' => 20, 'sas' => 25],
        );

        $this->assertSame([], $errors);

        $ganjil = TahfidzWeightSetting::where('semester', 'ganjil')->first();
        $genap = TahfidzWeightSetting::where('semester', 'genap')->first();
        $this->assertNotNull($ganjil);
        $this->assertNotNull($genap);
        $this->assertSame(0.0, $ganjil->weights()['sas'], 'Semester ganjil tidak mengenal SAS.');
        $this->assertSame(25.0, $genap->weights()['sas']);

        // Buku lama ikut bobot departemen; NR Final dihitung ulang.
        $book->refresh();
        $this->assertSame(25.0, $book->weights()['npt']);
        // (100×25 + 90×20 + 70×20 + 60×10 + 80×25) ÷ 100 = 83.0
        $this->assertEqualsWithDelta(83.0, (float) $score->fresh()->nr_final, 0.01);
    }

    public function test_bobot_tidak_berjumlah_seratus_ditolak(): void
    {
        $errors = app(TahfidzWeightService::class)->save(
            $this->school->id,
            $this->year->id,
            ['npt' => 25, 'ziyadah' => 20, 'rabt' => 20, 'tikrar' => 10, 'sts' => 20],
            ['npt' => 25, 'ziyadah' => 10, 'rabt' => 10, 'tikrar' => 10, 'sts' => 20, 'sas' => 25],
        );

        $this->assertArrayHasKey('ganjil.total', $errors);
        $this->assertSame(0, TahfidzWeightSetting::count());
    }

    public function test_guru_tidak_dapat_mengubah_bobot_nilai_tahfidz(): void
    {
        $teacher = $this->makeUser('Guru Bobot', 'guru.bobot.tes@test.local');

        $response = $this->actingAs($teacher)->put(
            route('user.tahfidz.format.weights', ['userId' => $teacher->id]),
            [
                'academic_year_id' => $this->year->id,
                'ganjil' => ['npt' => 100, 'ziyadah' => 0, 'rabt' => 0, 'tikrar' => 0, 'sts' => 0],
                'genap' => ['npt' => 100, 'ziyadah' => 0, 'rabt' => 0, 'tikrar' => 0, 'sts' => 0, 'sas' => 0],
            ],
        );

        $response->assertForbidden();
        $this->assertSame(0, TahfidzWeightSetting::count());
    }

    public function test_buku_baru_memakai_bobot_departemen(): void
    {
        TahfidzWeightSetting::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id, 'semester' => 'ganjil',
            'weight_npt' => 25, 'weight_ziyadah' => 20, 'weight_rabt' => 20,
            'weight_tikrar' => 10, 'weight_sts' => 25, 'weight_sas' => 0,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true, 'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
        $teacherId = $this->makeUser('Guru Baru', 'guru.baru.bobot@test.local')->id;

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id, 'name' => 'Halaqah Baru',
            'gender' => 'putra', 'teacher_id' => $teacherId, 'is_active' => true,
        ]);

        app(TahfidzGradebookService::class)->ensureBooks($group, $this->year->id);

        $book = TahfidzGradebook::where('tahfidz_group_id', $group->id)->where('subject_id', $subject->id)->first();
        $this->assertNotNull($book);
        $this->assertSame(25.0, $book->weights()['npt']);
        $this->assertSame(20.0, $book->weights()['ziyadah']);
    }

    private function makeGradebook(array $weights): TahfidzGradebook
    {
        $subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THB', 'name' => "Tahfidz Bobot",
            'category' => 'nasional', 'is_active' => true, 'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
        $teacherId = $this->makeUser('Guru Buku', 'guru.buku.bobot@test.local')->id;
        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id, 'name' => 'Halaqah Lama',
            'gender' => 'putra', 'teacher_id' => $teacherId, 'is_active' => true,
        ]);

        return TahfidzGradebook::create([
            'school_id' => $this->school->id, 'tahfidz_group_id' => $group->id,
            'subject_id' => $subject->id, 'academic_year_id' => $this->year->id,
            'semester' => 'ganjil', 'is_active' => true,
        ] + $weights);
    }

    private function makeScore(TahfidzGradebook $book, array $values): TahfidzGradebookScore
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Bobot',
            'nis' => 'BB-1', 'nisn' => 'BB-1', 'gender' => 'L', 'status' => 'active',
        ]);

        return TahfidzGradebookScore::create([
            'tahfidz_gradebook_id' => $book->id,
            'student_id' => $student->id,
        ] + $values);
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
            'role_id' => $roleId, 'model_type' => User::class, 'model_id' => $user->id,
        ]);

        return $user;
    }
}
