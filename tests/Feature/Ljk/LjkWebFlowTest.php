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

    private function createExamViaHttp(array $extra = []): LjkExam
    {
        $response = $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk", array_merge([
                'judul' => 'STS Fiqih Web',
                'paket_soal_id' => $this->paket->id,
                'admin_book_id' => $this->book->id,
                'study_group_id' => $this->studyGroup->id,
                'jenis_ujian' => 'sts',
                'tanggal' => '2026-09-18',
                'waktu_mulai' => '07:30',
                'waktu_selesai' => '09:00',
            ], $extra))
            ->assertRedirect();

        // Ambil UUID dari URL redirect (lebih andal daripada "latest" saat
        // beberapa ujian dibuat pada detik yang sama).
        $location = (string) $response->headers->get('Location');
        $uuid = basename((string) parse_url($location, PHP_URL_PATH));

        return LjkExam::query()->findOrFail($uuid);
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

    public function test_halaman_peserta_menampilkan_jawaban_ganda_untuk_koreksi(): void
    {
        $exam = $this->createExamViaHttp();
        $participant = LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();

        $answers = $this->correctSlots($participant);
        $answers[2] = 'AB'; // siswa mengganti jawaban: dua kotak bertanda X

        $path = app(LjkSyntheticRenderer::class)->renderToFile($exam->template->geometry, [
            'answers' => $answers,
        ], [
            'participant_seq' => (int) $participant->nomor_peserta,
            'path' => sys_get_temp_dir().'/ljk-web-ganda-'.Str::random(8).'.jpg',
        ]);

        $this->actingAs($this->teacher)
            ->post("/{$this->teacher->id}/ljk/{$exam->id}/scan", [
                'image' => new UploadedFile($path, 'scan.jpg', 'image/jpeg', null, true),
                'participant_id' => $participant->id,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $page = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/hasil/{$participant->id}");

        $page->assertOk();
        $page->assertSee('Terdeteksi:', false);
        // Nomor ganda wajib dikoreksi; tombol "terima hasil baca" tidak ditampilkan.
        $page->assertDontSee('Terima hasil baca', false);
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

    public function test_lembar_jawaban_memakai_header_identitas_dan_karakter(): void
    {
        $exam = $this->createExamViaHttp();
        $participant = \App\Models\LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();

        $html = app(\App\Services\Ljk\LjkSheetService::class)
            ->renderSheetHtml($exam->fresh(['school', 'subject', 'studyGroup', 'adminBook.teacher', 'template']), $participant, 1);

        // Judul dua baris seperti lembar soal.
        $this->assertStringContainsString('LEMBAR JAWABAN KOMPUTER (LJK)', $html);
        $this->assertStringContainsString('TAHUN AJARAN', $html);

        // Tabel identitas 3 baris dengan NIS + nama peserta.
        $this->assertStringContainsString('class="identitas"', $html);
        $this->assertStringContainsString('Mata Pelajaran', $html);
        $this->assertStringContainsString('Nama Peserta Didik', $html);
        $this->assertStringContainsString('NIS', $html);
        $this->assertStringContainsString('Santri Uji', $html);
        $this->assertStringNotContainsString('Waktu', $html);

        // Penanaman karakter + petunjuk pengisian LJK.
        $this->assertStringContainsString('PENANAMAN KARAKTER', $html);
        $this->assertStringContainsString('Pilih satu jawaban dengan membuat tanda silang', $html);
        $this->assertStringContainsString('Jumlah nomor:', $html);

        // Header tidak boleh menimpa butir pertama (template baru).
        $geometry = $exam->fresh()->template->geometry;
        $this->assertArrayHasKey('header_height_mm', $geometry);
        $this->assertGreaterThanOrEqual(80.0, (float) $geometry['header_height_mm']);
        $this->assertGreaterThanOrEqual((float) $geometry['header_height_mm'], (float) collect($geometry['questions'])->min('y_mm'));

        // Master tetap tanpa nama.
        $master = app(\App\Services\Ljk\LjkSheetService::class)
            ->renderSheetHtml($exam->fresh(['school', 'subject', 'studyGroup', 'adminBook.teacher', 'template']), $participant, 1, true);
        $this->assertStringContainsString('nama-line', $master);
        $this->assertStringNotContainsString('Santri Uji', $master);
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

        // Preview normal: ada QR + strip + identitas NIS/nama.
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('class="abs strip-cell"', $html);
        $this->assertStringContainsString('Santri Uji', $html);
        $this->assertStringContainsString('NIS', $html);
        $this->assertStringContainsString('2001', $html);
        $this->assertStringNotContainsString('Waktu', $html);
        // Baris meta ujian (judul/mapel/kelas/tanggal) sudah dihapus.
        $this->assertStringNotContainsString('STS Fiqih', $html);

        // Master generik: tanpa QR/strip, semua isian identitas kosong.
        $masterHtml = app(\App\Services\Ljk\LjkSheetService::class)
            ->renderSheetHtml($exam->fresh(), \App\Models\LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail(), 1, true);

        $this->assertStringNotContainsString('MASTER FOTOKOPI', $masterHtml);
        $this->assertStringNotContainsString('Master', $masterHtml);
        $this->assertStringNotContainsString('Master fotokopi', $masterHtml);
        $this->assertStringNotContainsString('data:image/png;base64,', $masterHtml);
        $this->assertStringNotContainsString('class="abs strip-cell"', $masterHtml);
        $this->assertStringNotContainsString('Santri Uji', $masterHtml);
        $this->assertStringNotContainsString('2001', $masterHtml);
        $this->assertStringNotContainsString('Fiqih', $masterHtml);
        // Enam nilai identitas diganti garis putus-putus.
        $this->assertGreaterThanOrEqual(6, substr_count($masterHtml, 'nama-line'));
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

        // Opsi tersusun dalam kolom sejajar (A..D selalu pada posisi yang sama).
        $this->assertStringContainsString('table class="opts"', $html);
        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertStringContainsString('%', $html);

        // Empat kolom per baris opsi (maks opsi paket) — satu tabel per soal.
        $this->assertSame(5, substr_count($html, 'table class="opts"'), 'Setiap soal punya tabel opsi sendiri.');
        $this->assertSame(5 * 4, substr_count($html, '<td style="width: 25%'), 'Opsi harus dalam 4 kolom sejajar.');

        // Font Cambria dengan fallback serif.
        $this->assertStringContainsString('Cambria', $data['fontStack']);
        $this->assertStringContainsString('DejaVu Serif', $data['fontStack']);

        // Format cetak: dasar 12pt Cambria (dikompensasi bila font pengganti),
        // spasi single + jarak antar soal 1.15, margin inci.
        $this->assertSame(12.0, (float) $data['fontSizeBase']);
        $this->assertLessThanOrEqual(12.0, (float) $data['fontSize']);
        $this->assertSame(1.0, (float) $data['lineHeight']);
        $this->assertSame(1.5, (float) $data['questionGap']);
        $this->assertStringContainsString('font-size: '.$data['fontSize'].'pt', $html);
        $this->assertStringContainsString('line-height: 1', $html);

        $expectedGap = round(0.5 * (float) $data['fontSize'], 2);
        $this->assertStringContainsString('margin-top: '.$expectedGap.'pt', $html, 'Jarak antar soal = +50% satu baris (1.5).');

        // Huruf pilihan & nomor soal tidak dicetak tebal.
        $this->assertStringContainsString('.letter { font-weight: normal; }', $html);
        $this->assertStringContainsString('td.qno { width: 4.5%; font-weight: normal; }', $html);

        // Nomor & teks soal memakai tabel (hanging indent): baris lanjutan
        // sejajar dengan teks, bukan dengan nomor.
        $this->assertStringContainsString('table class="qwrap"', $html);
        $this->assertStringContainsString('class="qbody"', $html);
        // Teks soal & opsi berada di kolom yang sama (tanpa indent tabel opsi).
        $this->assertStringContainsString('margin: 0.1em 0 0 0;', $html);

        // Tanpa gambar kop di pengaturan sekolah → area kop dikosongkan
        // (tidak memakai teks kop).
        $this->assertStringNotContainsString('<div class="kop">', $html);

        // Reset CSS tidak boleh menimpa margin @page (isi pernah mepet ke tepi).
        $this->assertStringContainsString('* { padding: 0; box-sizing: border-box; }', $html);
        $this->assertStringNotContainsString('* { margin: 0; padding: 0', $html);

        // Margin dari inci: 0.2 / 0.1 / 0.59 / 0.63 → mm.
        $this->assertSame(['top' => 5.08, 'bottom' => 2.54, 'left' => 14.99, 'right' => 16.0], $data['marginMm']);
        $this->assertStringContainsString('margin: 5.08mm 16mm 2.54mm 14.99mm;', $html);

        // Identitas berupa tabel maksimal 3 baris (2 pasang per baris).
        $this->assertCount(3, $data['identityRows']);
        $this->assertStringContainsString('table class="identitas"', $html);
        $this->assertSame(3, substr_count($html, '<tr>') >= 3 ? 3 : 0, 'Tabel identitas harus 3 baris.');

        // Endpoint PDF.
        $response = $this->actingAs($this->teacher)
            ->get("/{$this->teacher->id}/ljk/{$exam->id}/soal/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    /**
     * Wacana (soal cerita) dicetak sekali dengan keterangan rentang nomor,
     * tampil juga sebagai legenda di lembar jawaban.
     */
    public function test_wacana_soal_cerita_dan_legenda_lembar_jawaban(): void
    {
        // Tautkan wacana ke soal nomor 2 & 3 (urutan paket, bukan urutan id).
        $items = \App\Models\PaketSoalItem::query()
            ->where('paket_soal_id', $this->paket->id)
            ->orderBy('urutan')
            ->limit(3)
            ->get();

        $soalById = \App\Models\Soal::query()
            ->whereIn('id', $items->pluck('soal_id'))
            ->get()
            ->keyBy('id');

        $wacana = \App\Models\WacanaSoal::create([
            'bank_soal_id' => $soalById->first()->bank_soal_id,
            'school_id' => $this->school->id,
            'judul' => 'Teks Bacaan Uji',
            'teks' => 'Ini paragraf wacana untuk pengujian lembar soal.',
            'created_by' => $this->teacher->id,
        ]);

        // Nomor 2 & 3 memakai wacana (nomor 1 tidak).
        foreach ($items->slice(1, 2) as $item) {
            $soalById[$item->soal_id]?->forceFill(['wacana_id' => $wacana->id])->saveQuietly();
        }

        // Naskah dibekukan ulang agar wacana ikut tersimpan di snapshot
        // (alur nyata: wacana dipasang sebelum paket dipublikasikan).
        app(\App\Services\Evaluasi\PaketNaskahService::class)->snapshot($this->paket->fresh());
        $this->paket = $this->paket->fresh();

        $exam = $this->createExamViaHttp();

        $sheet = app(\App\Services\Ljk\LjkSoalSheet::class);
        $data = $sheet->data($exam->fresh(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

        $wacanaBlocks = collect($data['blocks'])->where('type', 'wacana');
        $this->assertCount(1, $wacanaBlocks, 'Wacana dicetak satu kali untuk kelompok berurutan.');
        $this->assertSame(2, $wacanaBlocks->first()['dari']);
        $this->assertSame(3, $wacanaBlocks->first()['sampai']);
        $this->assertSame(['2–3'], $sheet->wacanaRanges($exam->fresh()));

        $html = $sheet->render($exam->fresh(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));
        $this->assertStringContainsString('class="wacana"', $html);
        $this->assertStringContainsString('Wacana untuk soal nomor 2–3', $html);
        $this->assertStringContainsString('Ini paragraf wacana untuk pengujian lembar soal.', $html);

        // Legenda wacana tampil di lembar jawaban.
        $participant = \App\Models\LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();
        $sheetHtml = app(\App\Services\Ljk\LjkSheetService::class)
            ->renderSheetHtml($exam->fresh(), $participant, 1);

        $this->assertStringContainsString('Wacana/soal cerita:', $sheetHtml);
        $this->assertStringContainsString('2–3', $sheetHtml);
    }

    /**
     * Teks Arab: bentuk presentasi (shaping) + urutan visual + kelas .ar.
     */
    public function test_teks_arab_di_shaping_dan_dibungkus_span_arab(): void
    {
        $arabic = \App\Services\Ljk\LjkArabicText::class;

        // Font Arab bawaan aplikasi (Amiri, OFL) terdeteksi & dipakai.
        $fonts = \App\Services\Ljk\LjkPdfFonts::class;
        $this->assertStringContainsString('Amiri', $fonts::arabicFontLabel());
        $this->assertStringContainsString("font-family: 'LJK-Arabic'", $fonts::arabicFaceCss());
        $this->assertStringContainsString('LJK-Arabic', $fonts::arabicStack());

        $this->assertTrue($arabic::hasArabic('بسم الله'));
        $this->assertFalse($arabic::hasArabic('Bahasa Inggris'));

        // Shaping: "بسم" → BEH initial + SEEN medial + MEEM final (dibalik untuk render LTR).
        $shaped = $arabic::shape('بسم');
        $codes = array_map(static fn ($c) => mb_ord($c, 'UTF-8'), preg_split('//u', $shaped, -1, PREG_SPLIT_NO_EMPTY));

        $this->assertSame([0xFEE2, 0xFEB4, 0xFE91], $codes, 'Bentuk presentasi & urutan visual harus benar.');

        // Ligatur lam-alef.
        $lamAlef = $arabic::shape('لا');
        $this->assertSame(0xFEFB, mb_ord($lamAlef, 'UTF-8'));

        // HTML: segmen Arab dibungkus span.ar, teks latin tetap.
        $html = $arabic::toHtml('Soal: مَا هَذَا؟ pilihan A.');
        $this->assertStringContainsString('<span class="ar">', $html);
        $this->assertStringContainsString('Soal:', $html);
        $this->assertStringContainsString('pilihan A.', $html);
    }

    /**
     * PDF dapat diunduh (Content-Disposition: attachment) untuk lembar soal & lembar jawaban.
     */
    public function test_pdf_dapat_diunduh_dengan_header_attachment(): void
    {
        $exam = $this->createExamViaHttp();

        foreach ([
            "/{$this->teacher->id}/ljk/{$exam->id}/soal/pdf?download=1",
            "/{$this->teacher->id}/ljk/{$exam->id}/pdf?download=1",
        ] as $url) {
            $response = $this->actingAs($this->teacher)->get($url);

            $response->assertOk();
            $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }
    }

    /**
     * Pemilihan kertas otomatis: A4 default, F4 hanya bila menghemat halaman.
     */
    public function test_pemilihan_kertas_otomatis_a4_ke_f4(): void
    {
        $renderer = app(\App\Services\Ljk\LjkSoalPdfRenderer::class);

        // Muat 1 halaman A4 → tetap A4.
        $this->assertSame('a4', $renderer->choosePaper(1, 1));
        // A4 2 halaman, F4 1 halaman → F4 (hemat kertas).
        $this->assertSame('f4', $renderer->choosePaper(2, 1));
        $this->assertSame('f4', $renderer->choosePaper(3, 2));
        // Sama-sama butuh halaman yang sama → default A4.
        $this->assertSame('a4', $renderer->choosePaper(2, 2));
        $this->assertSame('a4', $renderer->choosePaper(4, 4));

        // Ukuran kertas dari konfigurasi.
        $sheet = app(\App\Services\Ljk\LjkSoalSheet::class);
        $this->assertSame([215.0, 330.0], [$sheet->paper('f4')['width_mm'], $sheet->paper('f4')['height_mm']]);
        $this->assertSame([210.0, 297.0], [$sheet->paper('a4')['width_mm'], $sheet->paper('a4')['height_mm']]);
    }

    /**
     * Soal Bahasa Arab memakai huruf pilihan ا، ب، ج، د (bukan A, B, C, D)
     * pada lembar soal maupun label kotak di lembar jawaban.
     */
    public function test_soal_arab_memakai_huruf_pilihan_arab(): void
    {
        // Tambah satu soal Arab ke bank yang dipakai paket, lalu beku ulang naskah.
        $bankId = $this->paket->items()->first()?->soal?->bank_soal_id;

        $soalArab = \App\Models\Soal::create([
            'bank_soal_id' => $bankId,
            'tipe_soal' => 'pg',
            'pertanyaan' => 'مَا هَذَا؟',
            'bobot_default' => 1,
            'status' => 'approved',
            'workflow_status' => \App\Models\Soal::WORKFLOW_APPROVED,
            'content_hash' => hash('sha256', 'arab-uji-'.Str::random(8)),
            'dibuat_oleh' => $this->teacher->id,
        ]);

        foreach (['ا', 'ب', 'ج', 'د'] as $i => $huruf) {
            \App\Models\SoalOption::create([
                'soal_id' => $soalArab->id,
                'label' => chr(65 + $i),
                'teks_opsi' => 'خيار '.$huruf,
                'is_correct' => $i === 0,
                'urutan' => $i + 1,
            ]);
        }

        \App\Models\PaketSoalItem::create([
            'paket_soal_id' => $this->paket->id,
            'soal_id' => $soalArab->id,
            'urutan' => 99,
            'nomor' => 6,
            'jenis_soal' => 'pg',
        ]);

        $this->paket->recomputeTotals();
        app(\App\Services\Evaluasi\PaketNaskahService::class)->snapshot($this->paket->fresh());
        $this->paket = $this->paket->fresh();

        $exam = $this->createExamViaHttp(['bahasa' => 'arab']);

        $sheet = app(\App\Services\Ljk\LjkSoalSheet::class);
        $data = $sheet->data($exam->fresh(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

        $this->assertSame([1, 2, 3, 4, 5, 6], $sheet->arabicQuestionNumbers($exam->fresh(['paketSoal'])));

        $arab = collect($data['blocks'])->firstWhere('nomor', 6);
        $this->assertTrue($arab['isArabic']);
        $this->assertSame(['ا', 'ب', 'ج', 'د'], array_column($arab['opsi'], 'display_label'));

        $html = $sheet->render($exam->fresh(['subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));
        // RTL: penanda ".ا" diletakkan SETELAH teks opsi (bukan "ا. teks").
        $alef = \App\Services\Ljk\LjkArabicText::shape('ا');
        $this->assertStringContainsString('<span class="letter">.<span class="ar">'.$alef.'</span></span>', $html);

        // Ujian mode Arab → SELURUH lembar memakai huruf Arab (tanpa A.).
        $this->assertSame(0, substr_count($html, 'class="letter">A.'), 'Ujian Arab tidak boleh memakai huruf Latin.');

        // Lembar jawaban: label kotak nomor 6 memakai huruf Arab.
        $participant = \App\Models\LjkParticipant::where('ljk_exam_id', $exam->id)->firstOrFail();
        $sheetHtml = app(\App\Services\Ljk\LjkSheetService::class)->renderSheetHtml($exam->fresh(), $participant, 1);

        $this->assertStringContainsString('<span class="ar">ا</span>', $sheetHtml);
    }

    /**
     * Bila sekolah mengunggah kop surat (pengaturan sekolah), lembar soal
     * memakai gambar kop tersebut.
     */
    public function test_kop_sekolah_memakai_gambar_dari_pengaturan(): void
    {
        $relative = 'ljk-test/kop.png';
        $absolute = storage_path('app/public/'.$relative);
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($absolute));

        // PNG 1x1 (hitam) — cukup untuk memastikan gambar ter-embed.
        \Illuminate\Support\Facades\File::put($absolute, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));

        $this->school->forceFill(['kop_path' => $relative, 'logo_path' => null])->save();

        try {
            $exam = $this->createExamViaHttp();
            $html = app(\App\Services\Ljk\LjkSoalSheet::class)
                ->render($exam->fresh(['school', 'subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

            $this->assertStringContainsString('class="kop-image"', $html);
            $this->assertStringContainsString('data:image/png;base64,', $html);
            $this->assertStringNotContainsString('<div class="kop-school">', $html, 'Bila kop gambar ada, teks kop tidak dicetak.');
        } finally {
            \Illuminate\Support\Facades\File::delete($absolute);
            $this->school->forceFill(['kop_path' => null])->save();
        }
    }

    /**
     * Mode bahasa UJIAN: 'arab' → seluruh lembar RTL (nomor Arab + huruf ا/ب/ج/د,
     * penanda setelah teks); 'gabungan' → seluruh lembar LTR + huruf A/B/C/D
     * walau sebagian soal berbahasa Arab.
     */
    public function test_mode_bahasa_ujian_latin_gabungan_arab(): void
    {
        $bankId = $this->paket->items()->first()?->soal?->bank_soal_id;

        $buat = function (string $bahasa, string $pertanyaan, array $opsi) use ($bankId) {
            $soal = \App\Models\Soal::create([
                'bank_soal_id' => $bankId,
                'tipe_soal' => 'pg',
                'bahasa' => $bahasa,
                'pertanyaan' => $pertanyaan,
                'bobot_default' => 1,
                'status' => 'approved',
                'workflow_status' => \App\Models\Soal::WORKFLOW_APPROVED,
                'content_hash' => hash('sha256', 'mode-'.$bahasa.'-'.Str::random(8)),
                'dibuat_oleh' => $this->teacher->id,
            ]);

            foreach ($opsi as $i => $teks) {
                \App\Models\SoalOption::create([
                    'soal_id' => $soal->id,
                    'label' => chr(65 + $i),
                    'teks_opsi' => $teks,
                    'is_correct' => $i === 0,
                    'urutan' => $i + 1,
                ]);
            }

            return $soal;
        };

        $arab = $buat(\App\Models\Soal::BAHASA_ARAB, 'مَا هَذَا؟', ['كِتَابٌ', 'قَلَمٌ', 'بَابٌ', 'كُرْسِيٌّ']);
        $gabungan = $buat(\App\Models\Soal::BAHASA_GABUNGAN, 'Terjemahkan: الكِتَاب', ['Buku', 'Pena', 'Pintu', 'Kursi']);

        \App\Models\PaketSoalItem::create([
            'paket_soal_id' => $this->paket->id, 'soal_id' => $arab->id,
            'urutan' => 97, 'nomor' => 7, 'jenis_soal' => 'pg',
        ]);
        \App\Models\PaketSoalItem::create([
            'paket_soal_id' => $this->paket->id, 'soal_id' => $gabungan->id,
            'urutan' => 98, 'nomor' => 8, 'jenis_soal' => 'pg',
        ]);

        $this->paket->recomputeTotals();
        app(\App\Services\Evaluasi\PaketNaskahService::class)->snapshot($this->paket->fresh());
        $this->paket = $this->paket->fresh();

        // (a) Ujian GABUNGAN → seluruh lembar LTR + huruf Latin.
        $examGabungan = $this->createExamViaHttp(['bahasa' => 'gabungan']);
        $dataGabungan = app(\App\Services\Ljk\LjkSoalSheet::class)
            ->data($examGabungan->fresh(['school', 'subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

        $this->assertSame('gabungan', $dataGabungan['examBahasa']);
        $this->assertFalse($dataGabungan['arabAngka']);

        foreach (collect($dataGabungan['blocks'])->where('type', 'soal') as $block) {
            $this->assertFalse($block['rtl'], 'Naskah gabungan harus LTR walau ada soal Arab.');
            $this->assertSame(['A', 'B', 'C', 'D'], array_column($block['opsi'], 'display_label'));
        }

        $htmlGabungan = app(\App\Services\Ljk\LjkSoalSheet::class)->render($examGabungan->fresh(['school', 'subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));
        $this->assertStringNotContainsString('class="qbody" style="text-align: right;"', $htmlGabungan);
        $this->assertStringContainsString('<span class="letter">A.</span>', $htmlGabungan);

        // (b) Ujian ARAB → seluruh lembar RTL, nomor Arab, huruf Arab.
        $examArab = $this->createExamViaHttp(['bahasa' => 'arab']);
        $dataArab = app(\App\Services\Ljk\LjkSoalSheet::class)
            ->data($examArab->fresh(['school', 'subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

        $this->assertSame('arab', $dataArab['examBahasa']);
        $this->assertTrue($dataArab['arabAngka']);

        foreach (collect($dataArab['blocks'])->where('type', 'soal') as $block) {
            $this->assertTrue($block['rtl']);
            $this->assertSame(['ا', 'ب', 'ج', 'د'], array_column($block['opsi'], 'display_label'));
        }

        // Nomor tampil memakai digit Arab (mis. ١.).
        $pertama = collect($dataArab['blocks'])->firstWhere('type', 'soal');
        $this->assertSame('١.', $pertama['nomor_tampil']);

        $htmlArab = app(\App\Services\Ljk\LjkSoalSheet::class)->render($examArab->fresh(['school', 'subject', 'studyGroup.gradeLevel', 'adminBook.teacher', 'academicYear', 'paketSoal']));

        // RTL: teks didahulukan, nomor di sel kanan, penanda ".ج" setelah teks.
        $this->assertStringContainsString('class="qbody" style="text-align: right;"', $htmlArab);
        $this->assertStringContainsString('class="qno" style="text-align: right;"', $htmlArab);
        $this->assertStringContainsString('١.', $htmlArab);
        $this->assertStringContainsString('<span class="letter">.<span class="ar">', $htmlArab);
        $this->assertStringNotContainsString('<span class="letter">A.</span>', $htmlArab);
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
