<?php

namespace Tests\Feature\Ljk;

use App\Models\BankSoal;
use App\Models\KisiKisiSoal;
use App\Models\LjkExam;
use App\Models\LjkParticipant;
use App\Models\LjkScanAnswer;
use App\Models\NilaiSumatif;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\Permission;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Services\Evaluasi\PaketNaskahService;
use App\Services\Ljk\LjkExamService;
use App\Services\Ljk\LjkSyntheticRenderer;
use App\Authorization\ValueObjects\ScopeKey;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur HTTP guru: terbitkan LJK → PDF → pindai dari kamera HP → koreksi → sinkron nilai.
 */
class LjkWebFlowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $otherTeacher;

    private StudyGroup $studyGroup;

    private TeacherAdminBook $book;

    private PaketSoal $paket;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnit = \App\Models\WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Yayasan Alim',
            'code' => 'YYS-'.Str::random(6),
            'is_active' => true,
        ]);

        $this->school = School::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnit->id,
            'name' => 'MA Alim',
            'npsn' => 'NPSN'.Str::random(6),
            'school_level' => 'sma',
        ]);

        $this->teacher = $this->makeUser('Guru Pengampu');
        $this->otherTeacher = $this->makeUser('Guru Lain');

        $academicYear = \App\Models\AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $subject = \App\Models\Subject::create([
            'id' => (string) Str::uuid(),
            'name' => 'Fiqih',
            'code' => 'FQH',
            'school_id' => $this->school->id,
        ]);

        $gradeLevel = \App\Models\GradeLevel::create([
            'id' => (string) Str::uuid(),
            'name' => 'X',
            'level' => 10,
            'school_id' => $this->school->id,
        ]);

        $this->studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'X-A',
            'is_active' => true,
        ]);

        $this->book = TeacherAdminBook::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $this->teacher->id,
            'subject_id' => $subject->id,
            'study_group_id' => $this->studyGroup->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'name' => 'Santri Uji',
            'nis' => '2001',
            'nisn' => '9002001',
            'gender' => 'L',
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'study_group_id' => $this->studyGroup->id,
            'academic_year_id' => $academicYear->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        $this->paket = $this->makePaket($subject->id, $academicYear->id, $gradeLevel->id);
    }

    private function makeUser(string $name): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => strtolower(Str::random(8)).'@alim.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $this->school->id,
            academicYearId: 'global',
            roleDimension: implode(',', $user->fresh()->effectiveRoles()) ?: 'default',
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $this->school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode([]),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);

        Schema::disableForeignKeyConstraints();
        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => 'Guru Mapel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $user;
    }

    private function makePaket(string $subjectId, string $yearId, string $gradeLevelId): PaketSoal
    {
        $bank = BankSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'subject_id' => $subjectId,
            'grade_level_id' => $gradeLevelId,
            'academic_year_id' => $yearId,
            'semester' => 'ganjil',
            'nama' => 'Bank Fiqih',
            'jenis_soal' => 'pilihan_ganda',
            'owner_user_id' => $this->teacher->id,
            'created_by' => $this->teacher->id,
        ]);

        $kisi = KisiKisiSoal::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'subject_id' => $subjectId,
            'grade_level_id' => $gradeLevelId,
            'academic_year_id' => $yearId,
            'created_by' => $this->teacher->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi Fiqih',
            'tingkat_sekolah' => 'sma',
            'is_active' => true,
        ]);

        $paket = PaketSoal::create([
            'id' => (string) Str::uuid(),
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'STS Fiqih',
            'kode_paket' => 'PKT-WEB-'.Str::random(5),
            'versi' => 1,
            'workflow_status' => PaketSoal::WORKFLOW_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $soal = Soal::create([
                'id' => (string) Str::uuid(),
                'bank_soal_id' => $bank->id,
                'tipe_soal' => 'pg',
                'pertanyaan' => "Soal {$i}",
                'bobot_default' => 1,
                'status' => 'approved',
                'workflow_status' => Soal::WORKFLOW_APPROVED,
                'content_hash' => hash('sha256', 'web-soal-'.Str::random(8)),
                'dibuat_oleh' => $this->teacher->id,
            ]);

            foreach (['A', 'B', 'C', 'D'] as $idx => $label) {
                SoalOption::create([
                    'id' => (string) Str::uuid(),
                    'soal_id' => $soal->id,
                    'label' => $label,
                    'teks_opsi' => "Opsi {$label}",
                    'is_correct' => $label === 'A',
                    'urutan' => $idx + 1,
                ]);
            }

            PaketSoalItem::create([
                'id' => (string) Str::uuid(),
                'paket_soal_id' => $paket->id,
                'soal_id' => $soal->id,
                'urutan' => $i,
                'nomor' => $i,
                'jenis_soal' => 'pg',
            ]);
        }

        $paket->recomputeTotals();
        app(PaketNaskahService::class)->snapshot($paket);

        return $paket->fresh();
    }

    /**
     * Slot kotak (A..E) yang memuat kunci untuk tiap nomor.
     *
     * @return array<int, string>
     */
    private function correctSlots(LjkParticipant $participant): array
    {
        $grading = app(\App\Services\Ljk\LjkGradingService::class);
        $slots = [];

        foreach ($participant->mappingItems() as $item) {
            $keys = $grading->keyLabels($item['item_id']);
            $key = $keys[0] ?? 'A';
            $index = array_search($key, $item['opsi'] ?? [], true);
            $slots[(int) $item['no']] = $index === false ? 'A' : chr(65 + (int) $index);
        }

        return $slots;
    }

    private function createExamViaHttp(): LjkExam
    {
        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk", [
                'judul' => 'STS Fiqih Web',
                'paket_soal_id' => $this->paket->id,
                'admin_book_id' => $this->book->id,
                'study_group_id' => $this->studyGroup->id,
                'jenis_ujian' => 'sts',
                'tanggal' => '2026-09-18',
                'waktu_mulai' => '07:30',
                'waktu_selesai' => '09:00',
            ])
            ->assertRedirect();

        return LjkExam::query()->latest('created_at')->firstOrFail();
    }

    public function test_guru_dapat_membuka_daftar_dan_form_ljk(): void
    {
        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk")
            ->assertOk()
            ->assertSee('LJK Smart Scanner');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/create")
            ->assertOk()
            ->assertSee('Paket Soal');
    }

    public function test_terbitkan_ljk_membuat_peserta_lembar_dan_pdf(): void
    {
        $exam = $this->createExamViaHttp();

        $this->assertSame(1, LjkParticipant::where('ljk_exam_id', $exam->id)->count());
        $this->assertDatabaseHas('ljk_sheets', ['ljk_exam_id' => $exam->id]);

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}")
            ->assertOk()
            ->assertSee($exam->code);

        $response = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_pemindaian_lewat_http_memproses_dan_menilai(): void
    {
        $exam = $this->createExamViaHttp();
        $participant = LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();

        $geometry = $exam->template->geometry;
        $answers = $this->correctSlots($participant);
        $path = app(LjkSyntheticRenderer::class)->renderToFile($geometry, [
            'answers' => $answers,
        ], [
            'participant_seq' => (int) $participant->nomor_peserta,
            'path' => sys_get_temp_dir().'/ljk-web-'.Str::random(8).'.jpg',
        ]);

        $file = new UploadedFile($path, 'scan.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/scan", [
                'image' => $file,
                'participant_id' => $participant->id,
            ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(5, count($response->json('data.answers')));

        $scan = \App\Models\LjkScan::query()->latest('created_at')->firstOrFail();
        $this->assertSame('processed', $scan->status);
        $this->assertSame(5, $scan->answers()->count());

        // Semua kunci A → nilai 100.
        $this->assertEqualsWithDelta(100.0, (float) $participant->fresh()->nilai_akhir, 0.01);

        // Nilai otomatis masuk ke kolom resmi (STS murni) tanpa aksi manual.
        $row = NilaiSumatif::where('admin_book_id', $this->book->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($row, 'Nilai harus otomatis tersimpan setelah pemindaian bersih.');
        $this->assertEqualsWithDelta(100.0, (float) $row->sts_murni, 0.01);
        $this->assertSame($exam->id, $row->ljk_exam_id);
        $this->assertDatabaseHas('ljk_sync_logs', ['ljk_exam_id' => $exam->id, 'action' => 'auto']);

        // Guru lain tidak boleh mengunduh gambar scan.
        $this->actingAs($this->otherTeacher)
            ->get("/{$this->otherTeacher->id}/ljk/scan/gambar/{$scan->id}")
            ->assertForbidden();
    }

    public function test_koreksi_dan_sinkron_lewat_http(): void
    {
        $exam = $this->createExamViaHttp();
        $participant = LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();

        // Jawab nomor 1 dengan slot yang bukan kunci (salah).
        $geometry = $exam->template->geometry;
        $answers = $this->correctSlots($participant);
        $answers[1] = $answers[1] === 'A' ? 'B' : 'A';

        $path = app(LjkSyntheticRenderer::class)->renderToFile($geometry, [
            'answers' => $answers,
        ], ['participant_seq' => (int) $participant->nomor_peserta]);

        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/scan", [
                'image' => new UploadedFile($path, 'scan.jpg', 'image/jpeg', null, true),
                'participant_id' => $participant->id,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $answer = LjkScanAnswer::where('nomor', 1)->latest('created_at')->firstOrFail();
        $this->assertFalse((bool) $answer->is_correct);

        // Halaman periksa peserta memakai konvensi UI yang sama (chip opsi + tabel beku).
        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/hasil/{$participant->id}")
            ->assertOk()
            ->assertSee('ljk-option-chip')
            ->assertSee('table-freeze')
            ->assertSee('border-bottom-dashed');

        // Koreksi guru → jawaban sesungguhnya A.
        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/jawaban/{$answer->id}/review", [
                'action' => 'correct',
                'status' => 'terbaca',
                'label' => 'A',
                'reason' => 'Tanda silang kurang jelas.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ljk_answer_corrections', [
            'ljk_scan_answer_id' => $answer->id,
            'corrected_by' => $this->teacher->id,
        ]);

        // Koreksi membuat hasil bersih → nilai resmi ikut ter-update otomatis.
        $this->assertEqualsWithDelta(
            100.0,
            (float) NilaiSumatif::where('admin_book_id', $this->book->id)->where('student_id', $this->student->id)->value('sts_murni'),
            0.01,
            'Nilai STS murni harus ikut diperbarui setelah koreksi guru.'
        );

        // Finalkan lalu sinkron ke nilai resmi.
        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/final")
            ->assertRedirect();

        $this->assertSame(LjkExam::STATUS_FINAL, $exam->fresh()->status);

        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/sinkron", [])
            ->assertRedirect();

        $row = NilaiSumatif::where('admin_book_id', $this->book->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($row, 'Nilai harus tersinkron ke modul nilai resmi.');
        $this->assertEqualsWithDelta(100.0, (float) $row->sts_murni, 0.01);
        $this->assertSame($exam->id, $row->ljk_exam_id);
    }

    /**
     * Seluruh halaman LJK wajib mengikuti konvensi UI gtk/index.blade.php:
     * stat card, filter-badge, table-freeze, card header border-bottom-dashed.
     */
    public function test_ui_ljk_mengikuti_pola_gtk_index(): void
    {
        $exam = $this->createExamViaHttp();

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk")
            ->assertOk()
            ->assertSee('card-animate')
            ->assertSee('filter-badge')
            ->assertSee('table-freeze')
            ->assertSee('stat-label')
            ->assertSee('border-bottom-dashed');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/create")
            ->assertOk()
            ->assertSee('card-animate')
            ->assertSee('ljk-guide-step')
            ->assertSee('border-bottom-dashed');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}")
            ->assertOk()
            ->assertSee('card-animate')
            ->assertSee('table-freeze')
            ->assertSee('border-bottom-dashed');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/hasil")
            ->assertOk()
            ->assertSee('filter-badge')
            ->assertSee('card-animate')
            ->assertSee('table-freeze');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/scan")
            ->assertOk()
            ->assertSee('ljk-camera-shell')
            ->assertSee('ljk-guide-step')
            ->assertSee('btn btn-success');

        // Varian filter (query string) juga harus tetap konsisten.
        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk?status=aktif&jenis=sts&q=STS")
            ->assertOk()
            ->assertSee('filter-badge');

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/hasil?filter=review")
            ->assertOk()
            ->assertSee('filter-badge')
            ->assertSee('Belum Dipindai');
    }

    public function test_master_fotokopi_tanpa_nama_qr_dan_data_strip(): void
    {
        $exam = $this->createExamViaHttp();

        $response = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/pdf?master=1");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

        $html = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/preview")->getContent();

        // Preview normal: ada QR + strip + identitas.
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('class="abs strip-cell"', $html);
        $this->assertStringContainsString('Santri Uji', $html);

        // Master (tanpa nama): tanpa QR/strip, identitas kosong.
        $masterHtml = app(\App\Services\Ljk\LjkSheetService::class)
            ->renderSheetHtml($exam->fresh(), \App\Models\LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail(), 1, true);

        $this->assertStringContainsString('MASTER FOTOKOPI', $masterHtml);
        $this->assertStringNotContainsString('data:image/png;base64,', $masterHtml);
        $this->assertStringNotContainsString('class="abs strip-cell"', $masterHtml);
        $this->assertStringNotContainsString('Santri Uji', $masterHtml);
        $this->assertStringContainsString('Master fotokopi', $masterHtml);
    }

    public function test_halaman_lihat_soal_dan_kunci_terlindungi(): void
    {
        $exam = $this->createExamViaHttp();

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/soal")
            ->assertOk()
            ->assertSee('Soal 1')
            ->assertSee('kunci disembunyikan')
            ->assertDontSee('title="Kunci jawaban"', false);
    }

    /**
     * Regresi: view evaluasi paket soal tidak boleh error "Missing parameter"
     * (semua route() wajib memuat userId).
     */
    public function test_halaman_paket_soal_evaluasi_dapat_dibuka(): void
    {
        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/paket-soal")
            ->assertOk();

        $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/paket-soal/{$this->paket->id}")
            ->assertOk();
    }

    /**
     * Lembar soal sesuai format naskah madrasah: kop, penanaman karakter,
     * instruksi, opsi mendatar — tanpa bobot/poin dan tanpa kunci.
     */
    public function test_lembar_soal_pdf_sesuai_format_dan_tanpa_bobot(): void
    {
        $exam = $this->createExamViaHttp();

        $service = app(\App\Services\Ljk\LjkSoalSheet::class);
        $data = $service->data($exam->fresh());
        $html = $service->render($exam->fresh());

        // Kop sesuai contoh.
        $this->assertSame('SOAL ASESMEN SUMATIF TENGAH SEMESTER GANJIL', $data['judul'][0]);
        $this->assertSame('TAHUN AJARAN 2026/2027', $data['judul'][1]);
        $this->assertStringContainsString("Jum'at, 18 September 2026", $data['hariTanggal']);
        // Tingkat "X" (level 10) → "Sepuluh (X) <rombel>"; tingkat "Kelas 1" → "Satu (I)".
        $this->assertSame('Sepuluh (X) X-A', $data['kelas']);
        $this->assertSame('07.30 – 09.00', $data['waktu']);
        $this->assertStringContainsString('Guru Pengampu', $html);

        // Penanaman karakter + instruksi pilihan ganda.
        $this->assertStringContainsString('PENANAMAN KARAKTER', $html);
        $this->assertStringContainsString('Berdo', $html);
        $this->assertStringContainsString('Pilihlah jawaban yang paling tepat', $html);

        // Isi soal hanya pertanyaan & opsi — TANPA bobot/poin/jenis/TP/kunci.
        $this->assertStringContainsString('Soal 1', $html);
        $this->assertStringNotContainsString('bobot', $html);
        $this->assertStringNotContainsString('Bobot', $html);
        $this->assertStringNotContainsString('Pilihan Ganda', $html);
        $this->assertStringNotContainsString('Kunci', $html);
        $this->assertStringNotContainsString('kunci', $html);
        $this->assertStringNotContainsString('Pembahasan', $html);

        // Opsi mendatar (inline) — turun baris otomatis hanya bila tidak muat.
        $this->assertStringContainsString('class="opt"', $html);
        $this->assertStringContainsString('display: inline-block', $html);
        $this->assertStringNotContainsString('<br>B.', $html);

        // Font Cambria dengan fallback serif.
        $this->assertStringContainsString('Cambria', $data['fontStack']);
        $this->assertStringContainsString('DejaVu Serif', $data['fontStack']);

        // Endpoint PDF.
        $response = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/soal/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_guru_tidak_berwenang_tidak_bisa_melihat_ujian(): void
    {
        $exam = $this->createExamViaHttp();

        $this->actingAs($this->otherTeacher)
            ->get("/{$this->otherTeacher->id}/ljk/{$exam->id}")
            ->assertForbidden();

        $this->actingAs($this->otherTeacher)
            ->get("/{$this->otherTeacher->id}/ljk/{$exam->id}/scan")
            ->assertForbidden();
    }
}
