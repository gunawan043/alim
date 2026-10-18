<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\NotificationUniversal;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzIntervention;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TahfidzInterventionReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_mengingatkan_tindak_lanjut_terlambat_tanpa_duplikat(): void
    {
        Cache::flush();

        $unit = WorkUnit::create(['name' => 'Sekolah Tindak Lanjut', 'code' => 'SCH-TL', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '12121212', 'name' => 'Sekolah Tindak Lanjut']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Tindak Lanjut', 'email' => 'guru.tl@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Tindak Lanjut', 'nis' => 'TL-1', 'nisn' => 'TL-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Tindak Lanjut', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzIntervention::create([
            'student_id' => $student->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'type' => 'bimbingan_tambahan',
            'notes' => 'Tikror tambahan.', 'due_date' => now()->subDay()->toDateString(),
            'status' => 'open', 'assigned_to' => $teacher->id, 'created_by' => $teacher->id,
        ]);

        $this->artisan('tahfidz:send-intervention-reminders')->assertSuccessful();
        $count = NotificationUniversal::where('user_id', $teacher->id)->count();
        $this->assertGreaterThanOrEqual(1, $count);

        $this->artisan('tahfidz:send-intervention-reminders')->assertSuccessful();
        $this->assertSame($count, NotificationUniversal::where('user_id', $teacher->id)->count());
    }
}
