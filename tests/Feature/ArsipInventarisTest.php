<?php

namespace Tests\Feature;

use App\Http\Controllers\Arsip\ArsipInventarisController;
use App\Http\Controllers\Kurikulum\CapaianPembelajaranController;
use App\Http\Controllers\Kurikulum\KurikulumController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiKelasController;
use App\Models\AbsensiGtk;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\ArchiveDocument;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GtkProfile;
use App\Models\NilaiSumatif;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\Arsip\RaporPdfService;
use App\Support\ArabicText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    // ─────────────────────────────────────────────────────────────
    // V2 — SNAPSHOT RAPOR PDF, VERSI CP, GUARD ABSENSI GTK
    // ─────────────────────────────────────────────────────────────

    public function test_arsipkan_rapor_rombel_menyimpan_pdf_ber_hash(): void
    {
        Storage::fake('local');

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/rapor", [
                'study_group_id' => $this->activeRombel->id,
                'semester' => 'ganjil',
                'jenis' => 'sas',
                'bahasa' => 'id',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $document = ArchiveDocument::where('type', 'rapor')
            ->where('student_id', $this->activeStudent->id)
            ->first();

        $this->assertNotNull($document, 'PDF rapor harus tersimpan di registri dokumen arsip.');
        $this->assertSame($this->activeAy->id, $document->academic_year_id);
        $this->assertSame($this->activeRombel->id, $document->study_group_id);
        $this->assertSame('sas', $document->jenis);
        $this->assertSame('id', $document->language);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));

        $contents = Storage::disk('local')->get($document->file_path);
        $this->assertStringStartsWith('%PDF', $contents, 'Berkas arsip harus berupa PDF.');
        $this->assertSame(hash('sha256', $contents), $document->file_hash);

        // Idempoten: menjalankan ulang tidak menggandakan dokumen.
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/rapor", [
                'study_group_id' => $this->activeRombel->id,
                'semester' => 'ganjil',
                'jenis' => 'sas',
                'bahasa' => 'id',
            ])
            ->assertRedirect();

        $this->assertSame(1, ArchiveDocument::where('type', 'rapor')
            ->where('student_id', $this->activeStudent->id)
            ->count());
    }

    public function test_arsipkan_rapor_empat_varian_jenis_dan_bahasa(): void
    {
        Storage::fake('local');

        $payload = [
            'study_group_id' => $this->activeRombel->id,
            'semester' => 'ganjil',
            'jenis' => 'all',
            'bahasa' => 'all',
        ];

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/rapor", $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $documents = ArchiveDocument::where('type', 'rapor')
            ->where('student_id', $this->activeStudent->id)
            ->get();

        $this->assertCount(4, $documents, 'Harus ada 4 varian: STS/SAS × Indonesia/Arab.');
        $this->assertEqualsCanonicalizing(['sts', 'sas'], $documents->pluck('jenis')->unique()->values()->all());
        $this->assertEqualsCanonicalizing(['id', 'ar'], $documents->pluck('language')->unique()->values()->all());

        foreach ($documents as $document) {
            $this->assertTrue(Storage::disk('local')->exists($document->file_path));
            $contents = Storage::disk('local')->get($document->file_path);
            $this->assertStringStartsWith('%PDF', $contents);
            $this->assertSame(hash('sha256', $contents), $document->file_hash);
        }

        $indonesia = $documents->firstWhere('language', 'id');
        $arab = $documents->firstWhere('language', 'ar');

        $this->assertStringContainsString('Indonesia', $indonesia->title);
        $this->assertStringContainsString('Arab', $arab->title);
        $this->assertNotSame($indonesia->file_hash, $arab->file_hash, 'PDF Indonesia & Arab harus berbeda.');

        // Shaping Arab: bentuk presentasi (FE70–FEFF) menghasilkan urutan visual yang berbeda.
        $this->assertTrue(ArabicText::hasArabic('الاسم'));
        $shaped = ArabicText::shape('الاسم');
        $this->assertNotSame('الاسم', $shaped);
        $this->assertMatchesRegularExpression('/[\x{FE70}-\x{FEFF}]/u', $shaped);

        // Idempoten untuk seluruh varian.
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/rapor", $payload)
            ->assertRedirect();

        $this->assertSame(4, ArchiveDocument::where('type', 'rapor')
            ->where('student_id', $this->activeStudent->id)
            ->count());
    }

    public function test_arsipkan_leger_rombel_menyimpan_pdf_ber_hash(): void
    {
        Storage::fake('local');

        $payload = [
            'study_group_id' => $this->activeRombel->id,
            'semester' => 'ganjil',
            'jenis' => 'sas',
        ];

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/leger", $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $document = ArchiveDocument::where('type', 'leger')
            ->where('study_group_id', $this->activeRombel->id)
            ->first();

        $this->assertNotNull($document, 'Leger PDF harus tersimpan di registri dokumen arsip.');
        $this->assertSame('sas', $document->jenis);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));

        $contents = Storage::disk('local')->get($document->file_path);
        $this->assertStringStartsWith('%PDF', $contents);
        $this->assertSame(hash('sha256', $contents), $document->file_hash);
        $this->assertStringContainsString('Leger', $document->title);

        // Idempoten per TA+rombel+semester+jenis.
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/arsip/{$this->activeAy->id}/leger", $payload)
            ->assertRedirect();

        $this->assertSame(1, ArchiveDocument::where('type', 'leger')
            ->where('study_group_id', $this->activeRombel->id)
            ->count());
    }

    public function test_cp_berversi_tahun_ajaran_dan_disalin_saat_siapkan(): void
    {
        $targetAy = AcademicYear::create([
            'name' => '2027/2028', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2027-07-01', 'end_date' => '2028-06-30',
        ]);

        $cpOld = CapaianPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'academic_year_id' => $this->oldAy->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'CP versi TA lama.',
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->editor->id,
        ]);

        // Filter TA: CP versi TA aktif TIDAK boleh terlihat sebagai milik TA lama, dst.
        $visibleOld = CapaianPembelajaran::query()->forAcademicYear($this->oldAy->id)->pluck('id');
        $visibleActive = CapaianPembelajaran::query()->forAcademicYear($this->activeAy->id)->pluck('id');

        $this->assertTrue($visibleOld->contains($cpOld->id));
        $this->assertFalse($visibleActive->contains($cpOld->id));

        // Controller index menghormati periode.
        $this->actingAs($this->editor);
        $viewOld = app(CapaianPembelajaranController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]), $this->editor->id)
            ->getData();
        $this->assertTrue($viewOld['cpList']->contains('id', $cpOld->id));
        $this->assertTrue($viewOld['isArchiveAy']);

        $viewActive = app(CapaianPembelajaranController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->activeAy->id]), $this->editor->id)
            ->getData();
        $this->assertFalse($viewActive['cpList']->contains('id', $cpOld->id));

        // Siapkan Peta menyalin CP ke TA tujuan tanpa mengubah TA sumber.
        app(KurikulumController::class)->siapkan(
            $this->makeRequest([
                'academic_year_id' => $targetAy->id,
                'source_academic_year_id' => $this->oldAy->id,
            ]),
            $this->editor->id,
        );

        $this->assertDatabaseHas('capaian_pembelajaran', [
            'academic_year_id' => $targetAy->id,
            'subject_id' => $this->subject->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'CP versi TA lama.',
        ]);
        $this->assertDatabaseHas('capaian_pembelajaran', [
            'id' => $cpOld->id,
            'academic_year_id' => $this->oldAy->id,
            'deskripsi' => 'CP versi TA lama.',
        ]);
    }

    public function test_absensi_gtk_periode_terkunci_ditolak(): void
    {
        $profile = GtkProfile::create([
            'user_id' => $this->guru->id,
        ]);

        $oldDate = $this->oldAy->start_date->copy()->addDays(10)->toDateString();
        $activeDate = $this->activeAy->start_date->copy()->addDays(10)->toDateString();

        // Sebelum dikunci → absensi TA lama masih dapat diisi (selagi bukan arsip tulis).
        app(AcademicPeriodService::class)->lock($this->oldAy, $this->kepala);

        try {
            AbsensiGtk::create([
                'gtk_id' => $profile->id,
                'tanggal' => $oldDate,
                'status' => 'hadir',
                'dibuat_oleh' => $this->tu->id,
            ]);
            $this->fail('Absensi GTK pada periode terkunci seharusnya ditolak.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame(0, AbsensiGtk::count());

        // TA aktif → tersimpan normal.
        AbsensiGtk::create([
            'gtk_id' => $profile->id,
            'tanggal' => $activeDate,
            'status' => 'hadir',
            'dibuat_oleh' => $this->tu->id,
        ]);

        $this->assertSame(1, AbsensiGtk::count());
    }

    // ─────────────────────────────────────────────────────────────
    // V4 — NAMA ARAB, ANGKA ARAB OTOMATIS, TANGGAL PEMBAGIAN RAPOR
    // ─────────────────────────────────────────────────────────────

    public function test_rapor_arab_memakai_nama_arab_dan_angka_otomatis(): void
    {
        $this->activeRombel->update(['homeroom_teacher_id' => $this->guru->id]);
        $this->guru->update(['name_ar' => 'الأستاذ أحمد', 'name_ar_status' => 'approved']);
        $this->activeStudent->update(['name_ar' => 'سالم الفريد', 'name_ar_status' => 'approved']);
        $this->subject->update(['name_ar' => 'الرياضيات']);
        $this->school->update(['principal_name_ar' => 'المدير']);

        $kktp = SubjectKktp::create([
            'subject_id' => $this->subject->id,
            'school_id' => $this->school->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->activeAy->id,
            'semester' => 'ganjil',
            'kktp_score' => 75,
            'kkm_score' => 75,
            'created_by' => $this->tu->id,
        ]);
        $this->activeBook->update(['kktp_id' => $kktp->id]);

        // Pengaturan tanggal pembagian rapor (menu Rapor).
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/pengaturan", [
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'distribution_date' => '2026-10-20',
                'city' => 'Mataram',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rapor_distribution_settings', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->activeAy->id,
            'semester' => 'ganjil',
            'city' => 'Mataram',
        ]);

        // Nilai/predikat/KKTP/tanggal otomatis; nama memakai input Arab.
        $this->assertSame('٨٥٫٥', ArabicText::digits('85.5'));
        $this->assertSame('٢٠ أكتوبر ٢٠٢٦', ArabicText::formatDate('2026-10-20'));

        $html = app(RaporPdfService::class)->renderHtml(
            $this->school->id,
            $this->activeRombel->fresh(),
            $this->activeAy,
            (string) $this->activeAy->id,
            'ganjil',
            'sts',
            $this->activeStudent->fresh(),
            'ar',
        );

        $this->assertStringContainsString(ArabicText::shape('الطالب'), $html, 'Nama santri Arab harus dipakai.');
        $this->assertStringContainsString(ArabicText::shape('الرياضيات'), $html, 'Nama mapel Arab harus dipakai.');
        $this->assertStringContainsString(ArabicText::shape('الأستاذ'), $html, 'Nama wali kelas Arab harus dipakai.');
        $this->assertStringContainsString(ArabicText::shape('أحمد'), $html, 'Nama wali kelas Arab harus dipakai.');
        $this->assertStringContainsString(ArabicText::shape('المدير'), $html, 'Nama KSP Arab harus dipakai.');
        $this->assertStringContainsString('٢٠ أكتوبر ٢٠٢٦', $html, 'Tanggal pembagian rapor dipakai & berformat Arab.');
        $this->assertStringContainsString('٨٥', $html, 'Nilai otomatis menjadi angka Arab-Indik.');
        $this->assertStringContainsString('٧٥', $html, 'KKTP otomatis menjadi angka Arab-Indik.');

        // Versi Indonesia tetap Latin.
        $htmlId = app(RaporPdfService::class)->renderHtml(
            $this->school->id,
            $this->activeRombel->fresh(),
            $this->activeAy,
            (string) $this->activeAy->id,
            'ganjil',
            'sts',
            $this->activeStudent->fresh(),
            'id',
        );

        $this->assertStringContainsString('Santri Aktif', $htmlId);
        $this->assertStringContainsString('85', $htmlId);
        $this->assertStringNotContainsString('٨٥', $htmlId);

        // Nama Arab berstatus pending TIDAK dipakai pada dokumen resmi.
        $this->activeStudent->update(['name_ar_status' => 'pending']);

        $htmlPending = app(RaporPdfService::class)->renderHtml(
            $this->school->id,
            $this->activeRombel->fresh(),
            $this->activeAy,
            (string) $this->activeAy->id,
            'ganjil',
            'sts',
            $this->activeStudent->fresh(),
            'ar',
        );

        $this->assertStringNotContainsString(ArabicText::shape('سالم'), $htmlPending);
        $this->assertStringNotContainsString(ArabicText::shape('الفريد'), $htmlPending);
        $this->assertStringContainsString('Santri Aktif', $htmlPending);
    }

    // ─────────────────────────────────────────────────────────────
    // V5 — CETAK MULTIBAHASA, HIJRI, FILTER DOKUMEN
    // ─────────────────────────────────────────────────────────────

    public function test_cetak_rapor_bahasa_arab_dari_ui(): void
    {
        $this->actingAs($this->tu);

        $url = "/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/{$this->activeStudent->id}/cetak"
            ."?academic_year_id={$this->activeAy->id}&semester=ganjil&jenis=sas&bahasa=ar";

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_cetak_rapor_massal_bahasa_arab_dari_ui(): void
    {
        $this->actingAs($this->tu);

        $url = "/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/cetak-massal"
            ."?academic_year_id={$this->activeAy->id}&semester=ganjil&jenis=sas&bahasa=ar";

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_halaman_rapor_menyediakan_pengaturan_dan_preview_pdf(): void
    {
        $url = "/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor"
            ."?academic_year_id={$this->activeAy->id}&semester=ganjil";

        $this->actingAs($this->tu)
            ->get($url)
            ->assertOk()
            ->assertSee('Preview PDF', false)
            ->assertSee('name="margin_top"', false)
            ->assertSee('name="font_size"', false)
            ->assertSee('raporPreview', false)
            ->assertSee('name="paper_size"', false);
    }

    public function test_pengaturan_tata_letak_rapor_disimpan_untuk_sts_dan_sas(): void
    {
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/layout", [
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'margin_top' => 12.5,
                'margin_right' => 14,
                'margin_bottom' => 16,
                'margin_left' => 13,
                'font_size' => 10.5,
                'title_font_size' => 15,
                'table_font_size' => 9.5,
                'info_font_size' => 9.5,
                'footer_font_size' => 7.5,
                'line_height' => 1.35,
                'paper_size' => 'folio',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = \App\Models\RaporDistributionSetting::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->activeAy->id)
            ->where('semester', 'ganjil')
            ->firstOrFail();

        $layout = $setting->layoutSettings();

        $this->assertSame(12.5, $layout['margin_top']);
        $this->assertSame(10.5, $layout['font_size']);
        $this->assertSame(9.5, $layout['table_font_size']);
        $this->assertSame(1.35, $layout['line_height']);
        $this->assertSame('folio', $layout['paper_size']);
    }

    public function test_tabel_rapor_menampilkan_nilai_predikat_dan_peringkat(): void
    {
        $url = "/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor"
            ."?academic_year_id={$this->activeAy->id}&semester=ganjil";

        $this->actingAs($this->tu)
            ->get($url)
            ->assertOk()
            ->assertSee('Nilai', false)
            ->assertSee('Predikat', false)
            ->assertSee('Peringkat', false)
            ->assertSee('85.0', false)
            ->assertSee('Jayyid Jiddan', false)
            ->assertSee('#1', false);
    }

    public function test_mapel_rapor_dapat_disimpan_untuk_rombel(): void
    {
        $subjects = app(RaporPdfService::class)->availableSubjects(
            $this->school->id,
            $this->activeRombel,
            $this->activeAy->id,
            'ganjil',
        );
        $this->assertNotEmpty($subjects);

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/mapel", [
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'subject_ids' => [$subjects->first()->id],
                'subject_order' => [$subjects->first()->id => 1],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = \App\Models\RaporDistributionSetting::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->activeAy->id)
            ->where('semester', 'ganjil')
            ->firstOrFail();

        $this->assertSame([$subjects->first()->id], $setting->reportSubjectIds());
    }

    public function test_pengaturan_ttd_stempel_dan_ukuran_gambar_disimpan(): void
    {
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/layout", [
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'margin_top' => 10,
                'margin_right' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'font_size' => 11,
                'title_font_size' => 14,
                'table_font_size' => 10,
                'info_font_size' => 10,
                'footer_font_size' => 8,
                'line_height' => 1.4,
                'paper_size' => 'a4',
                'show_kop' => 0,
                'show_ttd_ksp' => 1,
                'show_ttd_wali' => 1,
                'show_stamp' => 0,
                'kop_height' => 22,
                'ttd_ksp_width' => 28,
                'ttd_ksp_height' => 14,
                'ttd_wali_width' => 26,
                'ttd_wali_height' => 13,
                'stamp_width' => 24,
                'stamp_height' => 24,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = \App\Models\RaporDistributionSetting::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->activeAy->id)
            ->where('semester', 'ganjil')
            ->firstOrFail();

        $layout = $setting->layoutSettings();
        $this->assertFalse($layout['show_kop']);
        $this->assertTrue($layout['show_ttd_ksp']);
        $this->assertTrue($layout['show_ttd_wali']);
        $this->assertFalse($layout['show_stamp']);
        $this->assertSame(22.0, $layout['kop_height']);
        $this->assertSame(28.0, $layout['ttd_ksp_width']);
        $this->assertSame(24.0, $layout['stamp_width']);
    }

    public function test_ttd_wali_kelas_dapat_diupload_dan_dihapus(): void
    {
        Storage::fake('public');
        $this->activeRombel->update(['homeroom_teacher_id' => $this->tu->id]);

        $url = "/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/ttd-wali";

        $this->actingAs($this->tu)
            ->post($url, ['ttd_wali_file' => UploadedFile::fake()->image('ttd.png', 300, 100)])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->tu->refresh();
        $this->assertNotNull($this->tu->ttd_path);
        Storage::disk('public')->assertExists($this->tu->ttd_path);

        $this->actingAs($this->tu)
            ->post($url, ['remove_ttd_wali' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($this->tu->fresh()->ttd_path);
    }

    public function test_dokumen_kop_ttd_dan_stempel_dapat_diupload_dari_edit_sekolah(): void
    {
        Storage::fake('public');

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/{$this->school->id}/media", [
                'logo_path' => UploadedFile::fake()->image('logo.png', 100, 100),
                'kop_path' => UploadedFile::fake()->image('kop.png', 600, 200),
                'ttd_ksp_path' => UploadedFile::fake()->image('ttd.png', 300, 100),
                'stamp_path' => UploadedFile::fake()->image('stamp.png', 200, 200),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $school = $this->school->fresh();
        $this->assertNotNull($school->logo_path);
        $this->assertNotNull($school->kop_path);
        $this->assertNotNull($school->ttd_ksp_path);
        $this->assertNotNull($school->stamp_path);
        Storage::disk('public')->assertExists($school->kop_path);
        Storage::disk('public')->assertExists($school->ttd_ksp_path);

        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/{$this->school->id}/media", [
                'remove_ttd_ksp_path' => 1,
                'remove_stamp_path' => 1,
            ])
            ->assertRedirect();

        $school = $this->school->fresh();
        $this->assertNull($school->ttd_ksp_path);
        $this->assertNull($school->stamp_path);
    }

    public function test_ksp_waka_dan_tu_dapat_membuka_edit_sekolah(): void
    {
        foreach ([
            'Kepala Satuan Pendidikan',
            'Wakil Kepala Satuan Pendidikan',
            'Staf Tata Usaha',
        ] as $index => $jabatan) {
            $user = $this->makeUser(
                'Editor '.$jabatan,
                'editor.'.($index + 1).'@test.local',
                $jabatan,
            );

            $this->actingAs($user)
                ->get("/{$user->id}/schools/{$this->school->id}/edit")
                ->assertOk()
                ->assertSee('Kop Surat', false);

            $this->actingAs($user)
                ->get("/{$user->id}/schools/{$this->school->id}")
                ->assertOk()
                ->assertSee('Upload Logo, Kop, TTD', false);
        }
    }

    public function test_pengaturan_media_rapor_disimpan_melalui_modal(): void
    {
        $this->actingAs($this->tu)
            ->post("/{$this->tu->id}/schools/nilai-kelas/{$this->activeRombel->id}/rapor/media", [
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'show_kop' => 1,
                'show_ttd_ksp' => 0,
                'show_ttd_wali' => 1,
                'show_stamp' => 0,
                'kop_height' => 20,
                'ttd_ksp_width' => 25,
                'ttd_ksp_height' => 12,
                'ttd_wali_width' => 24,
                'ttd_wali_height' => 11,
                'stamp_width' => 22,
                'stamp_height' => 22,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $layout = \App\Models\RaporDistributionSetting::query()
            ->where('school_id', $this->school->id)
            ->where('academic_year_id', $this->activeAy->id)
            ->where('semester', 'ganjil')
            ->firstOrFail()
            ->layoutSettings();

        $this->assertTrue($layout['show_kop']);
        $this->assertFalse($layout['show_ttd_ksp']);
        $this->assertFalse($layout['show_stamp']);
        $this->assertSame(20.0, $layout['kop_height']);
        $this->assertSame(24.0, $layout['ttd_wali_width']);
    }

    public function test_nama_mapel_arab_otomatis_dari_halaman_mapel(): void
    {
        $this->actingAs($this->tu)
            ->put("/{$this->tu->id}/subjects/{$this->subject->id}/names", [
                'name' => 'Fiqih',
                'name_ar' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('الفقه', $this->subject->fresh()->name_ar);
    }

    public function test_rapor_arab_memakai_nama_mapel_arab_dan_tabel_rtl(): void
    {
        $html = app(RaporPdfService::class)->renderHtml(
            $this->school->id,
            $this->activeRombel,
            $this->activeAy,
            $this->activeAy->id,
            'ganjil',
            'sts',
            $this->activeStudent,
            'ar',
        );

        $this->assertStringContainsString('<table class="nilai" dir="rtl">', $html);
        $this->assertStringContainsString('body.ar table', $html);
        $this->assertSame('الفقه', app(\App\Services\ArabicName\ArabicNameResolver::class)->subjectName($this->subject));
    }

    public function test_tanggal_hijriah_arab_otomatis(): void
    {
        $hijri = ArabicText::formatHijriDate('2026-10-20');

        $this->assertNotSame('', $hijri);
        $this->assertStringContainsString('هـ', $hijri);
        $this->assertMatchesRegularExpression('/[\x{0660}-\x{0669}]/u', $hijri);
    }

    public function test_filter_dokumen_arsip_berdasarkan_jenis_dan_bahasa(): void
    {
        foreach ([
            ['type' => 'rapor', 'jenis' => 'sas', 'language' => 'ar', 'title' => 'Rapor Arab'],
            ['type' => 'rapor', 'jenis' => 'sts', 'language' => 'id', 'title' => 'Rapor Indonesia'],
            ['type' => 'leger', 'jenis' => 'sas', 'language' => null, 'title' => 'Leger'],
        ] as $i => $data) {
            ArchiveDocument::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->activeAy->id,
                'semester' => 'ganjil',
                'type' => $data['type'],
                'jenis' => $data['jenis'],
                'language' => $data['language'],
                'title' => $data['title'],
                'file_path' => "arsip/test/{$i}.pdf",
                'file_hash' => str_repeat('a', 64),
                'file_size' => 100,
            ]);
        }

        $this->actingAs($this->tu);

        $filtered = app(ArsipInventarisController::class)
            ->index($this->makeRequest([
                'academic_year_id' => $this->activeAy->id,
                'doc_type' => 'rapor',
                'doc_language' => 'ar',
            ]), $this->tu->id)
            ->getData();

        $this->assertCount(1, $filtered['documents']);
        $this->assertSame('Rapor Arab', $filtered['documents']->first()->title);
        $this->assertSame('rapor', $filtered['documentFilters']['type']);
        $this->assertSame('ar', $filtered['documentFilters']['language']);
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
                'confirm_warnings' => 1,
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

    public function test_checklist_kelengkapan_penutupan_periode(): void
    {
        $this->actingAs($this->tu);

        $view = app(ArsipInventarisController::class)
            ->index($this->makeRequest(['academic_year_id' => $this->oldAy->id]), $this->tu->id);
        $closure = $view->getData()['closure'];

        $keys = collect($closure['items'])->pluck('key')->all();

        $this->assertContains('rombel', $keys);
        $this->assertContains('penempatan', $keys);
        $this->assertContains('dokumen', $keys);
        $this->assertContains('nama_arab', $keys);
        $this->assertSame(0, $closure['errors'], 'Rombel & penempatan tersedia → tidak ada error.');
        $this->assertGreaterThan(0, $closure['warnings'], 'Data belum lengkap → ada peringatan.');
        $this->assertTrue($closure['ready']);
    }

    public function test_lock_menolak_tanpa_konfirmasi_peringatan(): void
    {
        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$this->oldAy->id}/kunci")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($this->oldAy->fresh()->locked_at, 'Tanpa konfirmasi peringatan, periode belum dikunci.');

        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$this->oldAy->id}/kunci", ['confirm_warnings' => 1])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($this->oldAy->fresh()->locked_at);
    }

    public function test_periode_tanpa_rombel_dan_penempatan_tidak_dapat_dikunci(): void
    {
        $emptyAy = AcademicYear::create([
            'name' => '2028/2029', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2028-07-01', 'end_date' => '2029-06-30',
        ]);

        $this->actingAs($this->kepala)
            ->post("/{$this->kepala->id}/arsip/{$emptyAy->id}/kunci", ['confirm_warnings' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($emptyAy->fresh()->locked_at);
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
