<?php

namespace Tests\Feature;

use App\Http\Controllers\Arsip\ArsipInventarisController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiKelasController;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\ArchiveDocument;
use App\Models\GradeLevel;
use App\Models\NilaiSumatif;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\AcademicPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * SISTEM INVENTARISASI ARSIP:
 * - Halaman inventaris per tahun ajaran (hitung data per modul).
 * - Penutupan/pembukaan periode (lock/unlock) + audit log.
 * - Guard terpusat: periode terkunci menolak perubahan nilai & dokumen kurikulum.
 * - Snapshot nilai otomatis saat buku dikunci final (dokumen ber-hash).
 */
class ArsipInventarisTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $oldAy;

    private AcademicYear $activeAy;

    private GradeLevel $grade;

    private Subject $subject;

    private StudyGroup $oldRombel;

    private StudyGroup $activeRombel;

    private Student $oldStudent;

    private Student $activeStudent;

    private User $tu;

    private User $kepala;

    private User $editor;

    private User $guru;

    private TeacherAdminBook $oldBook;

    private TeacherAdminBook $activeBook;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Arsip', 'code' => 'UUA-INV',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '77788899', 'name' => 'Sekolah Uji Arsip Inventaris',
        ]);

        $this->oldAy = AcademicYear::create([
            'name' => '2024/2025', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);
        $this->activeAy = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'FIQ', 'name' => 'Fiqih',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $this->oldRombel = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->oldAy->id,
            'grade_level_id' => $this->grade->id, 'name' => '7A-ARSIP', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);
        $this->activeRombel = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->activeAy->id,
            'grade_level_id' => $this->grade->id, 'name' => '7A-AKTIF', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->oldStudent = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Lama', 'nisn' => '9990001111',
            'gender' => 'L', 'status' => 'active',
        ]);
        $this->activeStudent = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Aktif', 'nisn' => '9990001112',
            'gender' => 'P', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->oldStudent->id, 'study_group_id' => $this->oldRombel->id,
            'academic_year_id' => $this->oldAy->id, 'is_active' => false,
            'join_date' => '2024-07-01', 'leave_date' => '2025-06-30', 'attendance_number' => 1,
        ]);
        StudentClassHistory::create([
            'student_id' => $this->activeStudent->id, 'study_group_id' => $this->activeRombel->id,
            'academic_year_id' => $this->activeAy->id, 'is_active' => true,
            'join_date' => '2026-07-01', 'attendance_number' => 1,
        ]);

        $this->tu = $this->makeUser('TU Arsip', 'tu.arsip.inv@test.local', 'Staf Tata Usaha');
        $this->kepala = $this->makeUser('Kepala Arsip', 'kepala.arsip.inv@test.local', 'Kepala Satuan Pendidikan');
        $this->editor = $this->makeUser('Koor Kurikulum Arsip', 'koor.arsip.inv@test.local', 'Koordinator Kurikulum');
        $this->guru = $this->makeUser('Guru Arsip', 'guru.arsip.inv@test.local', 'Guru Umum');

        $this->oldBook = TeacherAdminBook::create([
            'teacher_id' => $this->guru->id, 'subject_id' => $this->subject->id,
            'study_group_id' => $this->oldRombel->id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->oldAy->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);
        $this->activeBook = TeacherAdminBook::create([
            'teacher_id' => $this->guru->id, 'subject_id' => $this->subject->id,
            'study_group_id' => $this->activeRombel->id, 'school_id' => $this->school->id,
            'academic_year_id' => $this->activeAy->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $this->oldBook->id, 'student_id' => $this->oldStudent->id,
            'academic_year_id' => $this->oldAy->id, 'semester' => 'ganjil', 'sts' => 80,
        ]);
        NilaiSumatif::create([
            'admin_book_id' => $this->activeBook->id, 'student_id' => $this->activeStudent->id,
            'academic_year_id' => $this->activeAy->id, 'semester' => 'ganjil', 'sts' => 85,
        ]);
    }

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'Satuan Pendidikan', 'guard_name' => 'web'],
            ['level' => 8]
        );

        foreach (['impersonate_role', 'school-update', 'student-all-access', 'teacher-attendance_manual', 'teacher-attendance_view'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $name, 'email' => $email, 'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => $jabatan,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user;
    }

    private function makeRequest(array $data = []): Request
    {
        $request = Request::create('/arsip-inventaris', 'GET', $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => auth()->user());

        return $request;
    }

    // ─────────────────────────────────────────────────────────────
    // INVENTARIS PER PERIODE
    // ─────────────────────────────────────────────────────────────

    public function test_inventaris_menghitung_data_per_modul_pada_periode(): void
    {
        $this->actingAs($this->tu);

        $view = app(ArsipInventarisController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]), $this->tu->id);
        $data = $view->getData();

        $modules = collect($data['modules'])->keyBy('key');

        $this->assertSame(1, $modules['rombel']['count'], 'Hanya rombel periode ini yang dihitung.');
        $this->assertSame(1, $modules['penempatan_santri']['count']);
        $this->assertSame(1, $modules['buku_nilai']['count'], 'Buku nilai TA lama terhitung pada periode lama.');
        $this->assertSame(1, $modules['nilai_sumatif']['count']);
        $this->assertSame(0, $modules['kurikulum_atp']['count']);

        $this->assertGreaterThan(0, $data['overview']['filled']);
        $this->assertSame(0, $data['overview']['documents']);
        $this->assertNotEmpty($data['unsupportedModules']);
    }

    public function test_halaman_inventaris_arsip_punya_akses_terbatas(): void
    {
        $this->actingAs($this->tu)
            ->get("/{$this->tu->id}/arsip")
            ->assertOk()
            ->assertSee('Inventaris Arsip');

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/arsip")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // PENUTUPAN & PEMBUKAAN PERIODE
    // ─────────────────────────────────────────────────────────────

    public function test_kepala_dapat_menutup_dan_membuka_periode_dengan_audit(): void
    {
        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$this->oldAy->id}/kunci", [
                'lock_notes' => 'Ditutup setelah rapor akhir semester.',
            ])
            ->assertRedirect();

        $this->assertNotNull($this->oldAy->fresh()->locked_at);
        $this->assertSame($this->kepala->id, $this->oldAy->fresh()->locked_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ACADEMIC_YEAR_LOCKED',
            'table_name' => 'academic_years',
            'record_id' => $this->oldAy->id,
        ]);

        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$this->oldAy->id}/buka", [
                'lock_notes' => 'Koreksi hasil verifikasi rapor.',
            ])
            ->assertRedirect();

        $this->assertNull($this->oldAy->fresh()->locked_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ACADEMIC_YEAR_UNLOCKED',
            'record_id' => $this->oldAy->id,
        ]);
    }

    public function test_tahun_ajaran_aktif_tidak_dapat_dikunci(): void
    {
        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$this->activeAy->id}/kunci")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($this->activeAy->fresh()->locked_at);
    }

    public function test_tu_tidak_dapat_menutup_periode(): void
    {
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->oldAy->id}/kunci")
            ->assertStatus(403);

        $this->assertNull($this->oldAy->fresh()->locked_at);
    }

    public function test_ta_terkunci_tidak_dapat_diaktifkan_kembali(): void
    {
        app(AcademicPeriodService::class)->lock($this->oldAy, $this->kepala);

        $this->actingAs($this->kepala)
            ->get("/{$this->kepala->id}/academic-years/{$this->oldAy->id}/toggle-active")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse((bool) $this->oldAy->fresh()->is_active);
        $this->assertTrue($this->oldAy->fresh()->isLocked());
    }

    // ─────────────────────────────────────────────────────────────
    // GUARD: PERIODE TERKUNCI MENOLAK PERUBAHAN
    // ─────────────────────────────────────────────────────────────

    public function test_periode_terkunci_menolak_perubahan_nilai_meski_diaktifkan(): void
    {
        app(AcademicPeriodService::class)->lock($this->oldAy, $this->kepala);

        // Simulasi kekeliruan: TA terkunci diaktifkan kembali di level data.
        AcademicYear::query()->update(['is_active' => false]);
        $this->oldAy->forceFill(['is_active' => true])->save();

        $this->actingAs($this->tu);

        $request = $this->makeRequest([
            'tab' => 'leger',
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'leger_sts' => [
                $this->oldStudent->id => [
                    (string) $this->oldBook->id => ['sts' => 95],
                ],
            ],
        ]);

        try {
            app(NilaiKelasController::class)->stsStore($request, $this->tu->id, $this->oldRombel->id);
            $this->fail('Periode terkunci seharusnya menolak perubahan nilai.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertDatabaseHas('admin_nilai_sumatif', [
            'admin_book_id' => $this->oldBook->id,
            'student_id' => $this->oldStudent->id,
            'sts' => 80,
        ]);

        // Setelah kunci dibuka (masih TA aktif) → perubahan kembali diizinkan.
        app(AcademicPeriodService::class)->unlock($this->oldAy->fresh(), $this->kepala);

        $request2 = $this->makeRequest([
            'tab' => 'leger',
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'leger_sts' => [
                $this->oldStudent->id => [
                    (string) $this->oldBook->id => ['sts' => 95],
                ],
            ],
        ]);

        app(NilaiKelasController::class)->stsStore($request2, $this->tu->id, $this->oldRombel->id);

        $this->assertDatabaseHas('admin_nilai_sumatif', [
            'admin_book_id' => $this->oldBook->id,
            'student_id' => $this->oldStudent->id,
            'sts' => 95,
        ]);
    }

    public function test_periode_terkunci_menolak_perubahan_dokumen_kurikulum(): void
    {
        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->oldAy->id,
            'semester' => 'ganjil',
            'subject_id' => $this->subject->id,
            'grade_level_id' => $this->grade->id,
            'teacher_id' => $this->editor->id,
            'status' => 'draft',
            'created_by' => $this->editor->id,
            'total_jp' => 0,
        ]);

        // Sebelum dikunci → dokumen TA mendatang/lama masih dapat disiapkan.
        $this->actingAs($this->editor)
            ->put("/{$this->editor->id}/kurikulum/atp/{$atp->id}", ['status' => 'draft', 'catatan' => 'revisi awal'])
            ->assertRedirect();

        app(AcademicPeriodService::class)->lock($this->oldAy, $this->kepala);

        // Setelah dikunci → perubahan dokumen ditolak meski masih bisa dilihat.
        $this->actingAs($this->editor)
            ->put("/{$this->editor->id}/kurikulum/atp/{$atp->id}", ['status' => 'published', 'catatan' => 'coba publish'])
            ->assertStatus(403);

        $this->assertSame('draft', $atp->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // SNAPSHOT DOKUMEN NILAI
    // ─────────────────────────────────────────────────────────────

    public function test_snapshot_nilai_dibuat_saat_buku_dikunci(): void
    {
        Storage::fake('local');
        $this->actingAs($this->kepala);

        $view = app(NilaiController::class)->lockBook(
            $this->makeRequest([]),
            $this->kepala->id,
            (string) $this->activeBook->id,
        );
        $this->assertTrue($view->isRedirect());

        $document = ArchiveDocument::where('type', 'snapshot_nilai')
            ->where('record_id', $this->activeBook->id)
            ->first();

        $this->assertNotNull($document, 'Snapshot nilai harus dibuat saat buku dikunci.');
        $this->assertSame($this->activeAy->id, $document->academic_year_id);
        $this->assertSame($this->activeRombel->id, $document->study_group_id);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));

        $contents = Storage::disk('local')->get($document->file_path);
        $this->assertSame(hash('sha256', $contents), $document->file_hash, 'Hash dokumen harus sesuai isi berkas.');
        $this->assertStringContainsString('Santri Aktif', $contents);
        $this->assertStringContainsString('"sts": 85', $contents);
    }
}
