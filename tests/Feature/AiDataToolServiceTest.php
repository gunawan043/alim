<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GtkAddress;
use App\Models\GtkContact;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Ai\AiDataToolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiDataToolServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Student $student;

    private User $gtk;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit PII', 'code' => 'UPI',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '30303030',
            'name' => 'Sekolah Uji PII',
        ]);

        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Fauzi',
            'name_ar' => 'أحمد فوزي',
            'nis' => '12345',
            'nisn' => '99887766',
            'gender' => 'L',
            'status' => 'active',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '081234567890',
            'email' => 'ahmad@test.local',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->year->id,
            'is_active' => true,
        ]);

        $this->gtk = User::create([
            'name' => 'Ustadz Hasan',
            'email' => 'hasan@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $this->gtk->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'status_kepegawaian' => 'PTY',
            'nupy' => 'NUPY-001',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Fiqih',
            'position_type' => 'guru_mapel',
        ]);

        $profile = GtkProfile::create([
            'user_id' => $this->gtk->id,
            'tempat_lahir' => 'Mataram',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'status_perkawinan' => 'kawin',
        ]);

        GtkContact::create([
            'user_id' => $this->gtk->id,
            'no_hp' => '087700001111',
            'no_whatsapp' => '087700001111',
        ]);

        GtkAddress::create([
            'gtk_profile_id' => $profile->id,
            'type' => 'domisili',
            'jalan' => 'Jl. Guru No. 2',
            'rt_rw' => '01/02',
            'kode_pos' => '83351',
            'desa' => 'Kekait',
            'kecamatan' => 'Gunungsari',
            'kab_kota' => 'Lombok Barat',
            'provinsi' => 'Nusa Tenggara Barat',
        ]);
    }

    public function test_siswa_dapat_dicari_dan_dilihat_detail_jika_berwenang(): void
    {
        $tool = app(AiDataToolService::class);
        $context = [
            'school_id' => $this->school->id,
            'can_view_students' => true,
        ];

        $search = $tool->execute('search_students', ['query' => 'Ahmad'], $context);
        $this->assertSame(1, $search['count']);
        $this->assertSame('Ahmad Fauzi', $search['items'][0]['name']);
        $this->assertSame('12345', $search['items'][0]['nis']);

        $detail = $tool->execute('student_detail', ['nis' => '12345'], $context);
        $this->assertSame('Ahmad Fauzi', $detail['identity']['name']);
        $this->assertSame('Jl. Pendidikan No. 1', $detail['identity']['address']);
        $this->assertArrayHasKey('class_history', $detail);
        $this->assertArrayNotHasKey('nik', $detail['identity']);
        $this->assertArrayNotHasKey('no_kk', $detail['identity']);
    }

    public function test_siswa_tidak_dapat_diakses_tanpa_izin(): void
    {
        $tool = app(AiDataToolService::class);
        $result = $tool->execute('search_students', ['query' => 'Ahmad'], ['school_id' => $this->school->id]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringNotContainsString('Ahmad', json_encode($result));
    }

    public function test_gtk_dapat_dicari_dan_detail_sesuai_izin(): void
    {
        $tool = app(AiDataToolService::class);
        $context = [
            'school_id' => $this->school->id,
            'can_view_gtk' => true,
            'can_view_gtk_detail' => true,
        ];

        $search = $tool->execute('search_gtk', ['query' => 'Hasan'], $context);
        $this->assertSame(1, $search['count']);
        $this->assertSame('Ustadz Hasan', $search['items'][0]['name']);
        $this->assertSame('NUPY-001', $search['items'][0]['nupy']);

        $detail = $tool->execute('gtk_detail', ['nupy' => 'NUPY-001'], $context);
        $this->assertSame('Ustadz Hasan', $detail['identity']['name']);
        $this->assertSame('087700001111', $detail['contact']['no_hp']);
        $this->assertSame('Jl. Guru No. 2', $detail['addresses'][0]['jalan']);
        $this->assertArrayNotHasKey('nik', $detail['identity']);
        $this->assertArrayNotHasKey('npwp', $detail['identity']);

        $withoutDetail = $tool->execute('gtk_detail', ['nupy' => 'NUPY-001'], [
            'school_id' => $this->school->id,
            'can_view_gtk' => true,
            'can_view_gtk_detail' => false,
        ]);
        $this->assertArrayHasKey('error', $withoutDetail);
    }

    public function test_kelas_yang_diampu_dapat_dihitung(): void
    {
        $tool = app(AiDataToolService::class);
        $context = [
            'school_id' => $this->school->id,
            'my_study_group_ids' => [$this->group->id],
            'my_teaching' => [
                'academic_year' => $this->year->name,
                'classes' => [['id' => $this->group->id, 'name' => '7A', 'grade_level' => 'Kelas 7']],
                'subjects' => ['Fiqih'],
            ],
        ];

        $count = $tool->execute('count_my_students', [], $context);
        $this->assertSame(1, $count['total_active_students']);
        $this->assertSame('7A', $count['classes'][0]['rombel']);

        $classes = $tool->execute('list_my_classes', [], $context);
        $this->assertSame('7A', $classes['classes'][0]['name']);
    }

    public function test_definisi_tool_mengikuti_izin(): void
    {
        $tool = app(AiDataToolService::class);

        $names = collect($tool->definitions([]))->pluck('function.name');
        $this->assertFalse($names->contains('search_students'));
        $this->assertFalse($names->contains('search_gtk'));

        $names = collect($tool->definitions([
            'can_view_students' => true,
            'can_view_gtk' => true,
            'can_view_gtk_detail' => true,
        ]))->pluck('function.name');
        $this->assertTrue($names->contains('search_students'));
        $this->assertTrue($names->contains('student_detail'));
        $this->assertTrue($names->contains('search_gtk'));
        $this->assertTrue($names->contains('gtk_detail'));
    }
}
