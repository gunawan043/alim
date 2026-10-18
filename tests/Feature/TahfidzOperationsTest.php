<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzPackage;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $studyGroup;

    private Student $student;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $schoolUnit = WorkUnit::create([
            'name' => 'Sekolah Operasional Tahfidz', 'code' => 'SCH-OTH', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create(['work_unit_id' => $schoolUnit->id, 'npsn' => '70707070', 'name' => 'Sekolah Operasional Tahfidz']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create(['school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Tahfidz', 'nis' => '777', 'nisn' => '777777',
            'gender' => 'L', 'status' => 'active',
        ]);
        $this->teacher = User::create([
            'name' => 'Guru Halaqah', 'email' => 'guru.halaqah.ops@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Super Admin Ops', 'email' => 'superadmin.ops.tahfidz@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->seed(TahfidzFoundationSeeder::class);
    }

    public function test_halaqah_anggota_setoran_dan_verifikasi(): void
    {
        $package = TahfidzPackage::firstOrFail();
        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah", [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'study_group_id' => $this->studyGroup->id,
                'tahfidz_package_id' => $package->id,
                'name' => 'Halaqah 7A Putra',
                'code' => '7A-PA',
                'gender' => 'putra',
                'teacher_id' => $this->teacher->id,
                'max_members' => 20,
                'is_active' => 1,
            ]);
        $response->assertRedirect();
        $group = TahfidzGroup::firstOrFail();
        $this->assertSame($this->school->id, $group->school_id);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah/{$group->id}/members", [
                'student_ids' => [$this->student->id],
            ])->assertRedirect();

        $this->assertSame(1, TahfidzGroupMember::where('tahfidz_group_id', $group->id)->where('status', 'aktif')->count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/setoran", [
                'tahfidz_group_id' => $group->id,
                'student_id' => $this->student->id,
                'setoran_date' => now()->toDateString(),
                'week_number' => 1,
                'setoran_type' => 'ziyadah',
                'metode_pembelajaran' => 'Talaqqi',
                'juz' => 1,
                'surah_start_name' => 'Al-Fatihah',
                'ayat_start' => 1,
                'surah_end_name' => 'Al-Baqarah',
                'ayat_end' => 5,
                'halaman_start' => 1,
                'halaman_end' => 1,
                'jumlah_halaman' => 1,
                'jumlah_baris' => 15,
                'hasil_hafalan' => 90,
                'khofi' => 80,
                'jali' => 85,
                'status' => 'lulus',
                'catatan_guru' => 'Bagus.',
            ])->assertRedirect();

        $setoran = TahfidzSetoran::firstOrFail();
        $this->assertSame('pending', $setoran->verification_status);
        $this->assertSame('85.00', (string) $setoran->nilai_setoran);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/setoran/{$setoran->id}/verify")
            ->assertRedirect();

        $setoran->refresh();
        $this->assertSame('verified', $setoran->verification_status);
        $this->assertSame($this->admin->id, $setoran->verified_by);
        $this->assertNotNull($setoran->verified_at);

        $progress = TahfidzJuzProgress::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->year->id)
            ->where('juz_number', 1)
            ->firstOrFail();
        $this->assertSame('1.0', (string) $progress->halaman_completed);
        $this->assertSame('5.00', (string) $progress->percentage);

        $recap = TahfidzProgressRecap::where('student_id', $this->student->id)
            ->where('academic_year_id', $this->year->id)
            ->firstOrFail();
        $this->assertSame('1.0', (string) $recap->total_halaman_ziyadah);
        $this->assertSame('85.00', (string) $recap->rata_rata_nilai);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/halaqah/{$group->id}/targets", [
                'student_id' => $this->student->id,
                'semester' => 'ganjil',
                'target_bulan' => 1,
                'juz_start' => 1,
                'juz_end' => 1,
                'target_halaman' => 20,
                'target_hadits' => 5,
                'notes' => 'Target awal.',
            ])->assertRedirect();

        $this->assertDatabaseHas('tahfidz_student_targets', [
            'student_id' => $this->student->id,
            'tahfidz_group_id' => $group->id,
            'target_bulan' => 1,
            'target_halaman' => 20,
        ]);

        $recap->refresh();
        $this->assertSame('5.00', (string) $recap->pencapaian_target_persen);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/halaqah")
            ->assertOk()
            ->assertSee('Halaqah 7A Putra');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/halaqah/{$group->id}")
            ->assertOk()
            ->assertSee('Santri Tahfidz')
            ->assertSee('Target Hafalan Santri')
            ->assertSee('Progres Juz');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/setoran")
            ->assertOk()
            ->assertSee('Santri Tahfidz');
    }

    public function test_setoran_menolak_santri_bukan_anggota_halaqah(): void
    {
        $package = TahfidzPackage::firstOrFail();
        $group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id,
            'school_id' => $this->school->id,
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => $package->id,
            'name' => 'Halaqah Tanpa Anggota',
            'gender' => 'putra',
            'teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/setoran", [
                'tahfidz_group_id' => $group->id,
                'student_id' => $this->student->id,
                'setoran_date' => now()->toDateString(),
                'setoran_type' => 'ziyadah',
                'status' => 'lulus',
            ])
            ->assertStatus(422);

        $this->assertSame(0, TahfidzSetoran::count());
    }
}
