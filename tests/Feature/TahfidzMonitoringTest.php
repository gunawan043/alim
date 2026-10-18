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
use App\Models\TahfidzMutabaah;
use App\Models\TahfidzPackage;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\TahfidzStudentTarget;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_menandai_santri_tertinggal_dan_antrean_verifikasi(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Monitoring', 'code' => 'SCH-MON', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '60606060', 'name' => 'Sekolah Monitoring']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create(['school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $studyGroup = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);
        $teacher = User::create(['name' => 'Guru Monitoring', 'email' => 'guru.monitoring@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create([
            'name' => 'Super Admin Monitoring', 'email' => 'superadmin.monitoring@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->seed(TahfidzFoundationSeeder::class);
        $package = TahfidzPackage::firstOrFail();

        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'study_group_id' => $studyGroup->id, 'academic_year_id' => $year->id,
            'tahfidz_package_id' => $package->id, 'name' => 'Halaqah Monitoring',
            'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        $good = Student::create(['school_id' => $school->id, 'name' => 'Santri Rajin', 'nis' => 'MR-1', 'nisn' => 'MR-1', 'gender' => 'L', 'status' => 'active']);
        $behind = Student::create(['school_id' => $school->id, 'name' => 'Santri Tertinggal', 'nis' => 'MR-2', 'nisn' => 'MR-2', 'gender' => 'L', 'status' => 'active']);

        foreach ([$good, $behind] as $student) {
            TahfidzGroupMember::create([
                'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
                'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
            ]);
        }

        TahfidzSetoran::create([
            'student_id' => $good->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->toDateString(),
            'setoran_type' => 'ziyadah', 'nilai_setoran' => 88, 'status' => 'lulus', 'verification_status' => 'verified',
        ]);
        TahfidzSetoran::create([
            'student_id' => $behind->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->toDateString(),
            'setoran_type' => 'ziyadah', 'nilai_setoran' => 70, 'status' => 'lulus', 'verification_status' => 'pending',
        ]);
        TahfidzStudentTarget::create([
            'student_id' => $good->id, 'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'semester' => 'ganjil', 'target_bulan' => 1, 'juz_start' => 1, 'juz_end' => 1,
            'target_halaman' => 20, 'target_hadits' => 0, 'assigned_by' => $teacher->id,
        ]);
        TahfidzProgressRecap::create([
            'student_id' => $good->id, 'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'semester' => 'ganjil', 'total_juz_ziyadah' => 1, 'total_halaman_ziyadah' => 20,
            'total_juz_murajaah' => 0, 'total_halaman_murajaah' => 0, 'total_setoran' => 1,
            'total_hari_setoran' => 1, 'rata_rata_nilai' => 88, 'pencapaian_target_persen' => 90,
            'total_juz_completed' => 1, 'hadits_count' => 0,
        ]);
        TahfidzJuzProgress::create([
            'student_id' => $good->id, 'academic_year_id' => $year->id, 'juz_number' => 1,
            'status' => 'selesai_ziyadah', 'halaman_completed' => 20, 'total_halaman_juz' => 20, 'percentage' => 100,
        ]);
        TahfidzMutabaah::create([
            'student_id' => $good->id, 'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'recorded_by' => $teacher->id, 'record_date' => now()->toDateString(),
            'tilawah_halaman' => 2, 'tikror_mandiri_halaman' => 1,
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/monitoring?academic_year_id={$year->id}&date_from=".now()->subDays(30)->toDateString().'&date_to='.now()->toDateString())
            ->assertOk()
            ->assertSee('Santri Rajin')
            ->assertSee('Santri Tertinggal')
            ->assertSee('Perlu Perhatian')
            ->assertSee('Setoran Menunggu Verifikasi');
    }
}
