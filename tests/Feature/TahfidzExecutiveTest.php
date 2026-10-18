<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzExecutiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_eksekutif_menampilkan_ringkasan_sekolah_dan_export(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Eksekutif', 'code' => 'SCH-EKS', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '15151515', 'name' => 'Sekolah Eksekutif']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Eksekutif', 'email' => 'guru.eksekutif@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create(['name' => 'Super Admin Eksekutif', 'email' => 'superadmin.eksekutif@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Eksekutif', 'nis' => 'EK-1', 'nisn' => 'EK-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Eksekutif', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);
        TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->toDateString(),
            'setoran_type' => 'ziyadah', 'juz' => 1, 'nilai_setoran' => 88,
            'status' => 'lulus', 'verification_status' => 'verified',
        ]);
        TahfidzJuzProgress::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'juz_number' => 1,
            'status' => 'selesai_ziyadah', 'halaman_completed' => 20, 'total_halaman_juz' => 20, 'percentage' => 100,
        ]);
        TahfidzProgressRecap::create([
            'student_id' => $student->id, 'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'semester' => 'ganjil', 'total_juz_ziyadah' => 1, 'total_halaman_ziyadah' => 20,
            'total_juz_murajaah' => 0, 'total_halaman_murajaah' => 0, 'total_setoran' => 1,
            'total_hari_setoran' => 1, 'rata_rata_nilai' => 88, 'pencapaian_target_persen' => 80,
            'total_juz_completed' => 1, 'hadits_count' => 0,
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/executive")
            ->assertOk()
            ->assertSee('Laporan Eksekutif Tahfidz')
            ->assertSee('Sekolah Eksekutif')
            ->assertSee('Santri');

        $export = $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/executive?export=1");
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $export->headers->get('content-disposition'));
    }
}
