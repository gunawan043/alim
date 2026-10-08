<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Kaldik;
use App\Models\PerangkatPembelajaran;
use App\Models\Prosem;
use App\Models\Prota;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\PekanEfektifService;
use App\Services\ProsemService;
use App\Services\ProtaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PROTA → PROSEM → RPM → Cetak PDF.
 */
class ProtaProsemRpmTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private Subject $fisika;

    private User $kurikulum;

    private User $guru1;

    private User $guru2;

    private User $guru3;

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
    // PROTA
    // ─────────────────────────────────────────────────────────────

    public function test_prota_dibangun_dari_atp_dan_pekan_efektif(): void
    {
        $this->makeLiburAwalTahun();
        $this->generatePekan();
        $atp = $this->makeAtp(60, 40);

        $this->actingAs($this->guru1);
        $this->post("/{$this->guru1->id}/kurikulum/prota", ['atp_id' => $atp->id])->assertStatus(302);

        $prota = Prota::firstOrFail();

        $this->assertSame(25, $prota->minggu_efektif);       // dari Pekan Efektif
        $this->assertSame(5, $prota->jp_per_minggu);         // dari resolver (grade level)
        $this->assertSame(125, $prota->jp_efektif);          // 5 × 25
        $this->assertSame(100, $prota->total_jp);            // dari ATP (60 + 40)
        $this->assertCount(2, $prota->items);
        $this->assertSame('Bilangan', $prota->items->first()->bab);

        $this->get("/{$this->guru1->id}/kurikulum/prota/{$prota->id}")
            ->assertOk()
            ->assertSee('Rincian TP / BAB / Materi')
            ->assertSee('125')
            ->assertSee('100');
    }

    public function test_prota_menandai_perlu_diperbarui_dan_sinkron(): void
    {
        $this->makeLiburAwalTahun();
        $this->generatePekan();
        $atp = $this->makeAtp(60, 40);

        $prota = app(ProtaService::class)->createFromAtp($atp, $this->guru1);

        // Perubahan kalender: tambah 1 minggu libur penuh → Pekan Efektif berubah.
        $this->makeKaldik('Libur Tambahan', 'libur', '2026-08-03', '2026-08-08');
        $this->generatePekan();

        $this->actingAs($this->guru1);
        $this->get("/{$this->guru1->id}/kurikulum/prota/{$prota->id}")
            ->assertOk()
            ->assertSee('PROTA perlu diperbarui')
            ->assertSee('Pekan Efektif berubah');

        $this->post("/{$this->guru1->id}/kurikulum/prota/{$prota->id}/sync")->assertStatus(302);

        $prota->refresh();
        $this->assertSame(24, $prota->minggu_efektif);
        $this->assertSame(120, $prota->jp_efektif);
        $this->assertSame(100, $prota->total_jp);
    }

    // ─────────────────────────────────────────────────────────────
    // PROSEM
    // ─────────────────────────────────────────────────────────────

    public function test_prosem_mendistribusikan_ke_pekan_efektif_dan_menandai_ujian(): void
    {
        $this->makeLiburAwalTahun();
        $this->makeKaldik('Sumatif Akhir Semester', 'ujian', '2026-12-01', '2026-12-05');
        $this->generatePekan();

        $atp = $this->makeAtp(60, 60); // 120 JP → 24 pekan @5 JP
        $prota = app(ProtaService::class)->createFromAtp($atp, $this->guru1);

        $this->actingAs($this->guru1);
        $this->post("/{$this->guru1->id}/kurikulum/prosem", ['prota_id' => $prota->id])->assertStatus(302);

        $prosem = Prosem::firstOrFail();
        $items = $prosem->items()->orderBy('urutan')->get();

        $this->assertCount(2, $items);
        $this->assertSame(120, (int) $items->sum('jp'));

        // Minggu 1–2 libur → distribusi mulai pekan efektif pertama (pekan 3).
        $this->assertSame(3, $items->first()->mulai_minggu_ke);

        // Item terakhir menjangkau pekan ujian → keterangan otomatis.
        $this->assertStringContainsString('Pekan ujian', (string) $items->last()->keterangan);

        $this->get("/{$this->guru1->id}/kurikulum/prosem/{$prosem->id}")
            ->assertOk()
            ->assertSee('Distribusi TP / Materi')
            ->assertSee('Pekan 3')
            ->assertSee('Pekan ujian');
    }

    // ─────────────────────────────────────────────────────────────
    // RPM (TERSTRUKTUR + SERUMPUN)
    // ─────────────────────────────────────────────────────────────

    public function test_rpm_umum_dan_agama_memakai_struktur_berbeda(): void
    {
        $atp = $this->makeAtp(60, 40);

        $this->actingAs($this->guru1);

        // RPM Umum
        $this->post("/{$this->guru1->id}/kurikulum/perangkat", [
            'atp_id' => $atp->id,
            'judul' => 'RPM Umum Matematika',
            'tipe' => 'umum',
            'study_group_id' => $this->group->id,
        ])->assertStatus(302);

        $umum = PerangkatPembelajaran::where('tipe', 'umum')->firstOrFail();

        $this->put("/{$this->guru1->id}/kurikulum/perangkat/{$umum->id}", [
            'judul' => 'RPM Umum Matematika',
            'tipe' => 'umum',
            'status' => 'draft',
            'desain' => [
                'identifikasi_peserta_didik' => 'Siswa kelas 7 dengan beragam gaya belajar.',
                'profil_lulusan' => 'Penalaran kritis dan kolaborasi.',
                'topik' => 'Bilangan Bulat',
                'praktik_pedagogik' => 'Pembelajaran berbasis masalah.',
                'model_pembelajaran' => 'Problem Based Learning',
                'kegiatan_awal' => 'Pertanyaan pemantik tentang suhu.',
                'pengalaman_refleksi' => 'Refleksi jurnal belajar.',
                'penutup' => 'Simpulan bersama.',
                'asesmen_sumatif' => 'Proyek akhir bab.',
                'pertanyaan_pemantik' => 'Mengapa bilangan negatif diperlukan?',
            ],
        ])->assertStatus(302);

        $umum->refresh();
        $this->assertSame('Problem Based Learning', $umum->desainValue('model_pembelajaran'));
        $this->assertSame('Mengapa bilangan negatif diperlukan?', $umum->desainValue('pertanyaan_pemantik'));

        $this->get("/{$this->guru1->id}/kurikulum/perangkat/{$umum->id}")
            ->assertOk()
            ->assertSee('Profil Lulusan')
            ->assertSee('Praktik Pedagogik')
            ->assertSee('Kegiatan Awal')
            ->assertSee('Merefleksikan')
            ->assertSee('Penutup');

        // RPM Agama — struktur tanpa bagian umum.
        $this->post("/{$this->guru1->id}/kurikulum/perangkat", [
            'atp_id' => $atp->id,
            'judul' => 'RPM Agama',
            'tipe' => 'agama',
        ])->assertStatus(302);

        $agama = PerangkatPembelajaran::where('tipe', 'agama')->firstOrFail();

        $this->get("/{$this->guru1->id}/kurikulum/perangkat/{$agama->id}")
            ->assertOk()
            ->assertSee('Kemitraan Pembelajaran')
            ->assertDontSee('Profil Lulusan');
    }

    public function test_rpm_serumpun_dapat_melihat_tetapi_hanya_penyusun_yang_mengubah(): void
    {
        $atp = $this->makeAtp(60, 40);

        $this->actingAs($this->guru1);
        $this->post("/{$this->guru1->id}/kurikulum/perangkat", [
            'atp_id' => $atp->id,
            'judul' => 'RPM Bersama Matematika',
            'tipe' => 'umum',
        ])->assertStatus(302);

        $perangkat = PerangkatPembelajaran::firstOrFail();

        // Guru serumpun (Matematika) → boleh lihat.
        $this->actingAs($this->guru2);
        $this->get("/{$this->guru2->id}/kurikulum/perangkat/{$perangkat->id}")
            ->assertOk()
            ->assertSee('RPM bersama serumpun');
        $this->put("/{$this->guru2->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Diubah serumpun',
            'status' => 'draft',
        ])->assertStatus(403);

        // Guru mapel lain → tidak boleh lihat.
        $this->actingAs($this->guru3);
        $this->get("/{$this->guru3->id}/kurikulum/perangkat/{$perangkat->id}")->assertStatus(403);

        // Tim kurikulum → boleh lihat & ubah.
        $this->actingAs($this->kurikulum);
        $this->get("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}")->assertOk();
        $this->put("/{$this->kurikulum->id}/kurikulum/perangkat/{$perangkat->id}", [
            'judul' => 'Diverifikasi Kurikulum',
            'tipe' => 'umum',
            'status' => 'final',
        ])->assertStatus(302);
    }

    // ─────────────────────────────────────────────────────────────
    // CETAK PDF (6 DOKUMEN)
    // ─────────────────────────────────────────────────────────────

    public function test_cetak_pdf_semua_dokumen_menghasilkan_pdf(): void
    {
        $this->makeLiburAwalTahun();
        $this->makeKaldik('Sumatif Akhir Semester', 'ujian', '2026-12-01', '2026-12-05');
        $this->generatePekan();

        $atp = $this->makeAtp(60, 40);
        $prota = app(ProtaService::class)->createFromAtp($atp, $this->guru1);
        $prosem = app(ProsemService::class)->createFromProta($prota, $this->guru1);

        $perangkat = PerangkatPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'study_group_id' => $this->group->id,
            'atp_id' => $atp->id,
            'teacher_id' => $this->guru1->id,
            'judul' => 'RPM Uji Cetak',
            'tipe' => 'umum',
            'status' => 'draft',
            'desain' => array_merge(PerangkatPembelajaran::defaultDesain(), [
                'model_pembelajaran' => 'Problem Based Learning',
                'kegiatan_awal' => 'Apersepsi.',
                'pengalaman_refleksi' => 'Refleksi akhir.',
                'asesmen_formatif' => 'Kuis singkat.',
            ]),
            'created_by' => $this->guru1->id,
        ]);

        $this->actingAs($this->guru1);

        $urls = [
            'kaldik' => route('user.kurikulum.cetak.kaldik', ['userId' => $this->guru1->id, 'academic_year_id' => $this->ay->id]),
            'pekan-efektif' => route('user.kurikulum.cetak.pekan-efektif', ['userId' => $this->guru1->id, 'academic_year_id' => $this->ay->id, 'semester' => 1]),
            'atp' => route('user.kurikulum.cetak.atp', ['userId' => $this->guru1->id, 'id' => $atp->id]),
            'prota' => route('user.kurikulum.cetak.prota', ['userId' => $this->guru1->id, 'id' => $prota->id]),
            'prosem' => route('user.kurikulum.cetak.prosem', ['userId' => $this->guru1->id, 'id' => $prosem->id]),
            'rpm' => route('user.kurikulum.cetak.rpm', ['userId' => $this->guru1->id, 'id' => $perangkat->id]),
        ];

        foreach ($urls as $label => $url) {
            $response = $this->get($url);

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'), "Dokumen {$label} bukan PDF.");
            $this->assertStringStartsWith('%PDF', $response->getContent(), "Dokumen {$label} gagal dirender.");
        }
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function makeLiburAwalTahun(): Kaldik
    {
        return $this->makeKaldik('Libur Awal Tahun Ajaran', 'libur', '2026-07-01', '2026-07-12');
    }

    private function makeKaldik(string $name, string $type, string $start, string $end): Kaldik
    {
        return Kaldik::create([
            'name' => $name,
            'category' => Kaldik::CATEGORY_KALDIK,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'is_active' => true,
        ]);
    }

    private function generatePekan(): void
    {
        app(PekanEfektifService::class)->generate($this->school->id, $this->ay->id, 1);
    }

    private function makeAtp(int $jp1, int $jp2): AlurTujuanPembelajaran
    {
        $cp = CapaianPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'CP Matematika fase D.',
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $tp1 = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => 'TP.01',
            'deskripsi' => 'Memahami bilangan bulat.',
            'elemen' => 'Bilangan',
            'alokasi_waktu' => $jp1,
            'urutan' => 1,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $tp2 = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'capaian_pembelajaran_id' => $cp->id,
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => 'TP.02',
            'deskripsi' => 'Menerapkan operasi aljabar.',
            'elemen' => 'Aljabar',
            'alokasi_waktu' => $jp2,
            'urutan' => 2,
            'is_active' => true,
            'created_by' => $this->kurikulum->id,
        ]);

        $atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'fase' => 'D',
            'teacher_id' => $this->guru1->id,
            'status' => 'draft',
            'total_jp' => 0,
            'created_by' => $this->kurikulum->id,
        ]);

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp1->id,
            'urutan' => 1,
            'jp_alokasi' => $jp1,
        ]);

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp2->id,
            'urutan' => 2,
            'jp_alokasi' => $jp2,
        ]);

        $atp->recalculateTotal();

        return $atp->fresh();
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test PROTA',
            'code' => 'UTPROTA',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '12345678',
            'name' => 'SMP Uji Kurikulum',
            'address' => 'Jl. Uji No. 1',
            'principal_name' => 'Kepala Uji Sekolah',
            'principal_nip' => '197001012000011001',
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

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->math = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 5, 'is_active' => true]);
        $this->fisika = Subject::create(['school_id' => $this->school->id, 'code' => 'FIS', 'name' => 'Fisika', 'credit_hours' => 3, 'is_active' => true]);

        GradeLevelSubject::create(['grade_level_id' => $this->grade->id, 'subject_id' => $this->math->id, 'allocation_hours' => 5, 'is_active' => true]);
        GradeLevelSubject::create(['grade_level_id' => $this->grade->id, 'subject_id' => $this->fisika->id, 'allocation_hours' => 3, 'is_active' => true]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->kurikulum = User::create(['name' => 'Koor Kurikulum', 'email' => 'koor.prota@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guru1 = User::create(['name' => 'Guru Matematika 1', 'email' => 'guru.mat1@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guru2 = User::create(['name' => 'Guru Matematika 2', 'email' => 'guru.mat2@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guru3 = User::create(['name' => 'Guru Fisika', 'email' => 'guru.fis@test.local', 'password' => bcrypt('password'), 'is_active' => true]);

        foreach ([$this->kurikulum, $this->guru1, $this->guru2, $this->guru3] as $user) {
            $user->assignRole($role);
            $this->seedPermissionSnapshot($user, ['jadwalkbm.read']);
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                [$this->kurikulum, 'Koordinator Kurikulum'],
                [$this->guru1, 'Guru Mapel'],
                [$this->guru2, 'Guru Mapel'],
                [$this->guru3, 'Guru Mapel'],
            ] as [$user, $jabatan]) {
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
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/PROTA/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji PROTA)',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'issued_date' => now()->format('Y-m-d'),
            'effective_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$this->guru1, $this->math, 5],
            [$this->guru2, $this->math, 5],
            [$this->guru3, $this->fisika, 3],
        ] as [$teacher, $subject, $hours]) {
            TeachingAssignment::create([
                'decree_id' => $decreeId,
                'teacher_id' => $teacher->id,
                'school_id' => $this->school->id,
                'academic_year_id' => $this->ay->id,
                'study_group_id' => $this->group->id,
                'subject_id' => $subject->id,
                'weekly_hours' => $hours,
                'status' => 'active',
            ]);
        }
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
