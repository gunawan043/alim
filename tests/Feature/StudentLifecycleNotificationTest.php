<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMutationOut;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase 4 — Notifikasi wali end-to-end.
 *
 * Memakai DatabaseMigrations (tanpa transaksi pembungkus test) agar
 * callback DB::afterCommit pada listener benar-benar dieksekusi,
 * sama seperti di produksi.
 */
class StudentLifecycleNotificationTest extends TestCase
{
    use DatabaseMigrations;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Student $student;

    private User $tu;

    private User $wali;

    /**
     * Migrasi fresh tanpa rollback saat teardown (DB in-memory; rollback
     * memicu down() migrasi lama yang tidak kompatibel dengan stub SQLite).
     */
    protected function runDatabaseMigrations()
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixture();
    }

    public function test_notifikasi_wali_terkirim_saat_santri_lulus(): void
    {
        $mutation = StudentMutationOut::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'out_type' => 'graduation',
            'status' => 'submitted',
            'student_name' => $this->student->name,
            'graduation_year' => 2027,
            'graduation_certificate_number' => 'IJZ-NOTIF-001',
            'established_date' => '2027-06-20',
        ]);

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/mutations-lulus/{$mutation->id}/approve")
            ->assertStatus(302);

        // Efek lifecycle utama
        $this->assertSame('graduate', $this->student->fresh()->status);
        $this->assertDatabaseHas('alumni', ['student_id' => $this->student->id]);
        $this->assertDatabaseHas('student_lifecycle_audits', [
            'student_id' => $this->student->id,
            'event' => 'student.mutated_out',
        ]);

        // Notifikasi wali (job berjalan sync karena queue=sync)
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $this->wali->id,
            'module' => 'student_lifecycle',
        ]);

        $notification = DB::table('notifications_universal')
            ->where('user_id', $this->wali->id)
            ->where('module', 'student_lifecycle')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString($this->student->name, (string) $notification->message);
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Notifikasi',
            'code' => 'UUNO',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '99998888', 'name' => 'Sekolah Notifikasi Uji']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 9,
            'name' => 'Kelas 9',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '9A',
            'code' => '9A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'nisn' => '999000111',
            'name' => 'Santri Notifikasi',
            'gender' => 'L',
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
            'join_date' => '2026-07-15',
            'attendance_number' => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $this->tu = User::create([
            'name' => 'TU Notifikasi Uji',
            'email' => 'tu.notif@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->tu->assignRole($role);

        $this->wali = User::create([
            'name' => 'Wali Santri Uji',
            'email' => 'wali.notif@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        WaliSantri::create([
            'user_id' => $this->wali->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'role' => 'wali',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
        ]);

        // Snapshot konteks sekolah untuk TU.
        $roleDimension = implode(',', $this->tu->fresh()->effectiveRoles()) ?: 'default';
        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $this->tu->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $this->tu->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->tu->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Staf Tata Usaha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
