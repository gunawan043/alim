<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Events\Boarding\HealthPermitApproved;
use App\Events\Boarding\LeaveReturned;
use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\AdminPresensiMapel;
use App\Models\AdminPresensiSiswa;
use App\Models\DormitoryPermit;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassHistory;
use App\Models\StudentHealthPermit;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AcademicAttendanceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tahap 3 — Kehadiran terhubung: asrama/UKS → presensi harian & mapel,
 * aktor absen kelas (wali/petugas) vs guru mapel, dan verifikasi kesesuaian.
 */
class AttendanceSyncKehadiranTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $studyGroup;

    private Subject $subject;

    private Student $student;

    private TeacherAdminBook $book;

    private User $wali;

    private User $guruMapel;

    private User $tu;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $this->seedFixture();
    }

    // ─────────────────────────────────────────────────────────────
    // SINKRONISASI UKS / ASRAMA
    // ─────────────────────────────────────────────────────────────

    public function test_uks_sakit_mengisi_presensi_harian_dan_mapel_otomatis(): void
    {
        $today = now()->format('Y-m-d');

        // Aktor wali kelas (tabel legacy mencatat recorded_by).
        $this->actingAs($this->wali);

        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_UPS, (string) Str::uuid(), 'sakit'
        );

        $harian = AdminPresensiHarian::where('student_id', $this->student->id)
            ->whereDate('attendance_date', $today)->firstOrFail();

        $this->assertSame('sakit', $harian->status);
        $this->assertTrue($harian->isAutoFilled());
        $this->assertSame('uks', $harian->source);
        $this->assertSame($this->studyGroup->id, $harian->study_group_id);

        // Presensi mapel pada jam pelajaran di hari yang sama ikut terisi.
        $mapel = AdminPresensiSiswa::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('sakit', $mapel->status);
        $this->assertTrue($mapel->isAutoFilled());

        // Tabel legacy (Mobile API) juga sinkron.
        $legacy = StudentAttendance::where('student_id', $this->student->id)
            ->whereDate('attendance_date', $today)->firstOrFail();
        $this->assertSame('sakit', $legacy->status);
    }

    public function test_izin_pulang_mengisi_izin_dan_health_permit_event_sync(): void
    {
        $today = now()->format('Y-m-d');

        // Izin pulang asrama → status "izin".
        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_BOARDING_LEAVE, (string) Str::uuid(), 'izin'
        );

        $harian = AdminPresensiHarian::where('student_id', $this->student->id)->firstOrFail();
        $this->assertSame('izin', $harian->status);

        // Event perizinan kesehatan → listener → presensi "sakit" (rantai event).
        AdminPresensiHarian::query()->delete();

        $permit = StudentHealthPermit::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'permit_type' => 'sakit_sedang',
            'description' => 'Dirawat di UKS',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'approved',
            'created_by' => $this->wali->id,
        ]);

        HealthPermitApproved::dispatch($permit, $this->student);

        $this->assertSame(
            'sakit',
            AdminPresensiHarian::where('student_id', $this->student->id)->firstOrFail()->status
        );
    }

    public function test_catatan_manual_wali_tidak_ditimpa_sinkronisasi(): void
    {
        $today = now()->format('Y-m-d');

        $manual = AdminPresensiHarian::create([
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'attendance_date' => $today,
            'student_id' => $this->student->id,
            'status' => 'hadir',
            'notes' => 'Dikonfirmasi wali kelas.',
            'auto_filled' => false,
        ]);

        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_UPS, (string) Str::uuid(), 'sakit'
        );

        $manual->refresh();
        $this->assertSame('hadir', $manual->status, 'Catatan manual wali kelas tidak boleh ditimpa.');
        $this->assertFalse($manual->isAutoFilled());
    }

    // ─────────────────────────────────────────────────────────────
    // AKTOR & VERIFIKASI
    // ─────────────────────────────────────────────────────────────

    public function test_hanya_wali_atau_petugas_yang_bisa_absen_kelas_guru_mapel_ditolak(): void
    {
        $today = now()->format('Y-m-d');

        // Guru mapel tidak boleh mengisi absensi kelas/harian.
        $this->actingAs($this->guruMapel)
            ->post("/{$this->guruMapel->id}/absensi/harian", [
                'study_group_id' => $this->studyGroup->id,
                'attendance_date' => $today,
                'semester' => 'ganjil',
                'records' => [
                    ['student_id' => $this->student->id, 'status' => 'hadir'],
                ],
            ])
            ->assertStatus(403);

        // Wali kelas boleh.
        $this->actingAs($this->wali)
            ->post("/{$this->wali->id}/absensi/harian", [
                'study_group_id' => $this->studyGroup->id,
                'attendance_date' => $today,
                'semester' => 'ganjil',
                'records' => [
                    ['student_id' => $this->student->id, 'status' => 'hadir'],
                ],
            ])
            ->assertStatus(302);
    }

    public function test_verifikasi_kesesuaian_hanya_wali_atau_petugas(): void
    {
        $today = now()->format('Y-m-d');

        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_UPS, (string) Str::uuid(), 'sakit'
        );

        $record = AdminPresensiHarian::where('student_id', $this->student->id)->firstOrFail();

        // Guru mapel bukan wali → ditolak.
        $this->actingAs($this->guruMapel)
            ->post("/{$this->guruMapel->id}/absensi/harian/{$record->id}/verifikasi")
            ->assertStatus(403);

        // Wali kelas → terverifikasi + audit.
        $this->actingAs($this->wali)
            ->post("/{$this->wali->id}/absensi/harian/{$record->id}/verifikasi")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertNotNull($record->fresh()->verified_at);
    }

    // ─────────────────────────────────────────────────────────────
    // KEPULANGAN & UI VERIFIKASI
    // ─────────────────────────────────────────────────────────────

    public function test_kepulangan_mengembalikan_status_hadir(): void
    {
        $today = now()->format('Y-m-d');
        $this->actingAs($this->wali);

        // Hari ini sakit (UKS) → lalu kembali dari izin.
        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_UPS, (string) Str::uuid(), 'sakit'
        );

        $permit = new DormitoryPermit([
            'student_id' => $this->student->id,
            'actual_return_datetime' => now(),
        ]);
        $permit->id = (string) Str::uuid();

        // Fokus rantai sinkronisasi kehadiran (listener boarding → presensi).
        app(\App\Listeners\Boarding\SyncBoardingLeaveToAttendance::class)
            ->handleReturn(new LeaveReturned($permit, $this->student, null));

        $harian = AdminPresensiHarian::where('student_id', $this->student->id)
            ->whereDate('attendance_date', $today)->firstOrFail();

        $this->assertSame('hadir', $harian->status, 'Kepulangan mengembalikan status hadir.');
        $this->assertSame('boarding_return', $harian->source);
    }

    public function test_halaman_presensi_menampilkan_badge_otomatis_dan_tombol_verifikasi(): void
    {
        $today = now()->format('Y-m-d');
        $this->actingAs($this->wali);

        app(AcademicAttendanceSyncService::class)->markAsAbsent(
            $this->student->id, $today, $today,
            AcademicAttendanceSyncService::SOURCE_UPS, (string) Str::uuid(), 'sakit'
        );

        $this->actingAs($this->wali)
            ->get("/{$this->wali->id}/absensi/harian/{$this->student->id}")
            ->assertOk()
            ->assertSee('Otomatis')
            ->assertSee('Verifikasi');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Mapel'): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedSnapshot($user);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => $jabatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Kehadiran',
            'code' => 'UU-HDR',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '12121212',
            'name' => 'Sekolah Uji Kehadiran',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->wali = $this->makeUser('Wali Kelas', 'wali@kehadiran.test');
        $this->guruMapel = $this->makeUser('Guru Mapel', 'guru.mapel@kehadiran.test');
        $this->tu = $this->makeUser('Staf Tata Usaha', 'tu@kehadiran.test', 'Staf Tata Usaha');

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
            'homeroom_teacher_id' => $this->wali->id,
        ]);

        $this->subject = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '9998887770',
            'name' => 'Santri Uji Kehadiran',
            'gender' => 'L',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->studyGroup->id,
            'is_active' => true,
        ]);

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/HDR/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Kehadiran',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $this->guruMapel->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->studyGroup->id,
            'subject_id' => $this->subject->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);

        $this->book = TeacherAdminBook::create([
            'teacher_id' => $this->guruMapel->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        AdminPresensiMapel::create([
            'admin_book_id' => $this->book->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'attendance_date' => now()->format('Y-m-d'),
            'status' => 'hadir',
        ]);
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
