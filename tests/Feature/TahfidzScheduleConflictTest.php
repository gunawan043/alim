<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\TahfidzGroup;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_guru_bentrok_ditolak_dan_jadwal_tidak_bentrok_diterima(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Jadwal', 'code' => 'SCH-JDW', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '16161616', 'name' => 'Sekolah Jadwal']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Bentrok', 'email' => 'guru.bentrok@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create(['name' => 'Super Admin Jadwal', 'email' => 'superadmin.jadwal@test.local', 'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true]);
        $groupA = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah A', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        $groupB = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah B', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/halaqah/{$groupA->id}/schedules", [
                'day_of_week' => 1,
                'time_start' => '07:30',
                'time_end' => '08:30',
                'schedule_type' => 'setoran',
                'room' => 'Ruang 1',
                'is_active' => 1,
            ])->assertRedirect();

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/halaqah/{$groupB->id}/schedules", [
                'day_of_week' => 1,
                'time_start' => '07:30',
                'time_end' => '08:30',
                'schedule_type' => 'setoran',
                'room' => 'Ruang 2',
                'is_active' => 1,
            ])->assertSessionHasErrors('schedule');

        $this->assertSame(1, \App\Models\TahfidzSchedule::count());

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/halaqah/{$groupB->id}/schedules", [
                'day_of_week' => 1,
                'time_start' => '08:30',
                'time_end' => '09:30',
                'schedule_type' => 'setoran',
                'room' => 'Ruang 2',
                'is_active' => 1,
            ])->assertRedirect();

        $this->assertSame(2, \App\Models\TahfidzSchedule::count());
    }
}
