<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzIntervention;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzInterventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_analisis_risiko_dan_tindak_lanjut_santri(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Intervensi', 'code' => 'SCH-INT', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '10101010', 'name' => 'Sekolah Intervensi']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Intervensi', 'email' => 'guru.intervensi@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create([
            'name' => 'Super Admin Intervensi', 'email' => 'superadmin.intervensi@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Intervensi', 'nis' => 'IN-1', 'nisn' => 'IN-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Intervensi', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/santri/{$student->id}")
            ->assertOk()
            ->assertSee('Analisis Risiko Hafalan')
            ->assertSee('Kritis')
            ->assertSee('Tindak Lanjut');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/santri/{$student->id}/interventions", [
                'type' => 'bimbingan_tambahan',
                'due_date' => now()->addWeek()->toDateString(),
                'notes' => 'Tikror 3x sepekan.',
            ])->assertRedirect();

        $intervention = TahfidzIntervention::firstOrFail();
        $this->assertSame('open', $intervention->status);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/interventions")
            ->assertOk()
            ->assertSee('Santri Intervensi')
            ->assertSee('Bimbingan Tambahan');

        $export = $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/interventions?export=1");
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $export->headers->get('content-disposition'));

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->post("/{$admin->id}/tahfidz/interventions/{$intervention->id}/complete")
            ->assertRedirect();

        $this->assertSame('selesai', $intervention->fresh()->status);
        $this->assertNotNull($intervention->fresh()->completed_at);
    }
}
