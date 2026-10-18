<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzProgressRecap;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_konsistensi_menemukan_selisih_jumlah_juz(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Konsistensi', 'code' => 'SCH-KON', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '14141414', 'name' => 'Sekolah Konsistensi']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Konsistensi', 'email' => 'guru.konsistensi@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create(['name' => 'Super Admin Konsistensi', 'email' => 'superadmin.konsistensi@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Konsistensi', 'nis' => 'KO-1', 'nisn' => 'KO-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Konsistensi', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);
        TahfidzJuzProgress::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'juz_number' => 1,
            'status' => 'selesai_ziyadah', 'halaman_completed' => 20, 'total_halaman_juz' => 20, 'percentage' => 100,
        ]);
        TahfidzProgressRecap::create([
            'student_id' => $student->id, 'tahfidz_group_id' => $group->id, 'academic_year_id' => $year->id,
            'semester' => 'ganjil', 'total_juz_ziyadah' => 1, 'total_halaman_ziyadah' => 20,
            'total_juz_murajaah' => 0, 'total_halaman_murajaah' => 0, 'total_setoran' => 1,
            'total_hari_setoran' => 1, 'rata_rata_nilai' => 85, 'total_juz_completed' => 0, 'hadits_count' => 0,
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/consistency")
            ->assertOk()
            ->assertSee('Konsistensi Rapor–Leger–Syahadah')
            ->assertSee('Santri Konsistensi')
            ->assertSee('Leger: jumlah juz')
            ->assertSee('1 juz');
    }
}
