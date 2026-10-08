<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Kaldik;
use App\Models\PerangkatPembelajaran;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
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

        // Simpan isian desain.
        $this->put("/{$this->guru->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Perangkat Matematika 7A',
            'status' => 'final',
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

        $perangkat->refresh();
        $this->assertSame('final', $perangkat->status);
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
        $this->get("/{$this->kurikulum->id}/kurikulum/cp")->assertOk()->assertSee('Capaian Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/tp")->assertOk()->assertSee('Tujuan Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/atp")->assertOk()->assertSee('Alur Tujuan Pembelajaran');
        $this->get("/{$this->kurikulum->id}/kurikulum/perangkat")->assertOk()->assertSee('Perangkat Pembelajaran');

        // Halaman guru (Kurikulum Saya) juga render.
        $this->actingAs($this->guru);
        $this->get("/{$this->guru->id}/kurikulum/tp")->assertOk()->assertSee('Tujuan Pembelajaran');
        $this->get("/{$this->guru->id}/kurikulum/atp")->assertOk()->assertSee('Alur Tujuan Pembelajaran');
        $this->get("/{$this->guru->id}/kurikulum/perangkat")->assertOk()->assertSee('Perangkat Pembelajaran');
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
}
