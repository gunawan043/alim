<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherClassAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi 403: halaman riwayat scan QR harus bisa dibuka guru biasa.
 * Scoping data (milik sendiri vs semua) dilakukan di controller.
 */
class QrHistoryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_biasa_dapat_membuka_halaman_riwayat(): void
    {
        \App\Models\Permission::firstOrCreate([
            'name' => 'impersonate_role', 'guard_name' => 'web',
        ]);

        $role = Role::create(['name' => 'Guru', 'guard_name' => 'web', 'level' => 12]);

        $user = User::create([
            'name' => 'Guru Riwayat',
            'email' => 'guru.riwayat@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $this->actingAs($user);

        $this->get('/'.$user->id.'/absensi-guru-mapel-qr/history')
            ->assertOk();
    }

    public function test_riwayat_menampilkan_record_absensi(): void
    {
        \App\Models\Permission::firstOrCreate([
            'name' => 'impersonate_role', 'guard_name' => 'web',
        ]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Riwayat', 'code' => 'UUR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '22222222', 'name' => 'Sekolah Uji Riwayat',
        ]);

        $ay = AcademicYear::create(['name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId, 'school_id' => $school->id,
            'level' => 7, 'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'grade_level_id' => $gradeLevelId, 'name' => '7A', 'code' => '7A',
            'room' => '701', 'is_active' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Guru', 'guard_name' => 'web', 'level' => 12]);

        $user = User::create([
            'name' => 'Guru Riwayat', 'email' => 'guru.riwayat@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        $jadwal = JadwalKbm::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'study_group_id' => $group->id, 'subject_id' => $subject->id,
            'teacher_id' => $user->id, 'day_of_week' => 1, 'slot_index' => 1,
            'start_time' => '07:00:00', 'end_time' => '07:45:00',
            'room' => '701', 'is_active' => true,
        ]);

        TeacherClassAttendance::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'study_group_id' => $group->id, 'jadwal_kbm_id' => $jadwal->id,
            'teacher_id' => $user->id, 'attendance_date' => today()->toDateString(),
            'scheduled_start_time' => '07:00:00', 'scheduled_end_time' => '07:45:00',
            'actual_time_in' => '07:05:00', 'actual_time_out' => '07:45:00',
            'late_minutes' => 5, 'early_leave_minutes' => 0, 'duration_minutes' => 40,
            'status_masuk' => 'terlambat', 'status_keluar' => 'selesai',
        ]);

        $this->actingAs($user);

        $this->get('/'.$user->id.'/absensi-guru-mapel-qr/history?start_date='.today()->toDateString().'&end_date='.today()->toDateString())
            ->assertOk()
            ->assertSee('7A')
            ->assertSee('Matematika')
            ->assertSee('Terlambat');
    }

    public function test_riwayat_qr_tidak_memakai_middleware_permission_khusus(): void
    {
        $route = Route::getRoutes()->getByName('user.teacher-qr.history');

        $this->assertNotNull($route, 'Route user.teacher-qr.history harus terdaftar.');

        $middleware = $route->gatherMiddleware();

        $this->assertNotContains('permission:teacher-attendance_view', $middleware);
        $this->assertNotContains('permission:teacher-attendance_report_export', $middleware);
    }

    public function test_export_riwayat_qr_tidak_memakai_middleware_permission_khusus(): void
    {
        $route = Route::getRoutes()->getByName('user.teacher-qr.history.export');

        $this->assertNotNull($route, 'Route export riwayat harus terdaftar.');
        $this->assertNotContains('permission:teacher-attendance_view', $route->gatherMiddleware());
    }
}
