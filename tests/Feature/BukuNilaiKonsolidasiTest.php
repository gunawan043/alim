<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Jobs\ProvisionStudyGroupSubjectAcademicStructureJob;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tahap 3 — Buku Nilai Konsolidasi:
 *  - Satu buku per konteks (mapel+rombel+TA+semester); multi-guru berbagi.
 *  - Nilai final dikunci + audit; hanya kurikulum membuka kunci.
 *  - Rapor baca-saja: catatan hanya wali kelas / tim kurikulum.
 */
class BukuNilaiKonsolidasiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $studyGroup;

    private Subject $subject;

    private User $guruA;

    private User $guruB;

    private User $kurikulum;

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
    // SATU BUKU PER KONTEKS + CO-TEACHER
    // ─────────────────────────────────────────────────────────────

    public function test_provisi_satu_buku_per_konteks_dan_akses_co_teacher(): void
    {
        $sgsA = (string) Str::uuid();
        $sgsB = (string) Str::uuid();

        // Dua guru pada mapel+rombel+TA yang sama → satu buku.
        (new ProvisionStudyGroupSubjectAcademicStructureJob(
            $sgsA, $this->studyGroup->id, $this->subject->id,
            $this->guruA->id, $this->school->id, $this->ay->id, $this->grade->id
        ))->handle();

        (new ProvisionStudyGroupSubjectAcademicStructureJob(
            $sgsB, $this->studyGroup->id, $this->subject->id,
            $this->guruB->id, $this->school->id, $this->ay->id, $this->grade->id
        ))->handle();

        $books = TeacherAdminBook::where('subject_id', $this->subject->id)
            ->where('study_group_id', $this->studyGroup->id)
            ->where('academic_year_id', $this->ay->id)
            ->get();

        $this->assertCount(1, $books, 'Satu buku per konteks akademik.');
        $this->assertSame($this->guruA->id, $books->first()->teacher_id, 'Penyusun pertama tetap tercatat.');

        // Co-teacher (guru B) dapat membuka buku yang sama.
        $book = $books->first();

        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/schools/guru-mapel")
            ->assertOk();

        // Halaman wizard (w1) memakai scope buku yang sama.
        $this->actingAs($this->guruB)
            ->get("/{$this->guruB->id}/schools/guru-mapel/{$book->id}/w1")
            ->assertOk();


        // Guru tanpa penugasan tidak dapat membuka.
        $guruLain = $this->makeUser('Guru Lain', 'guru.lain@nilai.test');
        $this->actingAs($guruLain)
            ->get("/{$guruLain->id}/schools/guru-mapel/{$book->id}/w1")
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────
    // KUNCI NILAI FINAL
    // ─────────────────────────────────────────────────────────────

    public function test_kunci_nilai_memblokir_input_dan_hanya_kurikulum_membuka(): void
    {
        $this->provisionBook();
        $book = TeacherAdminBook::firstOrFail();

        // Guru biasa tidak boleh mengunci.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/nilai/{$book->id}/kunci")
            ->assertStatus(403);

        // Kurikulum mengunci.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/schools/nilai/{$book->id}/kunci")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $book->refresh();
        $this->assertTrue($book->isLocked());
        $this->assertNotNull($book->locked_at);

        // Input guru terblokir saat terkunci.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/guru-mapel/{$book->id}/autosave")
            ->assertStatus(403);

        // Guru tidak boleh membuka kunci.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/nilai/{$book->id}/buka-kunci")
            ->assertStatus(403);

        // Kurikulum membuka kunci + audit.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/schools/nilai/{$book->id}/buka-kunci")
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertFalse($book->fresh()->isLocked());
        $this->assertTrue(AuditLog::where('action', 'book_locked')->exists());
        $this->assertTrue(AuditLog::where('action', 'book_unlocked')->exists());

        // Input kembali tersedia (tidak lagi 403 terblokir kunci).
        $afterUnlock = $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/guru-mapel/{$book->id}/autosave");
        $this->assertNotSame(403, $afterUnlock->getStatusCode(), 'Pasca-unlock tidak boleh diblokir kunci.');
    }

    // ─────────────────────────────────────────────────────────────
    // NAUK KELAS: ARSIP TAHUN LALU & BUKU BARU KOSONG
    // ─────────────────────────────────────────────────────────────

    public function test_buku_tahun_ajaran_lalu_arsip_dan_tahun_baru_kosong(): void
    {
        // Buku tahun ajaran lama (tidak aktif) + satu nilai tersimpan.
        $oldYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => false,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        $oldBook = TeacherAdminBook::create([
            'teacher_id' => $this->guruA->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $oldYear->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $studentId,
            'school_id' => $this->school->id,
            'nisn' => '1112223334',
            'name' => 'Santri Arsip Nilai',
            'gender' => 'L',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\NilaiSumatif::create([
            'admin_book_id' => $oldBook->id,
            'student_id' => $studentId,
            'academic_year_id' => $oldYear->id,
            'semester' => 'ganjil',
            'sts' => 85,
        ]);

        // Tahun ajaran lama: data tersimpan, tetapi tidak dapat diubah (arsip).
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/guru-mapel/{$oldBook->id}/autosave")
            ->assertStatus(403);

        $this->assertNotNull(TeacherAdminBook::find($oldBook->id), 'Buku lama tetap tersimpan sebagai arsip.');
        $this->assertSame(1, $oldBook->nilaiSumatifs()->count());

        // Tahun ajaran baru: provisi membuat buku KOSONG untuk guru/kelas berikutnya.
        (new ProvisionStudyGroupSubjectAcademicStructureJob(
            (string) Str::uuid(), $this->studyGroup->id, $this->subject->id,
            $this->guruB->id, $this->school->id, $this->ay->id, $this->grade->id
        ))->handle();

        $newBook = TeacherAdminBook::where('academic_year_id', $this->ay->id)
            ->where('subject_id', $this->subject->id)
            ->where('study_group_id', $this->studyGroup->id)
            ->firstOrFail();

        $this->assertSame(0, $newBook->nilaiSumatifs()->count(), 'Buku tahun ajaran baru dimulai kosong.');
        $this->assertFalse($newBook->isLocked());
    }

    // ─────────────────────────────────────────────────────────────
    // RAPOR BACA-SAJA
    // ─────────────────────────────────────────────────────────────

    public function test_catatan_rapor_hanya_wali_atau_kurikulum(): void
    {
        $studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $studentId,
            'school_id' => $this->school->id,
            'nisn' => '1234567890',
            'name' => 'Santri Uji Nilai',
            'gender' => 'L',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('student_class_histories')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $studentId,
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $this->ay->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Guru pengampu tetapi bukan wali dan bukan kurikulum → ditolak.
        $this->actingAs($this->guruA)
            ->post("/{$this->guruA->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$studentId}/catatan", [
                'homeroom_note' => 'Catatan tidak sah',
            ])
            ->assertStatus(403);

        // Wali kelas ditetapkan → boleh menyimpan catatan.
        $this->studyGroup->forceFill(['homeroom_teacher_id' => $this->guruB->id])->save();

        $this->actingAs($this->guruB)
            ->post("/{$this->guruB->id}/schools/nilai-kelas/{$this->studyGroup->id}/rapor/{$studentId}/catatan", [
                'homeroom_note' => 'Catatan wali kelas.',
            ])
            ->assertStatus(302);

        $this->assertTrue(
            DB::table('raport_registrations')
                ->where('student_id', $studentId)
                ->where('study_group_id', $this->studyGroup->id)
                ->exists(),
            'Catatan rapor tersimpan untuk wali kelas.'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    public function test_nilai_murni_sts_wajib_sebelum_kolom_sts_diisi(): void
    {
        $this->provisionBook();
        $book = TeacherAdminBook::firstOrFail();

        $studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $studentId,
            'school_id' => $this->school->id,
            'nisn' => '9998887776',
            'name' => 'Santri Uji Murni',
            'gender' => 'L',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = "/{$this->guruA->id}/schools/guru-mapel/{$book->id}/w3";

        // STS diisi tanpa Nilai Murni STS → ditolak dengan pesan aturan.
        $this->actingAs($this->guruA)
            ->post($url, ['sumatif' => [$studentId => ['sts' => 80]]])
            ->assertSessionHasErrors("sumatif.{$studentId}.sts");

        // Isi Nilai Murni lebih dahulu → tersimpan.
        $this->actingAs($this->guruA)
            ->post($url, ['sumatif' => [$studentId => ['sts_murni' => 85, 'sts' => 85]]])
            ->assertSessionHasNoErrors();

        $row = \App\Models\NilaiSumatif::where('admin_book_id', $book->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $this->assertSame(85.0, (float) $row->sts_murni);
        $this->assertSame(85.0, (float) $row->sts);
    }

    public function test_tambah_kolom_sumatif_dengan_id_aman_tersimpan(): void
    {
        $this->provisionBook();
        $book = TeacherAdminBook::firstOrFail();

        $columns = app(\App\Services\SumatifHarianService::class)->saveColumns($book, [
            ['id' => 'col-custom-7', 'label' => 'Ulangan 7'],
        ]);

        $ids = collect($columns)->pluck('id')->all();
        $this->assertContains('col-custom-7', $ids, 'Kolom tambahan dengan id aman harus tersimpan.');
        $this->assertNotEmpty($book->fresh()->sumatif_columns);
    }

    private function provisionBook(): void
    {
        (new ProvisionStudyGroupSubjectAcademicStructureJob(
            (string) Str::uuid(), $this->studyGroup->id, $this->subject->id,
            $this->guruA->id, $this->school->id, $this->ay->id, $this->grade->id
        ))->handle();
    }

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
            'name' => 'Unit Uji Buku Nilai',
            'code' => 'UU-NILAI',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '55556666',
            'name' => 'Sekolah Uji Buku Nilai',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $this->subject = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 4, 'is_active' => true]);

        $this->guruA = $this->makeUser('Guru Buku A', 'guru.buku.a@nilai.test');
        $this->guruB = $this->makeUser('Guru Buku B', 'guru.buku.b@nilai.test');
        $this->kurikulum = $this->makeUser('Koor Kurikulum Nilai', 'kurikulum.nilai@nilai.test', 'Koordinator Kurikulum');

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/NILAI/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Buku Nilai',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->guruA, $this->guruB] as $teacher) {
            TeachingAssignment::create([
                'decree_id' => $decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->studyGroup->id,
                'subject_id' => $this->subject->id,
                'weekly_hours' => 4,
                'status' => 'active',
            ]);
        }
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
