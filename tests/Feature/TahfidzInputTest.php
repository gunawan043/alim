<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzPackage;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzAccess;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TahfidzInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_input_hafalan_mencari_santri_menampilkan_riwayat_dan_menambah_setoran(): void
    {
        config(['tahfidz.auto_verify_setoran' => false]);

        $unit = WorkUnit::create(['name' => 'Sekolah Input', 'code' => 'SCH-INP', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '50505050', 'name' => 'Sekolah Input']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create(['school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $studyGroup = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $teacher = User::create(['name' => 'Guru Input', 'email' => 'guru.input@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create([
            'name' => 'Super Admin Input', 'email' => 'superadmin.input@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        GtkEmployment::create([
            'user_id' => $teacher->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'status_kepegawaian' => 'GTY', 'jenis_gtk' => 'Pendidik / Guru', 'jabatan' => 'Guru Tahfidz',
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Input', 'nis' => 'IN-1', 'nisn' => 'IN-1',
            'gender' => 'L', 'status' => 'active',
        ]);
        $this->seed(TahfidzFoundationSeeder::class);
        $package = TahfidzPackage::firstOrFail();
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'study_group_id' => $studyGroup->id, 'academic_year_id' => $year->id,
            'tahfidz_package_id' => $package->id, 'name' => 'Halaqah Input',
            'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        $otherTeacher = User::create(['name' => 'Guru Halaqah Lain', 'email' => 'guru.lain.input@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $otherGroup = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'study_group_id' => $studyGroup->id, 'academic_year_id' => $year->id,
            'tahfidz_package_id' => $package->id, 'name' => 'Halaqah Lain',
            'gender' => 'putra', 'teacher_id' => $otherTeacher->id, 'is_active' => true,
        ]);

        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);
        TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->toDateString(),
            'setoran_type' => 'ziyadah', 'juz' => 1, 'nilai_setoran' => 85,
            'status' => 'lulus', 'verification_status' => 'verified',
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/input-hafalan")
            ->assertOk()
            ->assertSee('Santri Input')
            ->assertSee('7A | IN-1 | Santri Input')
            ->assertSee('Input Hafalan Santri');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/input-hafalan?student_id={$student->id}")
            ->assertOk()
            ->assertSee('Riwayat Hafalan')
            ->assertSee('Santri Input');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/setoran", [
                'return_to' => 'input',
                'tahfidz_group_id' => $group->id,
                'student_id' => $student->id,
                'setoran_date' => now()->toDateString(),
                'setoran_type' => 'murajaah',
                'juz' => 1,
                'surah_start_name' => 'Al-Fatihah',
                'ayat_start' => 1,
                'surah_end_name' => 'Al-Fatihah',
                'ayat_end' => 7,
                'jumlah_halaman' => 1,
                'hasil_hafalan' => 90,
                'status' => 'lulus',
            ])
            ->assertRedirect(route('user.tahfidz.input.index', ['userId' => $admin->id, 'student_id' => $student->id]));

        $this->assertSame(2, TahfidzSetoran::count());
        $this->assertSame(114, DB::table('tahfidz_surah_master')->count());

        $pending = TahfidzSetoran::where('verification_status', 'pending')->latest()->firstOrFail();
        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/setoran/{$pending->id}/edit?return_to=input")
            ->assertOk()
            ->assertSee('Edit Setoran')
            ->assertSee('Al-Fatihah');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->put("/{$admin->id}/tahfidz/setoran/{$pending->id}", [
                'return_to' => 'input',
                'tahfidz_group_id' => $group->id,
                'student_id' => $student->id,
                'setoran_date' => now()->toDateString(),
                'setoran_type' => 'murajaah',
                'juz' => 1,
                'surah_start_name' => 'Al-Fatihah',
                'ayat_start' => 1,
                'surah_end_name' => 'Al-Fatihah',
                'ayat_end' => 7,
                'jumlah_halaman' => 1,
                'hasil_hafalan' => 92,
                'status' => 'lulus',
            ])
            ->assertRedirect(route('user.tahfidz.input.index', ['userId' => $admin->id, 'student_id' => $student->id]));

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/santri/{$student->id}")
            ->assertOk()
            ->assertSee('Progres Juz')
            ->assertSee('Riwayat Setoran')
            ->assertSee('Santri Input');
        $this->assertTrue(app(TahfidzAccess::class)->isTahfidzTeacher($teacher));
        $this->assertTrue(app(TahfidzAccess::class)->canSubstituteSetoran($teacher, $otherGroup));
    }
}
