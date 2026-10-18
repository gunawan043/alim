<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_menampilkan_temuan_lintas_modul(): void
    {
        $unit = WorkUnit::create(['name' => 'Sekolah Audit', 'code' => 'SCH-AUD', 'type' => 'Unit Akademik', 'is_active' => true]);
        $school = School::create(['work_unit_id' => $unit->id, 'npsn' => '30303030', 'name' => 'Sekolah Audit']);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $teacher = User::create(['name' => 'Guru Audit', 'email' => 'guru.audit@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $admin = User::create([
            'name' => 'Super Admin Audit', 'email' => 'superadmin.audit@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $student = Student::create(['school_id' => $school->id, 'name' => 'Santri Audit', 'nis' => 'AD-1', 'nisn' => 'AD-1', 'gender' => 'L', 'status' => 'active']);
        $group = TahfidzGroup::create([
            'work_unit_id' => $unit->id, 'school_id' => $school->id, 'academic_year_id' => $year->id,
            'name' => 'Halaqah Audit', 'gender' => 'putra', 'teacher_id' => $teacher->id, 'is_active' => true,
        ]);
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
        ]);
        TahfidzSetoran::create([
            'student_id' => $student->id, 'teacher_id' => $teacher->id, 'tahfidz_group_id' => $group->id,
            'academic_year_id' => $year->id, 'setoran_date' => now()->subDays(8)->toDateString(),
            'setoran_type' => 'ziyadah', 'juz' => 1, 'status' => 'lulus', 'verification_status' => 'pending',
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/audit")
            ->assertOk()
            ->assertSee('Audit Akhir Tahfidz')
            ->assertSee('Santri Audit')
            ->assertSee('Setoran Pending')
            ->assertSee('Target Belum Diatur')
            ->assertSee('Mutaba’ah Tertinggal');

        $export = $this->actingAs($admin)->withSession(['sa_school_id' => $school->id])
            ->get("/{$admin->id}/tahfidz/audit?export=1");
        $export->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $export->headers->get('content-disposition'));
    }
}
