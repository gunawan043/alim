<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzAutoVerifyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_memverifikasi_setoran_pending_dan_menghitung_progres(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Pending', 'code' => 'SCH-PND', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '13131313', 'name' => 'Sekolah Pending']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Pending', 'email' => 'guru.pending@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Pending', 'nis' => 'PD-1', 'nisn' => 'PD-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Pending', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        $setoran = TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->toDateString(),
            'setoran_type' => 'ziyadah', 'juz' => 1, 'jumlah_halaman' => 2,
            'status' => 'lulus', 'verification_status' => 'pending',
        ]);

        $this->artisan('tahfidz:auto-verify-pending --dry-run')->assertSuccessful();
        $this->assertSame('pending', $setoran->fresh()->verification_status);

        $this->artisan('tahfidz:auto-verify-pending')->assertSuccessful();
        $this->assertSame('verified', $setoran->fresh()->verification_status);
        $this->assertDatabaseHas('tahfidz_juz_progress', ['student_id' => $student->id, 'juz_number' => 1]);
    }
}
