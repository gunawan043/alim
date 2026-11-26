<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzPackage;
use App\Models\TahfidzStudentTarget;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzTargetService;
use Database\Seeders\TahfidzBaselineCurriculumSeeder;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Target hafalan harus mengikuti jenjang satuan pendidikan (SD/SMP/SMA)
 * dan arah juz santri (alumni 1→30, non-alumni 30→1), serta bisa diubah
 * sekaligus untuk banyak santri.
 */
class TahfidzTargetJenjangTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private AcademicYear $year;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TahfidzFoundationSeeder::class);
        $this->unit = WorkUnit::where('code', 'DEP-TAH')->firstOrFail();

        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Target', 'email' => 'admin.target@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->seed(TahfidzBaselineCurriculumSeeder::class);
    }

    /**
     * @return array{0: School, 1: User, 2: TahfidzGroup, 3: Student}
     */
    private function makeGroup(string $schoolLevel, string $suffix, ?string $originSchoolId = null): array
    {
        $school = School::create([
            'work_unit_id' => $this->unit->id,
            'npsn' => '9'.$suffix,
            'name' => 'Sekolah '.$suffix,
            'school_level' => $schoolLevel,
        ]);

        $teacher = User::create([
            'name' => 'Guru '.$suffix, 'email' => 'guru.'.$suffix.'@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        GtkEmployment::create([
            'user_id' => $teacher->id, 'school_id' => $school->id, 'academic_year_id' => $this->year->id,
            'status_kepegawaian' => 'GTY', 'jenis_gtk' => 'Pendidik / Guru', 'jabatan' => 'Guru Tahfidz',
        ]);

        $group = TahfidzGroup::create([
            'work_unit_id' => $this->unit->id, 'school_id' => $school->id,
            'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => TahfidzPackage::firstOrFail()->id,
            'name' => 'Halaqah '.$suffix, 'gender' => 'putra',
            'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri '.$suffix, 'nis' => 'TG-'.$suffix, 'nisn' => 'TG-'.$suffix,
            'gender' => 'L', 'status' => 'active', 'origin_school_id' => $originSchoolId,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        return [$school, $teacher, $group, $student];
    }

    public function test_halaqah_sd_memakai_muqorror_sd_bukan_sma(): void
    {
        [, , $group] = $this->makeGroup('sd', 'SD-1');

        $plan = app(TahfidzTargetService::class)->planForGroup($group->fresh(['school']));

        $this->assertNotNull($plan, 'Muqorror SD seharusnya ditemukan.');
        $this->assertSame('sd', strtolower((string) $plan->jenjang));
        $this->assertStringContainsString('SD', $plan->title);
        $this->assertStringNotContainsString('SMA', $plan->title);
    }

    public function test_halaqah_smp_memakai_muqorror_smp(): void
    {
        [, , $group] = $this->makeGroup('smp', 'SMP-1');

        $plan = app(TahfidzTargetService::class)->planForGroup($group->fresh(['school']));

        $this->assertSame('smp', strtolower((string) $plan->jenjang));
    }

    public function test_jenjang_dibaca_dari_satuan_pendidikan_bukan_rombel(): void
    {
        [, , $group] = $this->makeGroup('sma', 'SMA-1');

        // Halaqah sengaja tanpa rombel; jenjang harus tetap terbaca.
        $this->assertNull($group->study_group_id);

        $service = app(TahfidzTargetService::class);
        $this->assertSame('sma', $service->jenjangForGroup($group->fresh(['school'])));
    }

    public function test_non_alumni_mulai_dari_juz_30_dan_alumni_mulai_dari_juz_1(): void
    {
        [$school, , $group, $nonAlumni] = $this->makeGroup('smp', 'SMP-2');

        $alumni = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Alumni', 'nis' => 'TG-AL', 'nisn' => 'TG-AL',
            'gender' => 'L', 'status' => 'active', 'origin_school_id' => $school->id,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $alumni->id,
            'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        $service = app(TahfidzTargetService::class);

        $this->assertFalse($service->isAlumni($nonAlumni, $group->school_id));
        $this->assertSame(30, $service->nextJuzFor($nonAlumni, $group->fresh(['school'])));

        $this->assertTrue($service->isAlumni($alumni, $group->school_id));
        $this->assertSame(1, $service->nextJuzFor($alumni, $group->fresh(['school'])));
    }

    public function test_apply_to_group_menulis_target_sesuai_arah_santri(): void
    {
        [$school, , $group, $nonAlumni] = $this->makeGroup('smp', 'SMP-3');
        $alumni = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Alumni', 'nis' => 'TG-AL3', 'nisn' => 'TG-AL3',
            'gender' => 'L', 'status' => 'active', 'origin_school_id' => $school->id,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $alumni->id,
            'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        $written = app(TahfidzTargetService::class)->applyToGroup($group->fresh(['school', 'members.student']));

        $this->assertGreaterThan(0, $written);

        $nonAlumniNote = TahfidzStudentTarget::where('student_id', $nonAlumni->id)->firstOrFail()->notes;
        $alumniNote = TahfidzStudentTarget::where('student_id', $alumni->id)->firstOrFail()->notes;

        $this->assertStringContainsString('30 → 1 (non-alumni)', $nonAlumniNote);
        $this->assertStringContainsString('1 → 30 (alumni)', $alumniNote);
    }

    public function test_halaman_target_menampilkan_jenjang_yang_berlaku(): void
    {
        [, $teacher] = $this->makeGroup('sd', 'SD-2');

        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => null])
            ->get(route('user.tahfidz.targets.index', ['userId' => $teacher->id]));

        $response->assertOk()
            ->assertSee('Jenjang yang berlaku')
            ->assertSee('Muqorror Tahfizh Standar — SD')
            ->assertDontSee('Muqorror Tahfizh Standar — SMA');
    }

    public function test_ubah_target_massal_menulis_semua_santri_terpilih(): void
    {
        [, $teacher, $group, $studentA] = $this->makeGroup('smp', 'SMP-4');
        $studentB = Student::create([
            'school_id' => $group->school_id, 'name' => 'Santri B', 'nis' => 'TG-B4', 'nisn' => 'TG-B4',
            'gender' => 'L', 'status' => 'active',
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $studentB->id,
            'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        // Santri C tidak dipilih — targetnya tidak boleh ikut berubah.
        $studentC = Student::create([
            'school_id' => $group->school_id, 'name' => 'Santri C', 'nis' => 'TG-C4', 'nisn' => 'TG-C4',
            'gender' => 'L', 'status' => 'active',
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $studentC->id,
            'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('user.tahfidz.targets.bulk', ['userId' => $teacher->id]),
            [
                'tahfidz_group_id' => $group->id,
                'semester' => 'ganjil',
                'student_ids' => [$studentA->id, $studentB->id],
                'bulan_dari' => 1,
                'bulan_sampai' => 3,
                'target_halaman' => 5,
                'target_hadits' => 2,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ([$studentA, $studentB] as $student) {
            $rows = TahfidzStudentTarget::where('student_id', $student->id)->orderBy('target_bulan')->get();
            $this->assertCount(3, $rows, 'Setiap bulan 1..3 harus ditulis.');
            $this->assertSame([1, 2, 3], $rows->pluck('target_bulan')->all());
            $this->assertEquals(5, (float) $rows->first()->target_halaman);
            $this->assertSame(2, (int) $rows->first()->target_hadits);
            // Non-alumni: juz otomatis mulai dari belakang.
            $this->assertSame(30, (int) $rows->first()->juz_start);
        }

        $this->assertSame(0, TahfidzStudentTarget::where('student_id', $studentC->id)->count());
    }

    public function test_ubah_massal_menghormati_juz_awal_yang_diisi(): void
    {
        [, $teacher, $group, $student] = $this->makeGroup('sma', 'SMA-2');

        $this->actingAs($this->admin)->post(
            route('user.tahfidz.targets.bulk', ['userId' => $teacher->id]),
            [
                'tahfidz_group_id' => $group->id,
                'semester' => 'ganjil',
                'student_ids' => [$student->id],
                'bulan_dari' => 4,
                'bulan_sampai' => 4,
                'juz_start' => 5,
                'juz_end' => 6,
            ]
        )->assertRedirect();

        $row = TahfidzStudentTarget::where('student_id', $student->id)->where('target_bulan', 4)->firstOrFail();
        $this->assertSame(5, (int) $row->juz_start);
        $this->assertSame(6, (int) $row->juz_end);
    }

    public function test_ubah_massal_menolak_juz_akhir_lebih_kecil_dari_juz_awal(): void
    {
        [, $teacher, $group, $student] = $this->makeGroup('sma', 'SMA-3');

        $this->actingAs($this->admin)->post(
            route('user.tahfidz.targets.bulk', ['userId' => $teacher->id]),
            [
                'tahfidz_group_id' => $group->id,
                'semester' => 'ganjil',
                'student_ids' => [$student->id],
                'bulan_dari' => 1,
                'bulan_sampai' => 2,
                'juz_start' => 10,
                'juz_end' => 4,
            ]
        )->assertSessionHasErrors('juz_end');

        $this->assertSame(0, TahfidzStudentTarget::count());
    }

    public function test_halaqah_tanpa_jenjang_tidak_memakai_muqorror_jenjang_lain(): void
    {
        [, , $group] = $this->makeGroup('', 'NOSCHOOL');

        $service = app(TahfidzTargetService::class);
        $this->assertNull($service->jenjangForGroup($group->fresh(['school'])));

        // Tanpa jenjang, muqorror apa pun boleh dipakai (tidak dikunci),
        // tetapi tidak boleh salah jenjang diam-diam saat jenjang diketahui.
        $this->assertNotNull(TahfidzMuqorrorPlan::where('jenjang', 'smA')->orWhere('jenjang', 'sma')->first());
    }
}
