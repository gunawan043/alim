<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\GradeLevel;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\Permission;
use App\Models\RaportRegistration;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tipe boarding satuan pendidikan + klasifikasi santri boarding/non-boarding,
 * serta data lengkap alumni (riwayat nilai & rapor dari arsip buku nilai).
 */
class BoardingAlumniDataTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $studyGroup;

    private Subject $subject;

    private User $admin;

    private Student $santriBoarding;

    private Student $santriNonBoarding;

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

    public function test_tipe_boarding_sekolah_default_dan_dapat_diubah(): void
    {
        $this->assertSame('non_boarding', $this->school->fresh()->boarding_type);

        $this->school->forceFill(['boarding_type' => 'both'])->save();
        $this->assertSame('both', $this->school->fresh()->boarding_type);

        // Nilai valid lain didukung.
        $this->school->forceFill(['boarding_type' => 'boarding'])->save();
        $this->assertSame('boarding', $this->school->fresh()->boarding_type);
    }

    public function test_klasifikasi_boarding_santri_tersimpan_dan_dapat_difilter(): void
    {
        $this->assertSame('boarding', $this->santriBoarding->fresh()->boarding_status);
        $this->assertSame('non_boarding', $this->santriNonBoarding->fresh()->boarding_status);

        // Filter daftar santri per status boarding.
        $this->actingAs($this->admin);
        $this->get("/{$this->admin->id}/students?boarding_status=boarding")
            ->assertOk()
            ->assertSee('Santri Boarding')
            ->assertDontSee('Santri Non Boarding');

        $this->get("/{$this->admin->id}/students?boarding_status=non_boarding")
            ->assertOk()
            ->assertSee('Santri Non Boarding')
            ->assertDontSee('Santri Boarding');
    }

    public function test_alumni_melihat_riwayat_nilai_dan_rapor(): void
    {
        $book = TeacherAdminBook::create([
            'teacher_id' => $this->admin->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $book->id,
            'student_id' => $this->santriBoarding->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'sts' => 80,
            'sas' => 85,
            'nr_final' => 88,
        ]);

        NilaiFormatif::create([
            'admin_book_id' => $book->id,
            'student_id' => $this->santriBoarding->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nr_final' => 90,
        ]);

        RaportRegistration::updateOrCreate(
            [
                'student_id' => $this->santriBoarding->id,
                'study_group_id' => $this->studyGroup->id,
                'academic_year_id' => $this->ay->id,
            ],
            [
                'semester' => 'ganjil',
                'homeroom_note' => 'Catatan alumni teladan.',
            ]
        );

        $alumni = new Alumni;
        $alumni->forceFill([
            'student_id' => $this->santriBoarding->id,
            'school_id' => $this->school->id,
            'graduation_year' => 2026,
        ])->save();

        $this->actingAs($this->admin)
            ->get("/{$this->admin->id}/alumni/{$alumni->id}")
            ->assertOk()
            ->assertSee('Riwayat Nilai & Rapor', false)
            ->assertSee('Matematika')
            ->assertSee('88')
            ->assertSee('Catatan alumni teladan.');
    }

    private function makeUser(string $name, string $email, string $jabatan = 'Guru Mapel', ?string $roleName = null): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName ?? 'Satuan Pendidikan', 'guard_name' => 'web'],
            ['level' => 8]
        );

        foreach (['impersonate_role', 'school-update', 'student-all-access', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
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

    private function makeStudent(string $name, string $nisn, string $boardingStatus): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'L',
            'boarding_status' => $boardingStatus,
        ]);
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Boarding',
            'code' => 'UU-BOARD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '34343434',
            'name' => 'Sekolah Uji Boarding',
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

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->subject = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);

        $this->admin = $this->makeUser('Admin Boarding', 'admin.boarding@test.local', 'Kepala Satuan Pendidikan', 'Kepala Sekolah');

        $this->santriBoarding = $this->makeStudent('Santri Boarding', '7770001111', 'boarding');
        $this->santriNonBoarding = $this->makeStudent('Santri Non Boarding', '7770001112', 'non_boarding');
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
            'permissions' => json_encode(['jadwalkbm.read', 'school-update']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
