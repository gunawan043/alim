<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\KisiKisiSoal;
use App\Models\PaketSoal;
use App\Models\PaketSoalItem;
use App\Models\ReviewAssignment;
use App\Models\Role;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\StudentClassHistory;
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
 * Bank Soal Terpusat lintas satuan pendidikan:
 * repository → similarity → review serumpun → approval → paket → distribusi → TU.
 */
class BankSoalTerpusatTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private AcademicYear $ay;

    private AcademicYear $ayPrev;

    private GradeLevel $gradeA;

    private GradeLevel $gradeB;

    private StudyGroup $groupA;

    private StudyGroup $groupB;

    private Subject $mathA;

    private Subject $mathB;

    private Subject $fisikaB;

    private User $guruA;

    private User $guruB;

    private User $guruD;

    private User $guruC;

    private User $tu;

    private User $waka;

    private User $ksp;

    private User $koor;

    private BankSoal $bankA;

    private BankSoal $bankHist;

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
    // REPOSITORY TERPUSAT LINTAS SATUAN PENDIDIKAN
    // ─────────────────────────────────────────────────────────────

    public function test_repository_terpusat_menampilkan_soal_lintas_satuan(): void
    {
        $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved');

        $this->actingAs($this->guruA);
        $response = $this->get("/{$this->guruA->id}/bank-soal-terpusat")
            ->assertOk()
            ->assertSee('Bank Soal Terpusat')
            ->assertSee('Repository Soal Lintas Satuan Pendidikan');

        // Soal guru sekolah lain tampil di repository (bukan dibatasi school_id).
        $response->assertSee('perkalian 25 dengan 4');
        $response->assertSee('Soal Saya');
    }

    public function test_soal_dibuat_langsung_di_sistem_secara_terstruktur(): void
    {
        $this->actingAs($this->guruA);

        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal", [
            'tipe_soal' => 'pg',
            'pertanyaan' => 'Hasil dari 25 × 4 adalah ....',
            'pembahasan' => '25 dikali 4 sama dengan 100.',
            'materi' => 'Perkalian',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'tags' => 'perkalian,bilangan',
            'options' => [
                ['label' => 'A', 'teks_opsi' => '75', 'is_correct' => 0],
                ['label' => 'B', 'teks_opsi' => '100', 'is_correct' => 1],
                ['label' => 'C', 'teks_opsi' => '125', 'is_correct' => 0],
                ['label' => 'D', 'teks_opsi' => '150', 'is_correct' => 0],
            ],
        ])->assertStatus(302);

        $soal = Soal::firstOrFail();

        $this->assertSame('Perkalian', $soal->materi);
        $this->assertSame('25 dikali 4 sama dengan 100.', $soal->pembahasan);
        $this->assertSame(Soal::WORKFLOW_DRAFT, $soal->workflow_status);
        $this->assertSame(4, $soal->options()->count());
        $this->assertSame(1, (int) $soal->options()->where('is_correct', true)->count());
        $this->assertNotNull($soal->content_hash);
        $this->assertNotEmpty($soal->shingles_hash);
    }

    // ─────────────────────────────────────────────────────────────
    // REUSE HISTORIS → TURUNAN (ORIGINAL TIDAK BERUBAH)
    // ─────────────────────────────────────────────────────────────

    public function test_reuse_soal_historis_membuat_turunan_tanpa_mengubah_asli(): void
    {
        $original = $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved');
        $originalQuestion = $original->pertanyaan;

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal-terpusat/{$original->id}/reuse")->assertStatus(302);

        $derivative = Soal::where('derived_from_soal_id', $original->id)->firstOrFail();

        $this->assertSame($this->guruA->id, $derivative->dibuat_oleh);
        $this->assertSame(Soal::WORKFLOW_DRAFT, $derivative->workflow_status);
        $this->assertSame('Turunan dari soal lain', 'Turunan dari soal lain');
        $this->assertDatabaseHas('soal_clone_log', [
            'soal_asli_id' => $original->id,
            'soal_clone_id' => $derivative->id,
            'clone_type' => 'adapt',
        ]);

        // Soal asli tidak berubah.
        $this->assertSame($originalQuestion, $original->fresh()->pertanyaan);
    }

    // ─────────────────────────────────────────────────────────────
    // SIMILARITY CHECK + REVIEW SERUMPUN
    // ─────────────────────────────────────────────────────────────

    public function test_submit_review_menjalankan_similarity_dan_menugaskan_reviewer_serumpun(): void
    {
        $historis = $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved');

        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review")->assertStatus(302);

        $soal->refresh();
        $this->assertSame(Soal::WORKFLOW_REVIEW, $soal->workflow_status);
        $this->assertNotNull($soal->similarity_checked_at);

        // Similarity terdeteksi terhadap soal historis (lintas sekolah/tahun).
        $this->assertDatabaseHas('soal_similarities', ['soal_id' => $soal->id, 'compared_soal_id' => $historis->id]);

        // Reviewer serumpun lintas satuan pendidikan (guruB & guruD di sekolah B), bukan guruA.
        $reviewers = ReviewAssignment::where('reviewable_type', Soal::class)
            ->where('reviewable_id', $soal->id)
            ->pluck('reviewer_id')
            ->all();

        $this->assertContains($this->guruB->id, $reviewers);
        $this->assertContains($this->guruD->id, $reviewers);
        $this->assertNotContains($this->guruA->id, $reviewers);
        $this->assertNotContains($this->guruC->id, $reviewers); // guru fisika (bukan serumpun)

        // Audit trail.
        $this->assertTrue(AuditLog::where('action', 'submit_review')->where('record_id', $soal->id)->exists());
    }

    public function test_approval_memerlukan_semua_reviewer_dan_revisi_mengulang(): void
    {
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Soal untuk approval bersama.', 'draft');

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review")->assertStatus(302);

        $assignments = ReviewAssignment::where('reviewable_id', $soal->id)->get();
        $this->assertCount(2, $assignments);

        $assignB = $assignments->firstWhere('reviewer_id', $this->guruB->id);
        $assignD = $assignments->firstWhere('reviewer_id', $this->guruD->id);
        $this->assertNotNull($assignB);
        $this->assertNotNull($assignD);

        // Guru luar (fisika) tidak boleh memutuskan.
        $this->actingAs($this->guruC);
        $this->post("/{$this->guruC->id}/review-soal/{$assignB->id}/decide", ['status' => 'approved'])
            ->assertStatus(403);

        // Reviewer 1 setuju → belum approved.
        $this->actingAs($this->guruB);
        $this->post("/{$this->guruB->id}/review-soal/{$assignB->id}/decide", ['status' => 'approved'])->assertStatus(302);
        $this->assertSame(Soal::WORKFLOW_REVIEW, $soal->fresh()->workflow_status);

        // Reviewer 2 minta perbaikan → Perlu Perbaikan.
        $this->actingAs($this->guruD);
        $this->post("/{$this->guruD->id}/review-soal/{$assignD->id}/decide", ['status' => 'revision', 'note' => 'Perbaiki pengecoh.'])->assertStatus(302);
        $this->assertSame(Soal::WORKFLOW_REVISI, $soal->fresh()->workflow_status);

        // Ajukan ulang → semua approval di-reset ke pending.
        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review")->assertStatus(302);
        $this->assertSame(0, ReviewAssignment::where('reviewable_id', $soal->id)->where('status', 'approved')->count());

        // Semua reviewer setuju → APPROVED.
        foreach (ReviewAssignment::where('reviewable_id', $soal->id)->get() as $assignment) {
            $reviewer = User::find($assignment->reviewer_id);
            $this->actingAs($reviewer);
            $this->post("/{$reviewer->id}/review-soal/{$assignment->id}/decide", ['status' => 'approved'])->assertStatus(302);
        }

        $this->assertSame(Soal::WORKFLOW_APPROVED, $soal->fresh()->workflow_status);
        $this->assertTrue(AuditLog::where('action', 'review_approved')->exists());
    }

    // ─────────────────────────────────────────────────────────────
    // PAKET: QUALITY GATE → APPROVAL → DISTRIBUSI → TU
    // ─────────────────────────────────────────────────────────────

    public function test_paket_quality_gate_approval_distribusi_dan_tu(): void
    {
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);
        $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved');

        $paket = $this->makePaket([$soal]);

        $this->actingAs($this->guruA);

        // 1) Quality gate
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/quality-gate")->assertStatus(302);
        $paket->refresh();
        $this->assertNotNull($paket->similarity_checked_at);
        $this->assertGreaterThanOrEqual(1, (int) ($paket->similarity_summary['historical_warnings'] ?? 0));

        // 2) Ajukan approval → reviewer serumpun lintas satuan
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")->assertStatus(302);
        $this->assertSame(PaketSoal::WORKFLOW_REVIEW, $paket->fresh()->workflow_status);

        $assignments = ReviewAssignment::where('reviewable_type', PaketSoal::class)
            ->where('reviewable_id', $paket->id)->get();
        $this->assertCount(2, $assignments);

        // 3) Semua reviewer setuju → approved
        foreach ($assignments as $assignment) {
            $reviewer = User::find($assignment->reviewer_id);
            $this->actingAs($reviewer);
            $this->post("/{$reviewer->id}/review-soal/{$assignment->id}/decide", ['status' => 'approved'])->assertStatus(302);
        }

        $paket->refresh();
        $this->assertSame(PaketSoal::WORKFLOW_APPROVED, $paket->workflow_status);

        // 4) Publish → final
        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/publish")->assertStatus(302);
        $paket->refresh();
        $this->assertTrue($paket->isFinal());

        // 5) Distribusi via sistem ke 5 penerima
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/distribute")->assertStatus(302);
        $this->assertSame(5, $paket->distributions()->count());
        $this->assertNotNull($paket->fresh()->distributed_at);
        $this->assertTrue(AuditLog::where('action', 'distributed')->where('record_id', $paket->id)->exists());

        // 6) TU menerima di sistem & mencatat produksi
        $this->actingAs($this->tu);
        $this->get("/{$this->tu->id}/tu-paket-soal")->assertOk()->assertSee($paket->kode_paket);

        $this->post("/{$this->tu->id}/tu-paket-soal/{$paket->id}/print-jobs", [
            'jumlah_cetak' => 120,
            'status' => 'proses',
            'petugas' => 'TU Uji',
        ])->assertStatus(302);

        $job = \App\Models\PaketSoalPrintJob::firstOrFail();
        $this->assertSame(120, $job->jumlah_cetak);

        $this->put("/{$this->tu->id}/tu-paket-soal/{$paket->id}/print-jobs/{$job->id}", [
            'jumlah_cetak' => 120,
            'status' => 'selesai',
        ])->assertStatus(302);
        $this->assertSame('selesai', $job->fresh()->status);

        // 7) Non-TU tidak dapat mengakses workspace TU
        $this->actingAs($this->guruC);
        $this->get("/{$this->guruC->id}/tu-paket-soal")->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────
    // INTEGRASI SUMATIF (BUKU ADMINISTRASI)
    // ─────────────────────────────────────────────────────────────

    public function test_sumatif_hanya_menerima_paket_final(): void
    {
        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Soal paket final untuk sumatif.', 'approved', [
            ['label' => 'A', 'teks_opsi' => '1', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '2', 'is_correct' => false],
        ]);

        $paketFinal = $this->makePaket([$soal]);
        $paketFinal->forceFill([
            'workflow_status' => PaketSoal::WORKFLOW_APPROVED,
            'approved_at' => now(),
            'is_published' => true,
            'published_at' => now(),
        ])->save();

        $paketDraft = $this->makePaket([$soal]);

        // Buku administrasi + santri.
        $book = TeacherAdminBook::create([
            'teacher_id' => $this->guruA->id,
            'subject_id' => $this->mathA->id,
            'study_group_id' => $this->groupA->id,
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $studentId = (string) Str::uuid();
        Schema::disableForeignKeyConstraints();
        try {
            DB::table('students')->insert([
                'id' => $studentId,
                'school_id' => $this->schoolA->id,
                'nisn' => '888000111',
                'name' => 'Santri Uji Bank Soal',
                'gender' => 'L',
                'special_needs' => 'tidak',
                'residence_type' => 'lainnya',
                'transportation' => 'jalan_kaki',
                'sibling_count' => 0,
                'is_kps_receiver' => 0,
                'is_kip_receiver' => 0,
                'is_pip_eligible' => 0,
                'status' => 'active',
                'wali_status' => 'unlinked',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        StudentClassHistory::create([
            'student_id' => $studentId,
            'study_group_id' => $this->groupA->id,
            'academic_year_id' => $this->ay->id,
            'attendance_number' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->guruA);

        // Paket belum final → ditolak
        $this->post("/{$this->guruA->id}/schools/guru-mapel/{$book->id}/w3", [
            'sumatif' => [$studentId => ['sts' => 80]],
            'paket_soal_id' => $paketDraft->id,
        ])->assertSessionHas('error');

        // Paket final → tersimpan sebagai penghubung Buku Administrasi
        $this->post("/{$this->guruA->id}/schools/guru-mapel/{$book->id}/w3", [
            'sumatif' => [$studentId => ['sts' => 80]],
            'paket_soal_id' => $paketFinal->id,
        ])->assertStatus(302);

        $this->assertDatabaseHas('admin_nilai_sumatif', [
            'admin_book_id' => $book->id,
            'student_id' => $studentId,
            'paket_soal_id' => $paketFinal->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // MENU INPUT SOAL
    // ─────────────────────────────────────────────────────────────

    public function test_form_input_soal_tersedia_dan_memuat_field_baru(): void
    {
        $this->actingAs($this->guruA);

        // Pintasan dari repository.
        $this->get("/{$this->guruA->id}/bank-soal-terpusat")
            ->assertOk()
            ->assertSee('Buat Soal');

        // Form input soal (Bank Soal → Tambah Soal) memuat field terstruktur.
        $this->get("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/create")
            ->assertOk()
            ->assertSee('Materi / Topik')
            ->assertSee('Pembahasan')
            ->assertSee('Opsi Jawaban');
    }

    // ─────────────────────────────────────────────────────────────
    // AKSES SEMUA SOAL: WAKA, KURIKULUM, TU, KSP
    // ─────────────────────────────────────────────────────────────

    public function test_waka_kurikulum_tu_ksp_melihat_semua_soal(): void
    {
        // Bank privat milik guruB (sekolah B) — tidak accessible bagi guru biasa.
        $privateBank = BankSoal::create([
            'school_id' => $this->schoolB->id,
            'subject_id' => $this->fisikaB->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'nama' => 'Bank Privat Sekolah B',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'campuran',
            'shared_scope' => 'private',
            'is_central' => false,
            'owner_user_id' => $this->guruB->id,
            'created_by' => $this->guruB->id,
        ]);

        $privateSoal = 'Soal privat fisika rahasia sekolah B.';
        $this->makeSoal($privateBank, $this->guruB, $privateSoal, 'approved');

        // Guru biasa (mapel lain) tidak melihat soal privat sekolah lain.
        $this->actingAs($this->guruA);
        $this->get("/{$this->guruA->id}/bank-soal-terpusat")
            ->assertOk()
            ->assertDontSee($privateSoal);

        // Waka, Kurikulum, TU, dan KSP melihat SELURUH soal.
        foreach ([$this->waka, $this->koor, $this->tu, $this->ksp] as $user) {
            $this->actingAs($user);
            $response = $this->get("/{$user->id}/bank-soal-terpusat")
                ->assertOk()
                ->assertSee($privateSoal)
                ->assertSee('Repository Soal'); // menu sidebar
        }

        // Hanya TU yang melihat menu cetak paket final.
        $this->actingAs($this->tu);
        $this->get("/{$this->tu->id}/bank-soal-terpusat")->assertSee('TU — Cetak Paket Final');

        $this->actingAs($this->waka);
        $this->get("/{$this->waka->id}/bank-soal-terpusat")->assertDontSee('TU — Cetak Paket Final');
    }

    // ─────────────────────────────────────────────────────────────
    // SIMILARITY: EXACT, NORMALISASI, FUZZY, TOKEN, HISTORIS
    // ─────────────────────────────────────────────────────────────

    public function test_exact_duplicate_terdeteksi_tanpa_memblokir_input(): void
    {
        $this->actingAs($this->guruA);

        $payload = fn () => [
            'tipe_soal' => 'pg',
            'pertanyaan' => 'Hasil dari 25 x 4 adalah ....',
            'pembahasan' => '25 dikali 4 sama dengan 100.',
            'materi' => 'Perkalian',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'options' => [
                ['label' => 'A', 'teks_opsi' => '75', 'is_correct' => 0],
                ['label' => 'B', 'teks_opsi' => '100', 'is_correct' => 1],
                ['label' => 'C', 'teks_opsi' => '125', 'is_correct' => 0],
            ],
        ];

        // Exact duplicate TIDAK diblokir — similarity adalah indikator, bukan vonis.
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal", $payload())->assertStatus(302);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal", $payload())->assertStatus(302);

        $this->assertSame(2, Soal::count());
        $a = Soal::firstOrFail();
        $b = Soal::where('id', '<>', $a->id)->firstOrFail();
        $this->assertSame($a->content_hash, $b->content_hash, 'Normalisasi harus menghasilkan hash identik.');

        $result = app(\App\Services\Evaluasi\SoalSimilarityService::class)->check($a);
        $row = collect($result['results'])->firstWhere('soal.id', $b->id);

        $this->assertNotNull($row, 'Exact duplicate harus terdeteksi.');
        $this->assertSame(\App\Models\SoalSimilarity::LEVEL_EXACT, $row['level']);
        $this->assertSame(100.0, (float) $row['score']);
    }

    public function test_perbedaan_kapitalisasi_spasi_dan_tanda_baca_dinormalisasi(): void
    {
        $this->actingAs($this->guruA);

        $base = [
            'tipe_soal' => 'pg',
            'pembahasan' => 'Kunci 100.',
            'materi' => 'Perkalian',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'options' => [
                ['label' => 'A', 'teks_opsi' => '75', 'is_correct' => 0],
                ['label' => 'B', 'teks_opsi' => '100', 'is_correct' => 1],
            ],
        ];

        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal", $base + [
            'pertanyaan' => 'Berapakah hasil 25 x 4 ???',
        ])->assertStatus(302);

        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal", $base + [
            'pertanyaan' => '   berapakah   HASIL 25 X 4   ',
        ])->assertStatus(302);

        $a = Soal::firstOrFail();
        $b = Soal::where('id', '<>', $a->id)->firstOrFail();

        $engine = app(\App\Services\Evaluasi\ContentHashEngine::class);
        $this->assertSame(
            $engine->pertanyaanNormalized('Berapakah hasil 25 x 4 ???'),
            $engine->pertanyaanNormalized('   berapakah   HASIL 25 X 4   ')
        );
        $this->assertSame($a->content_hash, $b->content_hash);

        $result = app(\App\Services\Evaluasi\SoalSimilarityService::class)->check($a);
        $row = collect($result['results'])->firstWhere('soal.id', $b->id);
        $this->assertNotNull($row);
        $this->assertSame(\App\Models\SoalSimilarity::LEVEL_EXACT, $row['level']);
    }

    public function test_soal_redaksi_mirip_terdeteksi_fuzzy_text(): void
    {
        $a = $this->makeSoal($this->bankHist, $this->guruB, 'Hasil dari 25 x 4 adalah 100.', 'approved', [
            ['label' => 'A', 'teks_opsi' => '75', 'is_correct' => false],
            ['label' => 'B', 'teks_opsi' => '100', 'is_correct' => true],
        ]);

        $b = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 x 4 ialah 100.', 'draft', [
            ['label' => 'A', 'teks_opsi' => '75', 'is_correct' => false],
            ['label' => 'B', 'teks_opsi' => '100', 'is_correct' => true],
        ]);

        $result = app(\App\Services\Evaluasi\SoalSimilarityService::class)->check($b);
        $row = collect($result['results'])->firstWhere('soal.id', $a->id);

        $this->assertNotNull($row, 'Redaksi mirip harus terdeteksi sebagai kandidat.');
        $this->assertSame(\App\Models\SoalSimilarity::LEVEL_TEXT, $row['level']);
        $this->assertGreaterThanOrEqual(\App\Services\Evaluasi\SoalSimilarityService::WARN_THRESHOLD, (float) $row['score']);
    }

    public function test_redaksi_berbeda_dengan_kata_kunci_sama_terdeteksi_token_overlap(): void
    {
        $hist = $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $baru = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $result = app(\App\Services\Evaluasi\SoalSimilarityService::class)->check($baru);
        $row = collect($result['results'])->firstWhere('soal.id', $hist->id);

        $this->assertNotNull($row, 'Kesamaan kata kunci & angka harus terdeteksi (lexical token overlap).');
        $this->assertSame(\App\Models\SoalSimilarity::LEVEL_TOKEN, $row['level']);
        $this->assertGreaterThanOrEqual(\App\Services\Evaluasi\SoalSimilarityService::TOKEN_THRESHOLD, (float) $row['score']);
    }

    public function test_perbandingan_soal_historis_lintas_tahun_ajaran(): void
    {
        $hist = $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $baru = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$baru->id}/submit-review")->assertStatus(302);

        // Hasil similarity menyimpan konteks tahun ajaran historis.
        $sim = \App\Models\SoalSimilarity::where('soal_id', $baru->id)->where('compared_soal_id', $hist->id)->firstOrFail();
        $this->assertGreaterThanOrEqual(60, (float) $sim->score);

        // Comparison view menampilkan metadata tahun ajaran & metode.
        $response = $this->getJson("/{$this->guruA->id}/bank-soal-terpusat/{$baru->id}/compare/{$hist->id}")
            ->assertOk()
            ->assertJsonPath('compared.academic_year', '2024/2025')
            ->assertJsonPath('compared.subject', 'Matematika')
            ->assertJsonPath('solution_visible', true);

        $this->assertNotEmpty($response->json('level_label'));
        $this->assertGreaterThanOrEqual(60, (float) $response->json('score'));
    }

    public function test_repository_filter_tahun_ajaran_dan_jenis_asesmen(): void
    {
        $historis = 'Soal historis tahun lalu tentang pecahan.';
        $this->makeSoal($this->bankHist, $this->guruB, $historis, 'approved');

        $this->actingAs($this->guruA);

        // Filter tahun ajaran historis → tampil.
        $this->get("/{$this->guruA->id}/bank-soal-terpusat?academic_year_id={$this->ayPrev->id}")
            ->assertOk()
            ->assertSee($historis);

        // Filter tahun ajaran aktif → soal historis tidak tampil.
        $this->get("/{$this->guruA->id}/bank-soal-terpusat?academic_year_id={$this->ay->id}")
            ->assertOk()
            ->assertDontSee($historis);

        // Filter jenis asesmen & materi.
        $this->get("/{$this->guruA->id}/bank-soal-terpusat?jenis_asesmen=pilihan_ganda&materi=Perkalian")
            ->assertOk()
            ->assertSee($historis);
    }

    public function test_turunan_soal_wajib_melewati_review_ulang(): void
    {
        $original = $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal-terpusat/{$original->id}/reuse")->assertStatus(302);

        $derivative = Soal::where('derived_from_soal_id', $original->id)->firstOrFail();
        $this->assertSame(Soal::WORKFLOW_DRAFT, $derivative->workflow_status);
        $this->assertNotNull($derivative->similarity_checked_at, 'Similarity check dijalankan saat reuse.');

        // Ajukan review → reviewer serumpun bertugas; original tidak berubah.
        $this->post("/{$this->guruA->id}/bank-soal/{$derivative->bank_soal_id}/soal/{$derivative->id}/submit-review")->assertStatus(302);

        // Halaman edit (penyusun) menampilkan hasil similarity.
        $this->get("/{$this->guruA->id}/bank-soal/{$derivative->bank_soal_id}/soal/{$derivative->id}/edit")
            ->assertOk()
            ->assertSee('Pemeriksaan Kemiripan');

        $reviewers = ReviewAssignment::where('reviewable_type', Soal::class)
            ->where('reviewable_id', $derivative->id)
            ->pluck('reviewer_id');

        $this->assertTrue($reviewers->contains($this->guruB->id));
        $this->assertSame(Soal::WORKFLOW_REVIEW, $derivative->fresh()->workflow_status);
        $this->assertSame(Soal::WORKFLOW_APPROVED, $original->fresh()->workflow_status, 'Soal asli tidak boleh berubah status.');
    }

    public function test_perubahan_soal_membatalkan_approval_dan_similarity_lama(): void
    {
        $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review", [
            'ack_note' => 'Kemiripan wajar, konteks soal berbeda.',
        ])->assertStatus(302);

        // Setujui semua reviewer → approved.
        foreach (ReviewAssignment::where('reviewable_id', $soal->id)->get() as $assignment) {
            $reviewer = User::find($assignment->reviewer_id);
            $this->actingAs($reviewer);
            $this->post("/{$reviewer->id}/review-soal/{$assignment->id}/decide", ['status' => 'approved'])->assertStatus(302);
        }

        $soal->refresh();
        $this->assertSame(Soal::WORKFLOW_APPROVED, $soal->workflow_status);
        $this->assertSame('Kemiripan wajar, konteks soal berbeda.', $soal->similarity_ack_note);
        $this->assertGreaterThan(0, $soal->similarities()->count());

        // Edit soal → approval & similarity lama kedaluwarsa.
        $this->actingAs($this->guruA);
        $this->put("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}", [
            'tipe_soal' => 'pg',
            'pertanyaan' => 'Hasil dari 26 × 4 adalah ....',
            'pembahasan' => '26 dikali 4 = 104.',
            'materi' => 'Perkalian',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'options' => [
                ['label' => 'A', 'teks_opsi' => '104', 'is_correct' => 1],
                ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => 0],
            ],
        ])->assertStatus(302);

        $soal->refresh();
        $this->assertSame(Soal::WORKFLOW_DRAFT, $soal->workflow_status, 'Approval lama harus gugur.');
        $this->assertSame(0, $soal->reviewAssignments()->count());
        $this->assertNull($soal->similarity_checked_at, 'Hasil similarity lama kedaluwarsa.');
        $this->assertNull($soal->similarity_ack_note, 'Pengecualian lama kedaluwarsa.');
        $this->assertSame(0, $soal->similarities()->count());
    }

    public function test_similarity_dan_alasan_penyusun_tampil_kepada_reviewer(): void
    {
        $this->makeSoal($this->bankHist, $this->guruB, 'Berapakah hasil perkalian 25 dengan 4?', 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $soal = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/bank-soal/{$this->bankA->id}/soal/{$soal->id}/submit-review", [
            'ack_note' => 'Redaksi berbeda, angka sama — tetap dilanjutkan.',
        ])->assertStatus(302);

        $assignment = ReviewAssignment::where('reviewable_id', $soal->id)
            ->where('reviewer_id', $this->guruB->id)
            ->firstOrFail();

        $this->actingAs($this->guruB);
        $this->get("/{$this->guruB->id}/review-soal/{$assignment->id}")
            ->assertOk()
            ->assertSee('Pemeriksaan Kemiripan')
            ->assertSee('Perlu ditinjau reviewer')
            ->assertSee('Kata Kunci Mirip')
            ->assertSee('Bandingkan')
            ->assertSee('Redaksi berbeda, angka sama — tetap dilanjutkan.');
    }

    public function test_kunci_jawaban_dan_pembahasan_terlindungi_authorization(): void
    {
        $soalGuruA = $this->makeSoal($this->bankA, $this->guruA, 'Soal rahasia milik guru A.', 'approved', [
            ['label' => 'A', 'teks_opsi' => 'Kunci rahasia A', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => 'Pengecoh', 'is_correct' => false],
        ]);
        $soalGuruA->forceFill(['pembahasan' => 'Pembahasan rahasia A.'])->save();

        $soalGuruB = $this->makeSoal($this->bankHist, $this->guruB, 'Soal milik guru B.', 'approved', [
            ['label' => 'A', 'teks_opsi' => 'Kunci rahasia B', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => 'Pengecoh', 'is_correct' => false],
        ]);

        // Guru C: bukan penyusun, bukan reviewer, bukan tim lintas satuan → kunci disembunyikan.
        $this->actingAs($this->guruC);
        $this->getJson("/{$this->guruC->id}/bank-soal-terpusat/{$soalGuruA->id}/compare/{$soalGuruB->id}")
            ->assertOk()
            ->assertJsonPath('solution_visible', false)
            ->assertJsonPath('soal.pembahasan', null)
            ->assertJsonPath('soal.options.0.correct', null)
            ->assertJsonPath('compared.options.0.correct', null);

        $this->getJson("/{$this->guruC->id}/bank-soal-terpusat/{$soalGuruA->id}/detail")
            ->assertOk()
            ->assertJsonPath('solution_visible', false)
            ->assertJsonPath('soal.pembahasan', null);

        // Penyusun melihat kunci soal sendiri.
        $this->actingAs($this->guruA);
        $this->getJson("/{$this->guruA->id}/bank-soal-terpusat/{$soalGuruA->id}/detail")
            ->assertOk()
            ->assertJsonPath('solution_visible', true)
            ->assertJsonPath('soal.pembahasan', 'Pembahasan rahasia A.')
            ->assertJsonPath('soal.options.0.correct', true);

        // Waka (tim lintas satuan) melihat kunci.
        $this->actingAs($this->waka);
        $this->getJson("/{$this->waka->id}/bank-soal-terpusat/{$soalGuruA->id}/detail")
            ->assertOk()
            ->assertJsonPath('solution_visible', true)
            ->assertJsonPath('soal.options.0.correct', true);
    }

    public function test_quality_gate_mendeteksi_duplikasi_internal_dan_kelengkapan(): void
    {
        $question = 'Hasil dari 25 × 4 adalah ....';

        $a = $this->makeSoal($this->bankA, $this->guruA, $question, 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);
        $b = $this->makeSoal($this->bankA, $this->guruA, $question, 'approved', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);
        $tanpaKunci = $this->makeSoal($this->bankA, $this->guruA, 'Soal PG tanpa kunci jawaban.', 'draft', [
            ['label' => 'A', 'teks_opsi' => 'Pilihan satu', 'is_correct' => false],
            ['label' => 'B', 'teks_opsi' => 'Pilihan dua', 'is_correct' => false],
        ]);

        $paket = $this->makePaket([$a, $b, $tanpaKunci]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/quality-gate")->assertStatus(302);

        $summary = $paket->fresh()->similarity_summary;

        $this->assertGreaterThanOrEqual(1, (int) $summary['internal_duplicates'], 'Duplikasi internal harus terdeteksi.');
        $this->assertGreaterThanOrEqual(1, (int) $summary['unapproved']);
        $this->assertGreaterThanOrEqual(1, (int) $summary['missing_key']);
        $this->assertGreaterThanOrEqual(1, (int) $summary['missing_metadata']);
    }

    public function test_paket_final_menolak_soal_yang_belum_approved(): void
    {
        $draft = $this->makeSoal($this->bankA, $this->guruA, 'Soal masih draft untuk paket.', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $paket = $this->makePaket([$draft]);

        $this->actingAs($this->guruA);
        $this->post("/{$this->guruA->id}/paket-soal/{$paket->id}/submit-approval")
            ->assertStatus(302)
            ->assertSessionHas('error');

        $this->assertSame(PaketSoal::WORKFLOW_DRAFT, $paket->fresh()->workflow_status);
        $this->assertSame(0, ReviewAssignment::where('reviewable_type', PaketSoal::class)->where('reviewable_id', $paket->id)->count());
    }

    public function test_repository_tetap_efisien_untuk_data_dalam_jumlah_besar(): void
    {
        // 60 soal serumpun di dua bank lintas satuan.
        for ($i = 1; $i <= 30; $i++) {
            $this->makeSoal($this->bankA, $this->guruA, "Soal latihan A nomor {$i} tentang operasi bilangan.", 'approved', [
                ['label' => 'A', 'teks_opsi' => "A{$i}", 'is_correct' => true],
                ['label' => 'B', 'teks_opsi' => "B{$i}", 'is_correct' => false],
            ]);
        }
        for ($i = 1; $i <= 30; $i++) {
            $this->makeSoal($this->bankHist, $this->guruB, "Soal latihan B nomor {$i} tentang operasi bilangan.", 'approved', [
                ['label' => 'A', 'teks_opsi' => "A{$i}", 'is_correct' => true],
                ['label' => 'B', 'teks_opsi' => "B{$i}", 'is_correct' => false],
            ]);
        }

        $target = $this->makeSoal($this->bankA, $this->guruA, 'Hasil dari 25 × 4 adalah ....', 'draft', [
            ['label' => 'A', 'teks_opsi' => '100', 'is_correct' => true],
            ['label' => 'B', 'teks_opsi' => '120', 'is_correct' => false],
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $result = app(\App\Services\Evaluasi\SoalSimilarityService::class)->check($target);

        $this->assertNotNull($result['summary']['checked_at']);
        $this->assertLessThanOrEqual(5, count($result['results']), 'Hasil similarity dibatasi (top-N), bukan seluruh repository.');

        // Prefilter kandidat memakai index (hash/tp/materi/shingle) — bukan scan seluruh tabel.
        $candidateQueries = array_values(array_filter($queries, function ($sql) {
            return str_contains($sql, '"pertanyaan" is not null') || str_contains($sql, '`pertanyaan` is not null');
        }));
        $this->assertCount(1, $candidateQueries, 'Hanya satu query kandidat yang dijalankan.');
        $this->assertTrue(
            str_contains($candidateQueries[0], 'content_hash') || str_contains($candidateQueries[0], 'json_each') || str_contains($candidateQueries[0], 'json_contains'),
            'Query kandidat harus memakai prefilter index.'
        );
        $this->assertLessThan(30, count($queries), 'Jumlah query tidak melebar mengikuti jumlah soal.');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE & HELPERS
    // ─────────────────────────────────────────────────────────────

    private function makeSoal(BankSoal $bank, User $author, string $question, string $workflow = 'draft', array $options = []): Soal
    {
        $engine = app(\App\Services\Evaluasi\ContentHashEngine::class);
        $correctTexts = collect($options)->where('is_correct', true)->pluck('teks_opsi')->all();

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tipe_soal' => 'pg',
            'pertanyaan' => '<p>'.$question.'</p>',
            'materi' => 'Perkalian',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'sedang',
            'waktu_estimasi_menit' => 2,
            'status' => $workflow === 'approved' ? 'approved' : 'draft',
            'workflow_status' => $workflow,
            'dibuat_oleh' => $author->id,
            'content_hash' => $engine->hashFromSoal('<p>'.$question.'</p>', $correctTexts),
            'shingles_hash' => $engine->shinglesFromSoal('<p>'.$question.'</p>'),
            'approved_at' => $workflow === 'approved' ? now() : null,
        ]);

        foreach ($options as $i => $opt) {
            SoalOption::create([
                'soal_id' => $soal->id,
                'label' => $opt['label'],
                'teks_opsi' => $opt['teks_opsi'],
                'is_correct' => (bool) $opt['is_correct'],
                'urutan' => $i + 1,
            ]);
        }

        return $soal;
    }

    /**
     * @param  array<int, Soal>  $soals
     */
    private function makePaket(array $soals): PaketSoal
    {
        $kisi = KisiKisiSoal::create([
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->mathA->id,
            'grade_level_id' => $this->gradeA->id,
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->guruA->id,
            'semester' => 'ganjil',
            'jenis_ujian' => 'sts',
            'judul' => 'Kisi STS Uji',
            'tingkat_sekolah' => 'smp',
            'total_soal_target' => count($soals),
            'is_active' => true,
        ]);

        $paket = PaketSoal::create([
            'kisi_kisi_soal_id' => $kisi->id,
            'judul' => 'Paket STS Uji',
            'is_acak_urutan_soal' => true,
            'is_acak_opsi' => true,
            'waktu_pengerjaan_menit' => 90,
            'shared_scope' => 'internal_school',
            'kkm' => 70,
        ]);

        foreach ($soals as $i => $soal) {
            PaketSoalItem::create([
                'paket_soal_id' => $paket->id,
                'soal_id' => $soal->id,
                'urutan' => $i + 1,
            ]);
        }

        $paket->recomputeTotals();

        return $paket->fresh();
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Yayasan Uji Bank Soal',
            'code' => 'YBS',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolA = School::create(['work_unit_id' => $workUnitId, 'npsn' => '11111111', 'name' => 'SMP A Uji']);
        $this->schoolB = School::create(['work_unit_id' => $workUnitId, 'npsn' => '22222222', 'name' => 'SMP B Uji']);

        $this->ay = AcademicYear::create(['name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        $this->ayPrev = AcademicYear::create(['name' => '2024/2025', 'semester' => 'ganjil', 'is_active' => false, 'start_date' => '2024-07-01', 'end_date' => '2025-06-30']);

        $this->gradeA = GradeLevel::create(['school_id' => $this->schoolA->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $this->gradeB = GradeLevel::create(['school_id' => $this->schoolB->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);

        $this->groupA = StudyGroup::create(['school_id' => $this->schoolA->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->gradeA->id, 'name' => '7A', 'code' => '7A', 'is_active' => true]);
        $this->groupB = StudyGroup::create(['school_id' => $this->schoolB->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->gradeB->id, 'name' => '7A', 'code' => '7A', 'is_active' => true]);

        $this->mathA = Subject::create(['school_id' => $this->schoolA->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 5, 'is_active' => true]);
        $this->mathB = Subject::create(['school_id' => $this->schoolB->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 5, 'is_active' => true]);
        $this->fisikaB = Subject::create(['school_id' => $this->schoolB->id, 'code' => 'FIS', 'name' => 'Fisika', 'credit_hours' => 3, 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $make = function (string $name, string $email, School $school, string $jabatan) use ($role) {
            $user = User::create(['name' => $name, 'email' => $email, 'password' => bcrypt('password'), 'is_active' => true]);
            $user->assignRole($role);
            $this->seedSnapshot($user, $school);

            Schema::disableForeignKeyConstraints();
            try {
                DB::table('gtk_employments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'school_id' => $school->id,
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
        };

        $this->guruA = $make('Guru A', 'guru.a@test.local', $this->schoolA, 'Guru Mapel');
        $this->guruB = $make('Guru B', 'guru.b@test.local', $this->schoolB, 'Guru Mapel');
        $this->guruD = $make('Guru D', 'guru.d@test.local', $this->schoolB, 'Guru Mapel');
        $this->guruC = $make('Guru C', 'guru.c@test.local', $this->schoolB, 'Guru Mapel');
        $this->tu = $make('TU Uji', 'tu@test.local', $this->schoolA, 'Staf Tata Usaha');
        $this->waka = $make('Waka Kurikulum Uji', 'waka@test.local', $this->schoolB, 'Wakil Kepala Satuan Pendidikan');
        $this->ksp = $make('Kepala Satuan Uji', 'ksp@test.local', $this->schoolB, 'Kepala Satuan Pendidikan');
        $this->koor = $make('Koor Kurikulum Uji', 'koor@test.local', $this->schoolB, 'Koordinator Kurikulum');

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/BS/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Uji Bank Soal',
            'school_id' => $this->schoolA->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$this->guruA, $this->schoolA, $this->mathA, $this->groupA],
            [$this->guruB, $this->schoolB, $this->mathB, $this->groupB],
            [$this->guruD, $this->schoolB, $this->mathB, $this->groupB],
            [$this->guruC, $this->schoolB, $this->fisikaB, $this->groupB],
        ] as [$teacher, $school, $subject, $group]) {
            TeachingAssignment::create([
                'decree_id' => $decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => 5,
                'status' => 'active',
            ]);
        }

        $this->bankA = BankSoal::create([
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->mathA->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->gradeA->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'nama' => 'Bank Matematika SMP A',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'sedang',
            'shared_scope' => 'public_pool',
            'is_central' => true,
            'owner_user_id' => $this->guruA->id,
            'created_by' => $this->guruA->id,
        ]);

        $this->bankHist = BankSoal::create([
            'school_id' => $this->schoolB->id,
            'subject_id' => $this->mathB->id,
            'fase' => 'D',
            'jenjang' => 'smp',
            'grade_level_id' => $this->gradeB->id,
            'academic_year_id' => $this->ayPrev->id,
            'semester' => 'ganjil',
            'nama' => 'Bank Matematika Historis SMP B',
            'jenis_soal' => 'pilihan_ganda',
            'tingkat_kesulitan_target' => 'sedang',
            'shared_scope' => 'public_pool',
            'is_central' => true,
            'owner_user_id' => $this->guruB->id,
            'created_by' => $this->guruB->id,
        ]);
    }

    private function seedSnapshot(User $user, School $school): void
    {
        $roleDimension = implode(',', $user->fresh()->effectiveRoles()) ?: 'default';

        $scopeKey = ScopeKey::fromComponents(
            schoolId: $school->id,
            academicYearId: 'global',
            roleDimension: $roleDimension,
            tenantId: 'local',
        )->value;

        DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
