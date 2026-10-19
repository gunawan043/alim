<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupAttendance;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_absensi_halaqah_disimpan_dan_diperbarui(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Absensi', 'code' => 'SCH-ABS', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '17171717', 'name' => 'Sekolah Absensi']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Absensi', 'email' => 'guru.absensi@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create(['name' => 'Super Admin Absensi', 'email' => 'superadmin.absensi@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Absensi', 'nis' => 'AB-1', 'nisn' => 'AB-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Absensi', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/attendance?group_id={$group->id}&date=".now()->toDateString())
            ->assertOk()
            ->assertSee('Buku Kerja Tahfidz')
            ->assertSee('Riwayat')
            ->assertSee('Rekap Bulanan')
            ->assertSee('Santri Absensi');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/attendance?group_id={$group->id}&tab=riwayat")
            ->assertOk()
            ->assertSee('Riwayat Absensi');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/attendance?group_id={$group->id}&tab=rekap")
            ->assertOk()
            ->assertSee('Rekap Bulanan per Santri');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/buku-kerja")
            ->assertOk()
            ->assertSee('Buku Kerja Tahfidz')
            ->assertSee('Halaqah Absensi');

        $payload = [
            'tahfidz_group_id' => $group->id,
            'attendance_date' => now()->toDateString(),
            'session_type' => 'setoran',
            'statuses' => [$student->id => 'hadir'],
            'notes' => [$student->id => 'Tepat waktu'],
        ];

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/attendance", $payload)
            ->assertRedirect();

        $attendance = TahfidzGroupAttendance::firstOrFail();
        $this->assertSame('hadir', $attendance->status);

        $payload['statuses'][$student->id] = 'izin';
        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/attendance", $payload)
            ->assertRedirect();

        $this->assertSame(1, TahfidzGroupAttendance::count());
        $this->assertSame('izin', $attendance->fresh()->status);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->postJson("/{$admin->id}/tahfidz/attendance", $payload)
            ->assertOk()
            ->assertJson(['saved' => true]);
    }
}
