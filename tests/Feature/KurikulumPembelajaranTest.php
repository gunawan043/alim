<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\CurriculumProfile;
use App\Models\CurriculumProject;
use App\Models\CurriculumProjectBankItem;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Kaldik;
use App\Models\KurikulumEditGrant;
use App\Models\PerangkatPembelajaran;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\SchoolCurriculumSetting;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\Ai\AiContentGenerator;
use App\Services\PekanEfektifService;
use App\Services\TeachingHoursResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rantai ekosistem:
 * Kalender → Pekan Efektif → JP Efektif → Kurikulum → CP → TP → ATP → Perangkat.
 */
class KurikulumPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private Subject $fisika;

    private User $kurikulum;

    private User $guru;

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
    // TAHAP 1 — JP EFEKTIF AKURAT (SATU ATURAN)
    // ─────────────────────────────────────────────────────────────

    public function test_jp_efektif_memakai_fallback_berjenjang_yang_sama(): void
    {
        $resolver = app(TeachingHoursResolver::class);

        // Otoritatif: jadwal kelas (study_group_subjects) → 4 JP.
        $this->assertSame(4, $resolver->resolve($this->group, $this->math));

        // Jenjang: grade_level_subjects → 5 JP.
        $this->assertSame(5, $resolver->resolve(null, $this->math, null, $this->grade->id));

        // Fisika: hanya jenjang → 3 JP.
        $this->assertSame(3, $resolver->resolve($this->group, $this->fisika));

        // Mapel tanpa sumber apa pun → default 2 JP.
        $biologi = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'BIO',
            'name' => 'Biologi',
            'credit_hours' => 0,
            'is_active' => true,
        ]);
        $this->assertSame(2, $resolver->resolve($this->group, $biologi));

        // Assignments weekly_hours=0 tidak menutup fallback.
        $this->assertSame(4, $resolver->resolve($this->group, $this->math, 0));
    }

    public function test_alur_kalender_sampai_jp_efektif_per_kelas(): void
    {
        $this->makeLiburAwalTahun();

        $service = app(PekanEfektifService::class);

        // Pekan efektif dari kalender.
        $summary = $service->summary($this->school->id, $this->ay->id, 1);
        $this->assertSame(25, $summary['minggu_efektif']);

        // JP efektif per kelas: Math 4×25=100, Fisika 3×25=75 (tanpa assignment).
        $jp = collect($service->effectiveJpForStudyGroup(
            $this->school->id,
            $this->group->id,
            $this->ay->id,
            1
        ));

        $math = $jp->firstWhere('subject', 'Matematika');
        $fisika = $jp->firstWhere('subject', 'Fisika');

        $this->assertNotNull($math);
        $this->assertSame(4, $math['weekly_hours']);
        $this->assertSame(100, $math['jp_efektif']);

        $this->assertNotNull($fisika);
        $this->assertSame(3, $fisika['weekly_hours']);
        $this->assertSame(75, $fisika['jp_efektif']);

        // JP efektif per mapel (konteks ATP/jenjang) memakai alokasi jenjang = 5 JP/minggu.
        $this->assertSame(125, $service->effectiveJpForSubject($this->school->id, $this->ay->id, 1, $this->math, $this->grade->id));
    }

    // ─────────────────────────────────────────────────────────────
    // TAHAP 2 — KURIKULUM → CP → TP → ATP
    // ─────────────────────────────────────────────────────────────

    public function test_hub_kurikulum_menghubungkan_semua_sumber(): void
    {
        $this->makeLiburAwalTahun();
        $this->makeCp();

        $this->actingAs($this->kurikulum);
        $this->get("/{$this->kurikulum->id}/kurikulum")
            ->assertOk()
            ->assertSee('Peta Kurikulum')
            ->assertSee('Matematika')
            ->assertSee('Fase')
            ->assertSee('100'); // JP efektif math = 4 × 25
    }

    public function test_akses_kelola_cp_hanya_tim_kurikulum(): void
    {
        $this->actingAs($this->guru);
        $this->post("/{$this->guru->id}/kurikulum/cp", [
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'deskripsi' => 'CP oleh guru (tidak sah).',
        ])->assertStatus(403);

        $this->actingAs($this->kurikulum);
        $this->post("/{$this->kurikulum->id}/kurikulum/cp", [
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'Peserta didik mampu memahami bilangan bulat dan operasinya.',
        ])->assertStatus(302);

        $this->assertDatabaseHas('capaian_pembelajaran', [
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_tp_diturunkan_dari_cp_dan_dapat_diurutkan(): void
    {
        $cp = $this->makeCp();

        $this->actingAs($this->kurikulum);

        // TP.01
        $this->post("/{$this->kurikulum->id}/kurikulum/tp", [
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'capaian_pembelajaran_id' => $cp->id,
            'kode_tp' => 'TP.01',
            'deskripsi' => 'Peserta didik mampu memahami bilangan bulat.',
            'alokasi_waktu' => 60,
        ])->assertStatus(302);

        $tp1 = TujuanPembelajaran::where('kode_tp', 'TP.01')->firstOrFail();
        $this->assertSame($cp->id, $tp1->capaian_pembelajaran_id);
        $this->assertSame('D', $tp1->fase); // fase turun dari CP

        // TP.02
        $this->post("/{$this->kurikulum->id}/kurikulum/tp", [
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'capaian_pembelajaran_id' => $cp->id,
            'kode_tp' => 'TP.02',
            'deskripsi' => 'Peserta didik mampu menerapkan operasi bilangan.',
            'alokasi_waktu' => 40,
        ])->assertStatus(302);

        // Kode TP duplikat ditolak.
        $this->post("/{$this->kurikulum->id}/kurikulum/tp", [
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'kode_tp' => 'TP.01',
            'deskripsi' => 'Duplikat.',
            'alokasi_waktu' => 10,
        ])->assertSessionHas('error');

        // Urutkan: TP.02 naik ke atas TP.01.
        $tp2 = TujuanPembelajaran::where('kode_tp', 'TP.02')->firstOrFail();
        $this->post("/{$this->kurikulum->id}/kurikulum/tp/{$tp2->id}/move", ['direction' => 'up'])
            ->assertStatus(302);

        $this->assertSame(1, $tp2->fresh()->urutan);
        $this->assertSame(2, $tp1->fresh()->urutan);
    }

    public function test_guru_hanya_bisa_mengelola_tp_mapel_yang_diampu(): void
    {
        $this->actingAs($this->guru);

        // Mapel yang diampu (Math) → boleh.
        $this->post("/{$this->guru->id}/kurikulum/tp", [
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'kode_tp' => 'TP.G1',
            'deskripsi' => 'TP guru matematika.',
            'alokasi_waktu' => 10,
        ])->assertStatus(302);

        // Mapel yang tidak diampu (Fisika) → ditolak.
        $this->post("/{$this->guru->id}/kurikulum/tp", [
            'subject_id' => $this->fisika->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'kode_tp' => 'TP.G2',
            'deskripsi' => 'TP guru fisika (tidak sah).',
            'alokasi_waktu' => 10,
        ])->assertStatus(403);
    }

    public function test_atp_menunjukkan_alokasi_kurang_pas_atau_lebih(): void
    {
        $this->makeLiburAwalTahun();
        $this->makeCp();

        $this->actingAs($this->kurikulum);

        // Buat TP: 60 + 40 JP.
        $tp1 = $this->makeTp('TP.01', 60);
        $tp2 = $this->makeTp('TP.02', 40);

        // Buat ATP dari UI.
        $this->post("/{$this->kurikulum->id}/kurikulum/atp", [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
        ])->assertStatus(302);

        $atp = AlurTujuanPembelajaran::firstOrFail();
        $this->assertSame('D', $atp->fase);

        // Tambahkan item: 60 + 40 = 100 JP → kurang 25 dari JP efektif jenjang (5 × 25 = 125).
        $this->post("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}/items", [
            'tujuan_pembelajaran_id' => $tp1->id,
            'jp_alokasi' => 60,
        ])->assertStatus(302);
        $this->post("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}/items", [
            'tujuan_pembelajaran_id' => $tp2->id,
            'jp_alokasi' => 40,
        ])->assertStatus(302);

        $atp->refresh();
        $this->assertSame(100, $atp->total_jp);

        $this->get("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}")
            ->assertOk()
            ->assertSee('Kurang 25 JP dari JP efektif')
            ->assertSee('125 JP');

        // Naikkan alokasi → pas (125 JP).
        $item1 = $atp->items()->first();
        $this->put("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}/items/{$item1->id}", [
            'jp_alokasi' => 85,
        ])->assertStatus(302);

        $this->get("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}")
            ->assertOk()
            ->assertSee('Alokasi pas dengan JP efektif');

        // Naikkan lagi → lebih.
        $this->put("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}/items/{$item1->id}", [
            'jp_alokasi' => 95,
        ])->assertStatus(302);

        $this->get("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}")
            ->assertOk()
            ->assertSee('Melebihi JP efektif sebanyak 10 JP');

        // Turunkan → kurang 10 JP.
        $this->put("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}/items/{$item1->id}", [
            'jp_alokasi' => 75,
        ])->assertStatus(302);

        $this->get("/{$this->kurikulum->id}/kurikulum/atp/{$atp->id}")
            ->assertOk()
            ->assertSee('Kurang 10 JP dari JP efektif');
    }

    // ─────────────────────────────────────────────────────────────
    // TAHAP 3 — PERANGKAT PEMBELAJARAN (PEMBELAJARAN MENDALAM)
    // ─────────────────────────────────────────────────────────────

    public function test_perangkat_dari_atp_memuat_desain_pembelajaran_mendalam(): void
    {
        $this->makeLiburAwalTahun();
        $tp = $this->makeTp('TP.01', 100);

        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'fase' => 'D',
            'teacher_id' => $this->guru->id,
            'status' => 'draft',
            'total_jp' => 0,
            'created_by' => $this->kurikulum->id,
        ]);

        $this->actingAs($this->guru);

        $this->post("/{$this->guru->id}/kurikulum/perangkat", [
            'atp_id' => $atp->id,
            'study_group_id' => $this->group->id,
            'judul' => 'Perangkat Matematika 7A',
        ])->assertStatus(302);

        $perangkat = PerangkatPembelajaran::firstOrFail();
        $this->assertSame($atp->id, $perangkat->atp_id);
        $this->assertSame($this->group->id, $perangkat->study_group_id);

        // Semua bagian Pembelajaran Mendalam tersedia (bukan sekadar satu field).
        $desain = $perangkat->desain;
        foreach (array_keys(PerangkatPembelajaran::DESAIN_SECTIONS) as $key) {
            $this->assertArrayHasKey($key, $desain);
        }

        // Simpan isian desain (draft — finalisasi bukan hak penyusun).
        $this->put("/{$this->guru->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Perangkat Matematika 7A',
            'status' => 'draft',
            'desain' => [
                'pertanyaan_pemantik' => 'Mengapa bilangan negatif diperlukan dalam kehidupan nyata?',
                'pemahaman_bermakna' => 'Bilangan membantu kita memahami dan menjelaskan dunia.',
                'pengalaman_memahami' => 'Siswa mengeksplorasi pola bilangan.',
                'pengalaman_mengaplikasi' => 'Siswa memecahkan masalah konteks nyata.',
                'pengalaman_refleksi' => 'Siswa merefleksikan strategi belajarnya.',
                'konteks_nyata' => 'Suhu, keuangan, dan ketinggian.',
                'asesmen_formatif' => 'Observasi dan kuis singkat.',
                'asesmen_sumatif' => 'Proyek akhir semester.',
                'diferensiasi' => 'Tingkat tantangan soal berjenjang.',
                'media_sumber' => 'Lembar kerja dan manipulatif bilangan.',
            ],
        ])->assertStatus(302);

        // Final = kunci; ditetapkan tim kurikulum (atau via approval kolektif rumpun).
        $this->actingAs($this->kurikulum);
        $this->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Perangkat Matematika 7A',
            'status' => 'final',
        ])->assertStatus(302);

        $this->actingAs($this->guru);
        $perangkat->refresh();
        $this->assertSame('final', $perangkat->status);
        $this->assertNotNull($perangkat->locked_at);
        $this->assertSame('Mengapa bilangan negatif diperlukan dalam kehidupan nyata?', $perangkat->desainValue('pertanyaan_pemantik'));

        $this->get("/{$this->guru->id}/kurikulum/perangkat/{$perangkat->id}")
            ->assertOk()
            ->assertSee('Pertanyaan Pemantik')
            ->assertSee('Merefleksikan')
            ->assertSee('Koneksi Konteks Nyata');
    }

    public function test_semua_halaman_kurikulum_render(): void
    {
        $this->actingAs($this->kurikulum);
        $this->get("/{$this->kurikulum->id}/kurikulum")->assertOk()->assertSee('Peta Kurikulum');
        $this->get("/{$this->kurikulum->id}/kurikulum/cp")->assertOk()->assertSee('Capaian Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/tp")->assertOk()->assertSee('Tujuan Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/atp")->assertOk()->assertSee('Alur Tujuan Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/prota")->assertOk()->assertSee('Program Tahunan');
        $this->get("/{$this->kurikulum->id}/kurikulum/prosem")->assertOk()->assertSee('Program Semester');
        $this->get("/{$this->kurikulum->id}/kurikulum/perangkat")->assertOk()->assertSee('Perangkat Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/realisasi")->assertOk()->assertSee('Realisasi Pembelajaran');

        // Halaman guru (Kurikulum Saya) juga render.
        $this->actingAs($this->guru);
        $this->get("/{$this->guru->id}/kurikulum/tp")->assertOk()->assertSee('Tujuan Pembelajaran');
        $this->get("/{$this->guru->id}/kurikulum/atp")->assertOk()->assertSee('Alur Tujuan Pembelajaran');
        $this->get("/{$this->guru->id}/kurikulum/perangkat")->assertOk()->assertSee('Perangkat Pembelajaran');
    }

    // ─────────────────────────────────────────────────────────────
    // KEBIJAKAN AKSES (menu & dokumen)
    // ─────────────────────────────────────────────────────────────

    public function test_tu_tidak_bisa_akses_modul_kurikulum(): void
    {
        $tu = $this->makeExtraUser('Staf TU Kurikulum', 'tu.kurikulum@test.local', 'Staf Tata Usaha');

        $this->actingAs($tu)
            ->get("/{$tu->id}/kurikulum")
            ->assertStatus(403);

        $this->actingAs($tu)
            ->get("/{$tu->id}/kurikulum/perangkat")
            ->assertStatus(403);

        $this->actingAs($tu)
            ->get("/{$tu->id}/kurikulum/rumpun")
            ->assertStatus(403);
    }

    public function test_ksp_melihat_semua_tetapi_tidak_mengelola(): void
    {
        $ksp = $this->makeExtraUser('Kepala Satuan Pendidikan', 'ksp@test.local', 'Kepala Satuan Pendidikan');

        $this->actingAs($ksp)
            ->get("/{$ksp->id}/kurikulum")
            ->assertOk()
            ->assertSee('Peta Kurikulum')
            ->assertSee('Matematika')
            ->assertSee('Fisika');

        $this->actingAs($ksp)
            ->get("/{$ksp->id}/kurikulum/tp")
            ->assertOk();

        // KSP bukan editor: menambah CP ditolak.
        $this->actingAs($ksp)
            ->post("/{$ksp->id}/kurikulum/cp", [
                'subject_id' => $this->math->id,
                'fase' => 'D',
                'elemen' => 'Bilangan',
                'deskripsi' => 'CP oleh KSP (tidak sah).',
            ])->assertStatus(403);

        // Master rumpun: boleh dilihat, tidak boleh dikelola.
        $this->actingAs($ksp)
            ->get("/{$ksp->id}/kurikulum/rumpun")
            ->assertOk();

        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kurikulum/rumpun")
            ->assertStatus(403);
    }

    public function test_peta_kurikulum_guru_melihat_seluruh_kurikulum_tanpa_hak_edit(): void
    {
        // Peta Kurikulum kini menampilkan seluruh struktur jenjang (bukan hanya mapel yang diampu),
        // namun hak edit tetap ditolak untuk guru murni.
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kurikulum")
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Fisika');

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kurikulum/kktp", [])
            ->assertForbidden();
    }

    public function test_guru_melihat_cp_serumpun_dan_bukan_mapel_lain(): void
    {
        $umum = SubjectGroup::firstOrCreate(['code' => 'umum'], ['name' => 'Umum', 'sort_order' => 1, 'is_active' => true]);
        $agama = SubjectGroup::firstOrCreate(['code' => 'agama'], ['name' => 'Agama', 'sort_order' => 2, 'is_active' => true]);

        $this->math->update(['subject_group_id' => $umum->id]);
        $this->fisika->update(['subject_group_id' => $umum->id]);

        $hadits = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'HDS',
            'name' => 'Hadits',
            'category' => 'nasional',
            'is_active' => true,
            'subject_group_id' => $agama->id,
        ]);

        // CP dibuat tim kurikulum untuk mapel serumpun (Fisika) & mapel lain (Hadits).
        $this->actingAs($this->kurikulum);
        $this->post("/{$this->kurikulum->id}/kurikulum/cp", [
            'subject_id' => $this->fisika->id,
            'fase' => 'D',
            'elemen' => 'Fisika',
            'deskripsi' => 'CP_FISIKA_SERUMPUN',
        ])->assertStatus(302);
        $this->post("/{$this->kurikulum->id}/kurikulum/cp", [
            'subject_id' => $hadits->id,
            'fase' => 'D',
            'elemen' => 'Hadits',
            'deskripsi' => 'CP_HADITS_LAIN',
        ])->assertStatus(302);

        // Guru Matematika: melihat CP serumpun (Fisika), bukan mapel di luar rumpun (Hadits).
        $this->actingAs($this->guru);
        $this->get("/{$this->guru->id}/kurikulum/cp")
            ->assertOk()
            ->assertSee('CP_FISIKA_SERUMPUN')
            ->assertDontSee('CP_HADITS_LAIN');
    }

    public function test_permintaan_akses_edit_harus_disetujui_sebelum_mengelola(): void
    {
        $umum = SubjectGroup::firstOrCreate(['code' => 'umum'], ['name' => 'Umum', 'sort_order' => 1, 'is_active' => true]);
        $this->math->update(['subject_group_id' => $umum->id]);
        $this->fisika->update(['subject_group_id' => $umum->id]);

        $serumpun = $this->makeExtraUser('Guru Serumpun Fisika', 'serumpun.fisika@test.local', 'Guru Mapel');
        $this->assignTeaching($serumpun, $this->math); // bagian rumpun Umum (tidak mengampu Fisika)

        // Halaman tersedia & sebelum disetujui tidak bisa mengelola TP Fisika.
        $this->actingAs($serumpun)
            ->get("/{$serumpun->id}/kurikulum/akses-edit")
            ->assertOk()
            ->assertSee('Permintaan Akses Edit');

        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/tp", [
                'subject_id' => $this->fisika->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'kode_tp' => 'TP.FS0',
                'deskripsi' => 'TP fisika sebelum izin (tidak sah).',
                'alokasi_waktu' => 4,
            ])->assertStatus(403);

        // Ajukan permintaan.
        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/akses-edit", [
                'subject_id' => $this->fisika->id,
                'academic_year_id' => $this->ay->id,
                'reason' => 'Ikut menyusun TP & ATP Fisika serumpun.',
            ])->assertStatus(302);

        $grant = KurikulumEditGrant::firstOrFail();
        $this->assertSame(KurikulumEditGrant::STATUS_PENDING, $grant->status);

        // Guru Matematika (bukan editor, bukan pengampu Fisika) tidak boleh memutuskan.
        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kurikulum/akses-edit/{$grant->id}/approve")
            ->assertStatus(403);

        // Tim kurikulum menyetujui.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/akses-edit/{$grant->id}/approve")
            ->assertStatus(302);
        $this->assertSame(KurikulumEditGrant::STATUS_APPROVED, $grant->fresh()->status);

        // Setelah disetujui: boleh mengelola TP Fisika.
        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/tp", [
                'subject_id' => $this->fisika->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'kode_tp' => 'TP.FS1',
                'deskripsi' => 'TP fisika oleh pemegang izin edit.',
                'alokasi_waktu' => 6,
            ])->assertStatus(302);

        // Akses dicabut kembali → tidak bisa mengelola lagi.
        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/akses-edit/{$grant->id}/revoke")
            ->assertStatus(302);
        $this->assertSame(KurikulumEditGrant::STATUS_REVOKED, $grant->fresh()->status);

        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/tp", [
                'subject_id' => $this->fisika->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'kode_tp' => 'TP.FS2',
                'deskripsi' => 'TP fisika setelah dicabut (tidak sah).',
                'alokasi_waktu' => 4,
            ])->assertStatus(403);
    }

    public function test_pengampu_mapel_dapat_menyetujui_permintaan_mapelnya(): void
    {
        $umum = SubjectGroup::firstOrCreate(['code' => 'umum'], ['name' => 'Umum', 'sort_order' => 1, 'is_active' => true]);
        $this->math->update(['subject_group_id' => $umum->id]);
        $this->fisika->update(['subject_group_id' => $umum->id]);

        $serumpun = $this->makeExtraUser('Guru Serumpun Matematika', 'serumpun.math@test.local', 'Guru Mapel');
        $this->assignTeaching($serumpun, $this->fisika); // bagian rumpun Umum (tidak mengampu Matematika)

        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/akses-edit", [
                'subject_id' => $this->math->id,
                'academic_year_id' => $this->ay->id,
                'reason' => 'Membantu menyusun ATP Matematika.',
            ])->assertStatus(302);

        $grant = KurikulumEditGrant::firstOrFail();

        // Pengampu Matematika (guru) melihat & menyetujui permintaan mapelnya.
        $this->actingAs($this->guru)
            ->get("/{$this->guru->id}/kurikulum/akses-edit")
            ->assertOk()
            ->assertSee('Guru Serumpun Matematika');

        $this->actingAs($this->guru)
            ->post("/{$this->guru->id}/kurikulum/akses-edit/{$grant->id}/approve")
            ->assertStatus(302);
        $this->assertSame(KurikulumEditGrant::STATUS_APPROVED, $grant->fresh()->status);

        // Pemegang izin dapat mengelola TP Matematika.
        $this->actingAs($serumpun)
            ->post("/{$serumpun->id}/kurikulum/tp", [
                'subject_id' => $this->math->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'kode_tp' => 'TP.MT.X1',
                'deskripsi' => 'TP matematika oleh pemegang izin (keroyokan).',
                'alokasi_waktu' => 8,
            ])->assertStatus(302);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function makeLiburAwalTahun(): Kaldik
    {
        return Kaldik::create([
            'name' => 'Libur Awal Tahun Ajaran',
            'category' => Kaldik::CATEGORY_KALDIK,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'type' => Kaldik::TYPE_LIBUR,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-12',
            'is_active' => true,
        ]);
    }

    private function makeCp(): CapaianPembelajaran
    {
        return CapaianPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'Peserta didik mampu memahami bilangan bulat dan operasinya.',
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);
    }

    private function makeTp(string $kode, int $jp): TujuanPembelajaran
    {
        return TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => $kode,
            'deskripsi' => "Tujuan pembelajaran {$kode}.",
            'elemen' => 'Bilangan',
            'alokasi_waktu' => $jp,
            'urutan' => (int) substr($kode, -1),
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Kurikulum',
            'code' => 'UTKUR',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77777777',
            'name' => 'Sekolah Uji Kurikulum',
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
            'code' => 'VII',
            'fase' => 'D',
            'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'room' => '701',
            'is_active' => true,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $this->fisika = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'FIS',
            'name' => 'Fisika',
            'credit_hours' => 2,
            'is_active' => true,
        ]);

        // Beban JP existing: jenjang + rombel (fallback berjenjang).
        GradeLevelSubject::create([
            'grade_level_id' => $this->grade->id,
            'subject_id' => $this->math->id,
            'allocation_hours' => 5,
            'is_active' => true,
        ]);
        GradeLevelSubject::create([
            'grade_level_id' => $this->grade->id,
            'subject_id' => $this->fisika->id,
            'allocation_hours' => 3,
            'is_active' => true,
        ]);

        $kurikulumRole = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web'], ['level' => 12]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->kurikulum = User::create([
            'name' => 'Koor Kurikulum',
            'email' => 'koor.kurikulum@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->kurikulum->assignRole($kurikulumRole);

        $this->guru = User::create([
            'name' => 'Guru Matematika',
            'email' => 'guru.matematika@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->guru->assignRole($guruRole);

        $this->seedPermissionSnapshot($this->kurikulum, ['jadwalkbm.read']);
        $this->seedPermissionSnapshot($this->guru, ['jadwalkbm.read']);

        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                [$this->kurikulum, 'Pimpinan & Struktural Pendidikan', 'Koordinator Kurikulum'],
                [$this->guru, 'Pendidik / Guru', 'Guru Mapel'],
            ] as [$user, $jenis, $jabatan]) {
                DB::table('gtk_employments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'school_id' => $this->school->id,
                    'status_kepegawaian' => 'GTY',
                    'jenis_gtk' => $jenis,
                    'jabatan' => $jabatan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Beban JP rombel (otoritatif untuk kelas) — math 4 JP.
        StudyGroupSubject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->guru->id,
            'weekly_hours' => 4,
            'is_active' => true,
        ]);

        // Plotting mengajar: weekly_hours 0 → memaksa fallback ke study_group_subjects.
        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/KUR/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji Kurikulum)',
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
            'teacher_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->math->id,
            'weekly_hours' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function seedPermissionSnapshot(User $user, array $permissions): void
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
            'permissions' => json_encode(array_values($permissions)),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }

    private function assignTeaching(User $user, Subject $subject): void
    {
        $decreeId = DB::table('institution_decrees')->value('id') ?: (string) Str::uuid();

        TeachingAssignment::create([
            'decree_id' => $decreeId,
            'teacher_id' => $user->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'weekly_hours' => 0,
            'status' => 'active',
        ]);
    }

    private function makeExtraUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->seedPermissionSnapshot($user, ['jadwalkbm.read']);

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

    public function test_profil_kurikulum_kbc_ditetapkan_dan_tp_membawa_nilai_cinta(): void
    {
        $this->seed(\Database\Seeders\CurriculumProfileSeeder::class);
        $kbc = CurriculumProfile::where('code', 'kbc_kemenag')->firstOrFail();

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/profil", [
                'academic_year_id' => $this->ay->id,
                'curriculum_profile_id' => $kbc->id,
                'kokurikuler_type' => 'p5ra',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_curriculum_settings', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'curriculum_profile_id' => $kbc->id,
            'kokurikuler_type' => 'p5ra',
        ]);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/profil")
            ->assertOk()
            ->assertSee('Profil Kurikulum')
            ->assertSee('Kurikulum Berbasis Cinta');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum")
            ->assertOk()
            ->assertSee('Kurikulum Berbasis Cinta');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/perangkat")
            ->assertOk()
            ->assertSee('Daftar Modul Ajar')
            ->assertSee('Kurikulum Berbasis Cinta');

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/tp", [
                'subject_id' => $this->math->id,
                'grade_level_id' => $this->grade->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'kode_tp' => 'TP-KBC-01',
                'deskripsi' => 'Peserta didik mampu membaca ayat dengan penuh cinta kepada Allah.',
                'kbc_values' => ['hubbullah', 'hubbunnaas'],
                'kbc_indicator' => 'Menunjukkan sikap cinta kepada Allah dan sesama saat pembelajaran.',
                'alokasi_waktu' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $tp = TujuanPembelajaran::where('kode_tp', 'TP-KBC-01')->firstOrFail();
        $this->assertSame(['hubbullah', 'hubbunnaas'], $tp->kbc_values);
        $this->assertSame($kbc->id, $tp->curriculum_profile_id);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/tp")
            ->assertOk()
            ->assertSee('Cinta kepada Allah')
            ->assertSee('Cinta kepada Sesama');

        $perangkat = new PerangkatPembelajaran(['tipe' => PerangkatPembelajaran::TIPE_UMUM]);
        $perangkat->setRelation('curriculumProfile', $kbc);
        $this->assertArrayHasKey('kbc', $perangkat->groups());
    }

    public function test_projek_p5ra_kbc_disimpan_dengan_dimensi_dan_aktivitas(): void
    {
        $this->seed(\Database\Seeders\CurriculumProfileSeeder::class);
        $kbc = CurriculumProfile::where('code', 'kbc_kemenag')->firstOrFail();

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/profil", [
                'academic_year_id' => $this->ay->id,
                'curriculum_profile_id' => $kbc->id,
                'kokurikuler_type' => 'p5ra',
            ])
            ->assertRedirect();

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/proyek", [
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
                'type' => 'p5ra',
                'title' => 'Kampung Cinta Lingkungan',
                'theme' => 'Gaya Hidup Berkelanjutan',
                'description' => 'Projek kolaboratif membersihkan lingkungan madrasah.',
                'dimensions' => ['hubbulbiah', 'hubbunnaas'],
                'grade_level_ids' => [$this->grade->id],
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'status' => 'active',
                'activities' => [
                    ['judul' => 'Observasi lingkungan', 'deskripsi' => 'Mengamati kondisi kebersihan.', 'tanggal' => '2026-08-05'],
                    ['judul' => 'Aksi bersih-bersih', 'deskripsi' => 'Kerja bakti bersama.', 'tanggal' => '2026-08-12'],
                ],
            ])
            ->assertRedirect();

        $project = CurriculumProject::firstOrFail();
        $this->assertSame('p5ra', $project->type);
        $this->assertSame(['hubbulbiah', 'hubbunnaas'], $project->dimensions);
        $this->assertCount(2, $project->activities);
        $this->assertSame($kbc->id, $project->curriculum_profile_id);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/proyek")
            ->assertOk()
            ->assertSee('Kampung Cinta Lingkungan')
            ->assertSee('Cinta kepada Lingkungan');

        $student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Projek',
            'nis' => 'PRJ-01',
            'nisn' => 'PRJ-01',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/proyek/{$project->id}/nilai", [
                'student_id' => $student->id,
                'scores' => ['hubbulbiah' => 4, 'hubbunnaas' => 3],
                'notes' => 'Aktif dan peduli lingkungan.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('curriculum_project_assessments', [
            'project_id' => $project->id,
            'student_id' => $student->id,
            'average' => 3.50,
            'predicate' => 'Sangat Baik',
        ]);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/proyek/{$project->id}")
            ->assertOk()
            ->assertSee('Santri Projek')
            ->assertSee('Sangat Baik');

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/proyek/{$project->id}/cetak")
            ->assertOk()
            ->assertSee('Laporan Projek')
            ->assertSee('Santri Projek');

        $pdf = $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/proyek/{$project->id}/pdf");
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_prompt_ai_kbc_memuat_profil_nilai_dan_struktur_kbc(): void
    {
        $generator = app(AiContentGenerator::class);
        $method = new \ReflectionMethod($generator, 'rpmMessages');
        $messages = $method->invoke($generator, [
            'subject' => 'Akidah Akhlak',
            'grade' => 'Kelas VIII',
            'fase' => 'D',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'topik' => 'Adab kepada sesama',
            'jumlah_pertemuan' => 2,
            'bahasa' => 'id',
            'instruksi' => null,
            'curriculum' => [
                'code' => 'kbc_kemenag',
                'name' => 'Kurikulum Berbasis Cinta (KBC) — Kemenag',
                'authority' => 'kemenag',
                'labels' => ['unit_label' => 'Madrasah', 'perangkat_label' => 'Modul Ajar', 'kokurikuler_label' => 'P5RA'],
                'values' => ['hubbullah' => 'Cinta kepada Allah', 'hubbunnaas' => 'Cinta kepada Sesama'],
                'kokurikuler' => 'P5RA',
            ],
        ]);

        $prompt = $messages[0]['content'].' '.$messages[1]['content'];
        $this->assertStringContainsString('Kurikulum Berbasis Cinta', $prompt);
        $this->assertStringContainsString('Cinta kepada Allah', $prompt);
        $this->assertStringContainsString('"kbc"', $prompt);
        $this->assertStringContainsString('P5RA', $prompt);
    }

    public function test_perangkat_kbc_tidak_boleh_final_tanpa_integrasi_nilai_cinta(): void
    {
        $this->seed(\Database\Seeders\CurriculumProfileSeeder::class);
        $kbc = CurriculumProfile::where('code', 'kbc_kemenag')->firstOrFail();

        SchoolCurriculumSetting::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'curriculum_profile_id' => $kbc->id,
            'kokurikuler_type' => 'p5ra',
        ]);

        $this->makeLiburAwalTahun();
        $tp = $this->makeTp('TP.KBC.01', 100);

        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'fase' => 'D',
            'teacher_id' => $this->guru->id,
            'status' => 'draft',
            'total_jp' => 0,
            'created_by' => $this->kurikulum->id,
        ]);

        $this->actingAs($this->guru);
        $this->post("/{$this->guru->id}/kurikulum/perangkat", [
            'atp_id' => $atp->id,
            'study_group_id' => $this->group->id,
            'judul' => 'Modul Ajar KBC Matematika',
        ])->assertRedirect();

        $perangkat = PerangkatPembelajaran::firstOrFail();
        $perangkat->update(['curriculum_profile_id' => $kbc->id]);

        $this->actingAs($this->kurikulum);
        $this->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Modul Ajar KBC Matematika',
            'status' => 'final',
        ])->assertSessionHas('error');

        $this->assertSame('draft', $perangkat->fresh()->status);

        $this->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Modul Ajar KBC Matematika',
            'status' => 'final',
            'desain' => [
                'tema_kbc' => 'Cinta kepada Allah',
                'materi_insersi' => 'Bilangan sebagai tanda keteraturan ciptaan Allah.',
                'pilar_kbc' => 'Cinta kepada Allah',
                'indikator_kbc' => 'Peserta didik menunjukkan rasa syukur atas keteraturan.',
                'refleksi_kbc' => 'Apa yang membuat saya bersyukur hari ini?',
                'asesmen_kbc' => 'Observasi sikap syukur dan tanggung jawab.',
            ],
        ])->assertRedirect();

        $this->assertSame('final', $perangkat->fresh()->status);

        $this->get("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}")
            ->assertOk()
            ->assertSee('Checklist Integrasi KBC')
            ->assertSee('100% lengkap');
    }

    public function test_bank_tema_dan_aktivitas_p5ra_dapat_disimpan(): void
    {
        $this->seed(\Database\Seeders\CurriculumProfileSeeder::class);
        $kbc = CurriculumProfile::where('code', 'kbc_kemenag')->firstOrFail();

        SchoolCurriculumSetting::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'curriculum_profile_id' => $kbc->id,
            'kokurikuler_type' => 'p5ra',
        ]);

        $this->actingAs($this->kurikulum)
            ->post("/{$this->kurikulum->id}/kurikulum/proyek/bank", [
                'academic_year_id' => $this->ay->id,
                'type' => 'p5ra',
                'kind' => 'theme',
                'title' => 'Cinta Lingkungan Sekolah',
                'description' => 'Tema internal untuk aksi kebersihan lingkungan.',
                'dimensions' => ['hubbulbiah'],
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('curriculum_project_bank_items', [
            'school_id' => $this->school->id,
            'type' => 'p5ra',
            'kind' => 'theme',
            'title' => 'Cinta Lingkungan Sekolah',
        ]);

        $this->actingAs($this->kurikulum)
            ->get("/{$this->kurikulum->id}/kurikulum/proyek/bank")
            ->assertOk()
            ->assertSee('Cinta Lingkungan Sekolah')
            ->assertSee('Cinta kepada Lingkungan');
    }
}
