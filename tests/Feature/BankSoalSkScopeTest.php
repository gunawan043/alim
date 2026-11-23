<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\Role;
use App\Models\School;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Evaluasi\PrintSettingsService;
use App\Services\Evaluasi\QuickSoalService;
use App\Services\Evaluasi\SoalNaskahPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bank soal, input soal, dan impor hanya boleh memakai mata pelajaran dan
 * kelas yang diamanahkan SK Pembagian Tugas yang sedang AKTIF.
 */
class BankSoalSkScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private Subject $ipa;

    private User $guru;

    private InstitutionDecree $decree;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        \DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Yayasan Uji Bank Soal',
            'code' => 'YBS',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'name' => 'SD IT Putra',
            'npsn' => '12345678',
            'jenjang' => 'SD',
            'is_active' => true,
        ]);

        $this->ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->grade = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Kelas 4',
            'level' => 4,
        ]);

        $this->group = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '4A',
            'is_active' => true,
        ]);

        $this->math = Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Matematika',
            'code' => 'MAT',
            'is_active' => true,
        ]);

        $this->ipa = Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Ilmu Pengetahuan Alam',
            'code' => 'IPA',
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Guru Matematika',
            'email' => 'guru.matematika@example.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $this->guru->assignRole($role);

        $this->seedSnapshot($this->guru, $this->school);

        \DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'nupy' => '1112223334',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Mapel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->decree = $this->makeDecree('111/PAH/SDIT-Pa/SK/VII/2026', InstitutionDecree::STATUS_ACTIVE);
        $this->assign($this->math, $this->decree);
    }

    public function test_form_bank_soal_hanya_menampilkan_mapel_di_sk_aktif(): void
    {
        $response = $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk();

        $response->assertSee('Matematika');
        $response->assertDontSee('Ilmu Pengetahuan Alam');
        $response->assertSee('111/PAH/SDIT-Pa/SK/VII/2026');
    }

    public function test_mapel_di_luar_sk_ditolak_saat_menyimpan_bank(): void
    {
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/bank-soal", [
                'nama' => 'Bank IPA',
                'subject_id' => $this->ipa->id,
                'jenis_soal' => 'pilihan_ganda',
                'tingkat_kesulitan_target' => 'sedang',
                'shared_scope' => 'private',
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseMissing('bank_soal', ['nama' => 'Bank IPA']);
    }

    public function test_mapel_di_sk_diterima_saat_menyimpan_bank(): void
    {
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/bank-soal", [
                'nama' => 'Bank Matematika',
                'subject_id' => $this->math->id,
                'grade_level_id' => $this->grade->id,
                'jenis_soal' => 'pilihan_ganda',
                'tingkat_kesulitan_target' => 'sedang',
                'shared_scope' => 'private',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bank_soal', [
            'nama' => 'Bank Matematika',
            'subject_id' => $this->math->id,
        ]);
    }

    public function test_sk_terarsip_tidak_lagi_memberi_akses_mapel(): void
    {
        $this->decree->update(['status' => InstitutionDecree::STATUS_ARCHIVED]);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk()
            ->assertViewHas('scope', fn ($scope) => $scope['has_assignment'] === false);
    }

    public function test_soal_saya_menampilkan_mapel_sk_dan_menolak_mapel_lain(): void
    {
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Ilmu Pengetahuan Alam');

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/soal-saya", [
                'subject_id' => $this->ipa->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jenis_asesmen' => 'harian',
                'tipe_soal' => 'uraian',
                'pertanyaan' => 'Sebutkan contoh hewan mamalia.',
                'tingkat_kesulitan_estimasi' => 'sedang',
            ])
            ->assertSessionHasErrors('subject_id');
    }

    public function test_impor_word_menolak_mapel_di_luar_sk(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'soal.docx',
            'bukan docx asli'
        );

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/soal-saya/import-word", [
                'file' => $file,
                'subject_id' => $this->ipa->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jenis_asesmen' => 'harian',
            ])
            ->assertSessionHasErrors('subject_id');
    }

    public function test_penugasan_di_sk_lain_tidak_membuka_mapel(): void
    {
        // Mapel IPA hanya diamanahkan SK draf, bukan SK aktif.
        $draft = $this->makeDecree('112/PAH/SDIT-Pa/SK/VII/2026', InstitutionDecree::STATUS_DRAFT);
        $this->assign($this->ipa, $draft);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/create")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Ilmu Pengetahuan Alam');
    }

    public function test_tabel_pivot_bank_soal_tp_ada_di_sqlite(): void
    {
        // Migrasi asli melewati SQLite sehingga tabel ini hilang di produksi
        // dan halaman detail bank soal gagal 500.
        $this->assertTrue(Schema::hasTable('bank_soal_tp'));
    }

    public function test_halaman_detail_bank_soal_tidak_gagal(): void
    {
        $bank = BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'academic_year_id' => $this->ay->id,
            'nama' => 'Bank Matematika Kelas 4',
            'jenis_soal' => 'campuran',
            'bahasa' => 'latin',
            'tingkat_kesulitan_target' => 'campuran',
            'is_public' => false,
            'shared_scope' => 'internal_school',
            'owner_user_id' => $this->guru->id,
            'created_by' => $this->guru->id,
            'total_soal' => 0,
        ]);

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal/{$bank->id}")
            ->assertOk()
            ->assertSee('Bank Matematika Kelas 4');
    }

    // ─────────────────────────────────────────────────────────────
    // KISI-KISI & CETAK NASKAH
    // ─────────────────────────────────────────────────────────────

    public function test_kisi_kisi_guru_hanya_menampilkan_mapel_sk(): void
    {
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kisi-kisi-soal/create")
            ->assertOk()
            ->assertViewHas('subjects', fn ($subjects) => $subjects->pluck('id')->map(fn ($id) => (string) $id)->all() === [(string) $this->math->id]);
    }

    public function test_kisi_kisi_menolak_mapel_di_luar_sk(): void
    {
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kisi-kisi-soal", [
                'judul' => 'Kisi-kisi IPA',
                'subject_id' => $this->ipa->id,
                'grade_level_id' => $this->grade->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'jenis_ujian' => 'sts',
                'tingkat_sekolah' => 'sd',
            ]);
        // Ditolak (422/redirect dengan error) — bukan tersimpan.
        $this->assertDatabaseMissing('kisi_kisi_soal', ['judul' => 'Kisi-kisi IPA']);
    }

    public function test_halaman_cetak_naskah_tersedia(): void
    {
        $this->makeSoal();

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/cetak")
            ->assertOk()
            ->assertSee('Pengaturan naskah')
            ->assertSee('Cetak / simpan PDF')
            ->assertSee('Pratinjau naskah');
    }

    public function test_pratinjau_cetak_menghasilkan_pdf(): void
    {
        $this->makeSoal();

        $response = $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/cetak/preview");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_cetak_pdf_soal_harian_tanpa_validasi(): void
    {
        $soal = $this->makeSoal();

        $response = $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/soal-saya/cetak", [
                'ids' => [$soal->id],
                'paper' => 'F4',
                'margin_top' => 15,
                'margin_left' => 20,
            ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pengaturan_judul_tahun_dan_keterangan_masuk_ke_naskah(): void
    {
        $soal = $this->makeSoal();

        $html = app(SoalNaskahPdfService::class)->html(
            Soal::with(['bankSoal.subject', 'bankSoal.gradeLevel', 'options', 'wacana'])->whereKey($soal->id)->get(),
            $this->school,
            $this->guru,
            [
                'judul' => 'SOAL SUMATIF HARIAN',
                'judul_size' => 14,
                'judul_bold' => false,
                'judul_line_height' => 1.4,
                'judul_align' => 'left',
                'show_tahun_ajaran' => true,
                'tahun_ajaran' => '2026/2027',
                'tahun_size' => 10,
                'tahun_bold' => false,
                'show_identitas' => true,
                'identitas_label_peserta' => 'Nama Siswa',
                'identitas_border' => false,
                'identitas_padding' => 2.5,
                'identitas_rows' => 2,
                'show_karakter' => true,
                'karakter_title' => 'NILAI KARAKTER',
                'karakter_items' => "Jujur\nDisiplin",
                'karakter_style' => 'dash',
                'karakter_size' => 10,
                'karakter_bold' => true,
                'karakter_line_height' => 1.5,
                'show_instruksi' => true,
                'instruksi' => 'Kerjakan semua soal berikut.',
                'instruksi_size' => 10,
                'instruksi_bold' => true,
                'instruksi_italic' => true,
                'instruksi_spacing' => 1.4,
                'option_indent' => 4,
                'font' => 'times',
                'font_size' => 11,
                'line_height' => 1.2,
                'question_gap' => 2.0,
                'start_number' => 7,
                'show_kop' => false,
            ]
        );

        $this->assertStringContainsString('SOAL SUMATIF HARIAN', $html);
        $this->assertStringContainsString('TAHUN AJARAN 2026/2027', $html);
        $this->assertStringContainsString('text-align: left', $html);
        $this->assertStringContainsString('line-height: 1.4', $html);
        $this->assertStringContainsString('border: none', $html);
        $this->assertStringContainsString('padding: 0.35mm 2.5mm', $html);
        $this->assertStringContainsString('Nama Siswa', $html);
        $this->assertStringContainsString('NILAI KARAKTER', $html);
        $this->assertStringContainsString('Jujur', $html);
        $this->assertStringContainsString('list-style-type: none', $html);
        $this->assertStringContainsString('Kerjakan semua soal berikut.', $html);
        $this->assertStringContainsString('font-style: italic', $html);
        $this->assertStringContainsString('padding: 0 4mm 0 0', $html);
        $this->assertStringContainsString('7.', $html);
        $this->assertStringNotContainsString('<img class="kop-image"', $html);
    }

    public function test_pengaturan_boleh_mematikan_tabel_dan_karakter(): void
    {
        $soal = $this->makeSoal();

        $html = app(SoalNaskahPdfService::class)->html(
            Soal::with(['bankSoal.subject', 'bankSoal.gradeLevel', 'options', 'wacana'])->whereKey($soal->id)->get(),
            $this->school,
            $this->guru,
            [
                'show_identitas' => false,
                'show_karakter' => false,
                'show_instruksi' => false,
                'show_tahun_ajaran' => false,
                'judul' => 'NASKAH RINGKAS',
            ]
        );

        $this->assertStringContainsString('NASKAH RINGKAS', $html);
        $this->assertStringNotContainsString('<table class="identitas">', $html);
        $this->assertStringNotContainsString('PENANAMAN KARAKTER', $html);
        $this->assertStringNotContainsString('TAHUN AJARAN', $html);
    }

    public function test_default_sistem_soal_sesuai_ketentuan_sekolah(): void
    {
        $print = app(PrintSettingsService::class);
        $defaults = $print->sanitize($print->defaults());

        $this->assertSame('cambria', $defaults['font'], 'Font bawaan Cambria.');
        $this->assertSame(11, $defaults['font_size'], 'Ukuran font soal 11 pt.');
        $this->assertEqualsWithDelta(1.0, $defaults['line_height'], 0.001, 'Jarak baris 1.');
        $this->assertEqualsWithDelta(1.25, $defaults['question_gap'], 0.001, 'Jarak antar soal 1,25.');
        $this->assertSame(5, (int) $defaults['margin_top'], 'Margin atas 5 mm.');
        $this->assertSame(10, (int) $defaults['margin_bottom'], 'Margin bawah 10 mm.');

        // Kop & karakter otomatis aktif.
        $this->assertTrue($defaults['show_kop'], 'Kop otomatis tampil.');
        $this->assertTrue($defaults['show_karakter'], 'Penanaman karakter otomatis tampil.');

        // Kop selebar area margin dan tanpa garis (garis sudah ada di gambar).
        $this->assertSame(100, (int) $defaults['kop_width_pct'], 'Lebar kop penuh.');
        $this->assertFalse($defaults['kop_rule'], 'Kop tanpa garis tambahan.');
    }

    public function test_kop_penuh_selebar_margin_dan_bisa_diperkecil(): void
    {
        $render = function (int $pct, bool $rule, float $kopHeightMm = 18.0): string {
            return view('ljk.soal-pdf', [
                'judul' => ['NASKAH SOAL', ''],
                'identityRows' => [],
                'karakter' => [],
                'instruksi' => '',
                'blocks' => [],
                'maxOptions' => 4,
                'fontSize' => 11,
                'fontSizeBase' => 11,
                'fontStack' => 'Cambria',
                'arabicStack' => 'Amiri',
                'faceCss' => '',
                'arabicFaceCss' => '',
                'hasArabic' => false,
                'paper' => ['width_mm' => 210.0, 'height_mm' => 297.0, 'label' => 'A4'],
                'marginMm' => ['top' => 5, 'right' => 20, 'bottom' => 10, 'left' => 25],
                'letterhead' => [
                    'kopBase64' => 'data:image/png;base64,AAA',
                    'kopWidthMm' => 145.0,
                    'kopHeightMm' => $kopHeightMm,
                    'kopAspect' => $kopHeightMm / 145.0,
                ],
                'kopMaxHeightMm' => 40.0,
                'showKop' => true,
                'kopWidthPct' => $pct,
                'kopRule' => $rule,
            ])->render();
        };

        // 210 - 25 - 20 = 165 mm. Kop rasio 145:18 → 165 mm lebar,
        // 20,48 mm tinggi, masih di bawah batas 40 mm naskah.
        $full = $render(100, false);
        $this->assertStringContainsString('width: 165mm', $full);
        $this->assertStringContainsString('height: 20.48mm', $full);
        $this->assertStringNotContainsString('kop-rule"></div>', $full);

        // Kop pendek (145:6) juga penuh 165 mm.
        $flat = $render(100, false, 6.0);
        $this->assertStringContainsString('width: 165mm', $flat);
        $this->assertStringContainsString('height: 6.83mm', $flat);

        $half = $render(60, true, 6.0);
        $this->assertStringContainsString('width: 99mm', $half);
        $this->assertStringContainsString('kop-rule"></div>', $half);
    }

    public function test_naskah_paket_tetap_terbentuk_tanpa_pengaturan_baru(): void
    {
        // Data bentuk lama (LJK/paket) tidak mengirim kunci baru — harus tetap
        // merender tabel identitas, karakter, dan opsi seperti sebelumnya.
        $html = view('ljk.soal-pdf', [
            'judul' => ['NASKAH SOAL', 'TAHUN AJARAN 2026/2027'],
            'identityRows' => [
                [['Mata Pelajaran', 'Matematika'], ['Kelas', '4A']],
                [['Guru Pengampu', 'Guru Matematika'], ['Nama Peserta Didik', '']],
            ],
            'karakter' => ['Jujur'],
            'instruksi' => 'Pilihlah jawaban yang benar.',
            'blocks' => [],
            'maxOptions' => 4,
            'fontSize' => 11,
            'fontStack' => 'Times New Roman',
            'arabicStack' => 'Amiri',
            'faceCss' => '',
            'arabicFaceCss' => '',
            'hasArabic' => false,
        ])->render();

        $this->assertStringContainsString('<table class="identitas">', $html);
        $this->assertStringContainsString('Nama Peserta Didik', $html);
        $this->assertStringContainsString('PENANAMAN KARAKTER :', $html);
        $this->assertStringContainsString('Pilihlah jawaban yang benar.', $html);
        $this->assertStringContainsString('padding: 0 2mm 0 0', $html);
    }

    public function test_soal_pribadi_tidak_membuat_bank_ganda_di_daftar_bank_soal(): void
    {
        $this->makeSoal();

        // Bank milik guru (bukan auto-bank) tetap tampil.
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/bank-soal")
            ->assertOk()
            ->assertSee('Bank Matematika Kelas 4');
    }

    public function test_bank_penyimpan_soal_saya_disembunyikan_dari_koleksi(): void
    {
        $bank = app(QuickSoalService::class)->bankFor(
            $this->guru,
            $this->school,
            $this->math,
            $this->grade,
            $this->ay,
            'ganjil'
        );

        $this->assertTrue($bank->isAutoBank());
        $this->assertTrue(
            BankSoal::query()->accessibleBy($this->guru->id, $this->school->id)->whereKey($bank->id)->exists(),
            'Soal Saya tetap memakai bank penyimpan ini.'
        );
        $this->assertFalse(
            BankSoal::query()->accessibleBy($this->guru->id, $this->school->id)->koleksi()->whereKey($bank->id)->exists(),
            'Bank penyimpan Soal Saya tidak boleh tampil sebagai koleksi Bank Soal.'
        );
    }

    public function test_pratinjau_mengikuti_pengaturan_dari_url(): void
    {
        // JS pratinjau langsung mengirim semua pengaturan lewat query string.
        $soal = $this->makeSoal();

        $query = http_build_query([
            'judul' => 'UJI LIVE PREVIEW',
            'judul_align' => 'right',
            'judul_bold' => '0',
            'show_karakter' => '0',
            'karakter_title' => 'TIDAK BOLEH TAMPIL',
            'ids' => [$soal->id],
        ]);

        $response = $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/cetak/preview?{$query}");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Pengaturan JSON yang sama harus menghasilkan HTML sesuai isian.
        $html = app(SoalNaskahPdfService::class)->html(
            Soal::with(['bankSoal.subject', 'bankSoal.gradeLevel', 'options', 'wacana'])->whereKey($soal->id)->get(),
            $this->school,
            $this->guru,
            [
                'judul' => 'UJI LIVE PREVIEW',
                'judul_align' => 'right',
                'judul_bold' => false,
                'show_karakter' => false,
                'karakter_title' => 'TIDAK BOLEH TAMPIL',
            ]
        );

        $this->assertStringContainsString('UJI LIVE PREVIEW', $html);
        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringNotContainsString('TIDAK BOLEH TAMPIL', $html);
    }

    public function test_halaman_cetak_memuat_skrip_pratinjau_langsung(): void
    {
        $this->makeSoal();

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/soal-saya/cetak")
            ->assertOk()
            ->assertSee('id="settingsForm"', false)
            ->assertSee('id="pdfSettings"', false)
            ->assertSee('data-preview-base', false)
            ->assertSee('id="previewStatus"', false);
    }

    private function makeSoal(): Soal
    {
        $bank = BankSoal::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'academic_year_id' => $this->ay->id,
            'nama' => 'Bank Matematika Kelas 4',
            'jenis_soal' => 'campuran',
            'bahasa' => 'latin',
            'tingkat_kesulitan_target' => 'campuran',
            'is_public' => false,
            'shared_scope' => 'internal_school',
            'owner_user_id' => $this->guru->id,
            'created_by' => $this->guru->id,
            'total_soal' => 1,
        ]);

        $soal = Soal::create([
            'bank_soal_id' => $bank->id,
            'tipe_soal' => 'pg',
            'bahasa' => 'latin',
            'pertanyaan' => 'Berapakah hasil dari 12 x 4?',
            'bobot_default' => 1,
            'tingkat_kesulitan_estimasi' => 'mudah',
            'waktu_estimasi_menit' => 2,
            'status' => 'draft',
            'workflow_status' => Soal::WORKFLOW_DRAFT,
            'jenis_asesmen' => Soal::JENIS_ASESMEN_HARIAN,
            'dibuat_oleh' => $this->guru->id,
            'content_hash' => hash('sha256', 'soal-uji-12x4'),
        ]);

        $opsi = [['48', true], ['46', false], ['52', false], ['56', false]];
        foreach ($opsi as $i => [$teks, $benar]) {
            SoalOption::create([
                'soal_id' => $soal->id,
                'label' => chr(65 + $i),
                'teks_opsi' => $teks,
                'is_correct' => $benar,
                'urutan' => $i + 1,
            ]);
        }

        return $soal;
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

        \DB::table('permission_snapshots')->insert([
            'user_id' => $user->id,
            'scope_key' => $scopeKey,
            'scope_school_id' => $school->id,
            'fingerprint' => hash('sha256', $user->id.$scopeKey),
            'permissions' => json_encode([]),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }

    private function makeDecree(string $number, string $status): InstitutionDecree
    {
        return InstitutionDecree::create([
            'decree_number' => $number,
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'Pembagian Tugas Mengajar',
            'academic_year_id' => $this->ay->id,
            'school_id' => $this->school->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => $status,
        ]);
    }

    private function assign(Subject $subject, InstitutionDecree $decree): void
    {
        TeachingAssignment::create([
            'decree_id' => $decree->id,
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'role' => 'guru_mapel',
            'weekly_hours' => 6,
            'status' => 'active',
        ]);
    }
}
