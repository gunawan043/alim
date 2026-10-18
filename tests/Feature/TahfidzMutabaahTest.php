<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzPackage;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzMutabaahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private TahfidzGroup $group;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create([
            'name' => 'Sekolah Mutabaah', 'code' => 'SCH-MUT', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '80808080', 'name' => 'Sekolah Mutabaah']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create(['school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $studyGroup = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Mutabaah', 'nis' => '888', 'nisn' => '888888',
            'gender' => 'L', 'status' => 'active',
        ]);
        $teacher = User::create([
            'name' => 'Guru Mutabaah', 'email' => 'guru.mutabaah@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Super Admin Mutabaah', 'email' => 'superadmin.mutabaah@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->seed(TahfidzFoundationSeeder::class);
        $package = TahfidzPackage::firstOrFail();

        $this->group = TahfidzGroup::create([
            'work_unit_id' => $this->school->work_unit_id,
            'school_id' => $this->school->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => $package->id,
            'name' => 'Halaqah Mutabaah',
            'gender' => 'putra',
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $this->group->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'join_date' => now()->toDateString(),
            'status' => 'aktif',
        ]);
    }

    public function test_mutabaah_harian_disimpan_diubah_dan_dicetak(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/mutabaah", [
                'tahfidz_group_id' => $this->group->id,
                'student_id' => $this->student->id,
                'record_date' => $date,
                'sholat_subuh' => 'berjamaah',
                'sholat_dzuhur' => 'berjamaah',
                'sholat_ashar' => 'berjamaah',
                'sholat_maghrib' => 'berjamaah',
                'sholat_isya' => 'berjamaah',
                'sholat_tahajud' => 1,
                'sholat_dhuha' => 1,
                'puasa_sunnah' => 1,
                'sedekah' => 1,
                'tilawah_halaman' => 3,
                'tikror_mandiri_halaman' => 2,
                'wirid_pagi' => 1,
                'wirid_sore' => 1,
                'catatan_musyrif' => 'Istiqamah.',
            ])->assertRedirect();

        $record = TahfidzMutabaah::firstOrFail();
        $this->assertSame('berjamaah', $record->sholat_subuh);
        $this->assertSame(3, $record->tilawah_halaman);
        $this->assertSame($this->admin->id, $record->recorded_by);

        // Hari yang sama harus memperbarui catatan, bukan membuat duplikat.
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/mutabaah", [
                'tahfidz_group_id' => $this->group->id,
                'student_id' => $this->student->id,
                'record_date' => $date,
                'sholat_subuh' => 'sendiri',
                'tilawah_halaman' => 5,
                'tikror_mandiri_halaman' => 4,
            ])->assertRedirect();

        $this->assertSame(1, TahfidzMutabaah::count());
        $record->refresh();
        $this->assertSame('sendiri', $record->sholat_subuh);
        $this->assertSame(5, $record->tilawah_halaman);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/mutabaah?month=".now()->format('Y-m'))
            ->assertOk()
            ->assertSee('Santri Mutabaah')
            ->assertSee('Waraqat al-Mutaba’ah');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/mutabaah/{$this->student->id}/print?month=".now()->format('Y-m')."&group_id={$this->group->id}")
            ->assertOk()
            ->assertSee('Santri Mutabaah')
            ->assertSee('Waraqat al-Mutaba’ah');
    }
}
