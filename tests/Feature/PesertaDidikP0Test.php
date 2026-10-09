<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMahrom;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentPromotion;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase 1 — Perbaikan P0 modul Peserta Didik:
 * alumni otomatis, lifecycle status manual, pindah santri, mutasi masuk
 * tanpa duplikasi, enum status, wizard naik kelas, scope sekolah,
 * route wali/foto, alumni export & verifikasi.
 */
class PesertaDidikP0Test extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $groupA;

    private StudyGroup $groupB;

    private User $user;

    private Student $student;

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
    // ALUMNI OTOMATIS DARI JALUR LULUS
    // ─────────────────────────────────────────────────────────────

    public function test_alumni_dibuat_otomatis_saat_kelulusan_via_mutasi_out(): void
    {
        $mutation = StudentMutationOut::create([
            'student_id' => $this->student->id,
            'school_id' => $this->schoolA->id,
            'out_type' => 'graduation',
            'status' => 'submitted',
            'student_name' => $this->student->name,
            'student_nisn' => $this->student->nisn,
            'graduation_year' => 2027,
            'graduation_certificate_number' => 'IJZ-2027-001',
            'established_date' => '2027-06-20',
        ]);

        $this->actingAs($this->user)
            ->post("/{$this->user->id}/mutations-lulus/{$mutation->id}/approve")
            ->assertStatus(302);

        $this->assertDatabaseHas('alumni', [
            'student_id' => $this->student->id,
            'graduation_certificate_number' => 'IJZ-2027-001',
        ]);
        $this->assertSame('graduate', $this->student->fresh()->status);
        $this->assertSame(0, StudentClassHistory::where('student_id', $this->student->id)->where('is_active', true)->count());
    }

    public function test_perubahan_status_manual_memicu_alumni_dan_menutup_rombel(): void
    {
        $response = $this->actingAs($this->user)->put("/{$this->user->id}/students/{$this->student->id}", [
            'school_id' => $this->schoolA->id,
            'nisn' => $this->student->nisn,
            'name' => $this->student->name,
            'gender' => 'L',
            'status' => 'graduate',
            'graduation_year' => 2027,
            'graduation_date' => '2027-06-20',
        ]);

        $response->assertStatus(302);

        $this->assertSame('graduate', $this->student->fresh()->status);
        $this->assertDatabaseHas('alumni', ['student_id' => $this->student->id]);
        $this->assertSame(0, StudentClassHistory::where('student_id', $this->student->id)->where('is_active', true)->count());
        $this->assertDatabaseHas('student_lifecycle_audits', [
            'student_id' => $this->student->id,
            'event' => 'student.status_changed',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // PINDAH SANTRI (student-move)
    // ─────────────────────────────────────────────────────────────

    public function test_pindah_santri_memindahkan_history_tanpa_duplikasi(): void
    {
        $historyId = StudentClassHistory::where('student_id', $this->student->id)->value('id');

        $this->actingAs($this->user)
            ->post("/{$this->user->id}/student-move", [
                'source_study_group_id' => $this->groupA->id,
                'destination_study_group_id' => $this->groupB->id,
                'student_ids' => [$this->student->id],
                'move_date' => now()->toDateString(),
                'notes' => 'Uji pindah rombel',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        // Baris history yang SAMA dipindahkan (tidak melanggar unique student+academic_year).
        $history = StudentClassHistory::find($historyId);
        $this->assertSame($this->groupB->id, $history->study_group_id);
        $this->assertTrue((bool) $history->is_active);
        $this->assertSame(1, StudentClassHistory::where('student_id', $this->student->id)->count());

        $this->assertDatabaseHas('student_lifecycle_audits', [
            'student_id' => $this->student->id,
            'event' => 'student.moved_rombel',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // MUTASI MASUK UNTUK SANTRI TERDAFTAR (TIDAK DUPLIKAT)
    // ─────────────────────────────────────────────────────────────

    public function test_mutasi_masuk_santri_terdaftar_memperbarui_bukan_menduplikasi(): void
    {
        $countBefore = Student::count();

        $mutation = StudentMutationIn::create([
            'student_id' => $this->student->id,
            'school_id' => $this->schoolA->id,
            'student_name' => $this->student->name,
            'student_nisn' => $this->student->nisn,
            'status' => 'submitted',
            'established_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post("/{$this->user->id}/mutations-in/{$mutation->id}/approve")
            ->assertStatus(302);

        $this->assertSame($countBefore, Student::count(), 'Mutasi masuk untuk santri terdaftar tidak boleh membuat baris Student baru.');
        $this->assertSame('approved', $mutation->fresh()->status);
        $this->assertSame($this->student->id, $mutation->fresh()->student_id);
    }

    // ─────────────────────────────────────────────────────────────
    // ENUM STATUS TRANSFER
    // ─────────────────────────────────────────────────────────────

    public function test_status_transfer_in_dapat_disimpan(): void
    {
        $this->actingAs($this->user)->put("/{$this->user->id}/students/{$this->student->id}", [
            'school_id' => $this->schoolA->id,
            'nisn' => $this->student->nisn,
            'name' => $this->student->name,
            'gender' => 'L',
            'status' => 'transfer_in',
        ])->assertStatus(302);

        $this->assertSame('transfer_in', $this->student->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPE SEKOLAH
    // ─────────────────────────────────────────────────────────────

    public function test_santri_sekolah_lain_tidak_dapat_dibuka(): void
    {
        $studentB = $this->makeStudent($this->schoolB, 'Santri Sekolah B', '999000111');

        $this->actingAs($this->user)
            ->get("/{$this->user->id}/students/{$studentB->id}")
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->get("/{$this->user->id}/students/{$studentB->id}/edit")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────
    // WIZARD NAIK KELAS
    // ─────────────────────────────────────────────────────────────

    public function test_wizard_naik_kelas_memuat_url_ajax_ber_prefix_user(): void
    {
        $this->actingAs($this->user)
            ->get("/{$this->user->id}/student-promotions/create")
            ->assertOk()
            ->assertSee('/'.$this->user->id.'/api/grade-levels/by-academic-year/', false)
            ->assertSee('/'.$this->user->id.'/api/study-groups/', false);
    }

    public function test_promosi_cancelled_tidak_dapat_dieksekusi(): void
    {
        $promotion = StudentPromotion::create([
            'from_academic_year_id' => $this->ay->id,
            'to_academic_year_id' => $this->ay->id,
            'from_study_group_id' => $this->groupA->id,
            'to_study_group_id' => $this->groupB->id,
            'promotion_date' => now()->toDateString(),
            'status' => 'cancelled',
        ]);

        $this->actingAs($this->user)
            ->put("/{$this->user->id}/student-promotions/{$promotion->id}/execute", ['confirmed' => 1])
            ->assertStatus(302)
            ->assertSessionHas('error');

        $this->assertSame('cancelled', $promotion->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // BULK PROMOTION — MUTASI KELUAR
    // ─────────────────────────────────────────────────────────────

    public function test_bulk_promotion_mutasi_keluar_memakai_status_transfer_out(): void
    {
        $this->actingAs($this->user)
            ->post("/{$this->user->id}/bulk-promotion/promote", [
                'from_study_group_id' => $this->groupA->id,
                'to_academic_year_id' => $this->ay->id,
                'promotion_date' => now()->toDateString(),
                'student_ids' => [$this->student->id],
                'student_actions' => [$this->student->id => 'mutate_out'],
            ])
            ->assertStatus(302);

        $this->assertSame('transfer_out', $this->student->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // ALUMNI: EXPORT, VERIFIKASI, EDIT
    // ─────────────────────────────────────────────────────────────

    public function test_export_pdf_alumni_berfungsi(): void
    {
        $this->makeAlumni($this->student);

        $response = $this->actingAs($this->user)
            ->get("/{$this->user->id}/alumni/export?format=pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_verifikasi_alumni_dan_edit_tidak_menurunkan_status(): void
    {
        $alumni = $this->makeAlumni($this->student, 'filled');

        $this->actingAs($this->user)
            ->post("/{$this->user->id}/alumni/{$alumni->id}/verify")
            ->assertStatus(302);

        $this->assertSame('verified', $alumni->fresh()->tracer_status);

        $this->actingAs($this->user)->put("/{$this->user->id}/alumni/{$alumni->id}", [
            'continuing_study_status' => 'belum',
            'working_status' => 'belum',
        ])->assertStatus(302);

        $this->assertSame('verified', $alumni->fresh()->tracer_status, 'Status verified tidak boleh turun menjadi filled.');
    }

    // ─────────────────────────────────────────────────────────────
    // WALI, FOTO, MAHROM
    // ─────────────────────────────────────────────────────────────

    public function test_halaman_wali_dan_upload_foto_dapat_diakses(): void
    {
        $this->actingAs($this->user)
            ->get("/{$this->user->id}/students/{$this->student->id}/wali/edit")
            ->assertOk();

        $this->actingAs($this->user)
            ->post("/{$this->user->id}/students/{$this->student->id}/photo", [
                'photo' => UploadedFile::fake()->image('santri.jpg', 120, 120),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($this->student->fresh()->photo_path);
    }

    public function test_filter_status_mahrom_global_berfungsi(): void
    {
        StudentMahrom::create([
            'student_id' => $this->student->id,
            'name' => 'Mahrom Aktif Uji',
            'relationship' => 'ayah',
            'is_primary' => true,
            'is_active' => true,
        ]);
        StudentMahrom::create([
            'student_id' => $this->student->id,
            'name' => 'Mahrom Nonaktif Uji',
            'relationship' => 'paman',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->get("/{$this->user->id}/students/mahroms?status=active")
            ->assertOk()
            ->assertSee('Mahrom Aktif Uji')
            ->assertDontSee('Mahrom Nonaktif Uji');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function makeStudent(School $school, string $name, string $nisn): Student
    {
        return Student::create([
            'school_id' => $school->id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => 'L',
            'status' => 'active',
        ]);
    }

    private function makeAlumni(Student $student, string $tracerStatus = 'pending'): Alumni
    {
        return Alumni::create([
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'graduation_year' => 2027,
            'graduation_date' => '2027-06-20',
            'tracer_status' => $tracerStatus,
        ]);
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Uji Peserta Didik',
            'code' => 'UUPD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolA = School::create(['work_unit_id' => $workUnitId, 'npsn' => '11112222', 'name' => 'Sekolah A Uji']);
        $this->schoolB = School::create(['work_unit_id' => $workUnitId, 'npsn' => '33334444', 'name' => 'Sekolah B Uji']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->schoolA->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->groupA = StudyGroup::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $this->groupB = StudyGroup::create([
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7B',
            'code' => '7B',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->student = $this->makeStudent($this->schoolA, 'Santri Uji P0', '888000111');

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
            'join_date' => '2026-07-15',
            'attendance_number' => 1,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        foreach (['impersonate_role', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->user = User::create([
            'name' => 'TU Peserta Didik Uji',
            'email' => 'tu.pd@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->user->assignRole($role);
        $this->seedSnapshot($this->user);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $this->user->id,
                'school_id' => $this->schoolA->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Tenaga Kependidikan',
                'jabatan' => 'Staf Tata Usaha',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function seedSnapshot(User $user): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->schoolA->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->schoolA->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
