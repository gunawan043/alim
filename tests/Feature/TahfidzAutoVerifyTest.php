<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzJuzProgress;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzAutoVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_setoran_guru_langsung_terverifikasi_dan_progres_langsung_dibuat(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Auto', 'code' => 'SCH-AUTO', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '20202020', 'name' => 'Sekolah Auto']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Auto', 'email' => 'guru.auto@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create([
            'name' => 'Super Admin Auto', 'email' => 'superadmin.auto@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Auto', 'nis' => 'AU-1', 'nisn' => 'AU-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Auto', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        config(['tahfidz.auto_verify_setoran' => true]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/setoran", [
                'tahfidz_group_id' => $group->id,
                'student_id' => $student->id,
                'setoran_date' => now()->toDateString(),
                'setoran_type' => 'ziyadah',
                'juz' => 1,
                'halaman_start' => 1,
                'halaman_end' => 2,
                'hasil_hafalan' => 85,
                'status' => 'lulus',
            ])->assertRedirect();

        $setoran = TahfidzSetoran::firstOrFail();
        $this->assertSame('verified', $setoran->verification_status);
        $this->assertSame('auto-verified', $setoran->verification_notes);
        $this->assertSame($admin->id, $setoran->verified_by);
        $this->assertSame('2.0', (string) $setoran->jumlah_halaman);
        $this->assertDatabaseHas('tahfidz_juz_progress', [
            'student_id' => $student->id,
            'juz_number' => 1,
            'status' => 'sedang',
        ]);
        $this->assertSame(1, TahfidzJuzProgress::where('student_id', $student->id)->count());
    }
}
