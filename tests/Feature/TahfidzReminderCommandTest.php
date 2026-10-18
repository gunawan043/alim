<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\NotificationUniversal;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzCoordinator;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TahfidzReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_mengirim_pengingat_dan_tidak_duplikat_di_hari_yang_sama(): void
    {
        Cache::flush();

        $unit = WorkUnit::create(['name' => 'Sekolah Reminder', 'code' => 'SCH-REM', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '40404040', 'name' => 'Sekolah Reminder']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $coordinator = User::create(['name' => 'Koordinator Reminder', 'email' => 'koordinator.reminder@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $teacher = User::create(['name' => 'Guru Reminder', 'email' => 'guru.reminder@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Reminder', 'nis' => 'RM-1', 'nisn' => 'RM-1', 'gender' => 'L', 'status' => 'active']);

        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id,
            'academic_year_id' => $year->id, 'name' => 'Halaqah Reminder',
            'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);
        TahfidzCoordinator::create([
            'school_id' => $school->id, 'user_id' => $coordinator->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->subDays(3)->toDateString(),
            'setoran_type' => 'ziyadah', 'status' => 'lulus', 'verification_status' => 'pending',
        ]);

        $this->artisan('tahfidz:send-monitoring-reminders --days=1')->assertSuccessful();
        $this->assertGreaterThanOrEqual(1, NotificationUniversal::where('user_id', $coordinator->id)->count());

        $firstCount = NotificationUniversal::where('user_id', $coordinator->id)->count();
        $this->artisan('tahfidz:send-monitoring-reminders --days=1')->assertSuccessful();
        $this->assertSame($firstCount, NotificationUniversal::where('user_id', $coordinator->id)->count());
    }
}
