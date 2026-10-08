<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Models\AcademicYear;
use App\Models\AdminJurnalPembelajaran;
use App\Models\AlurTujuanPembelajaran;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\Kaldik;
use App\Models\NilaiFormatif;
use App\Models\NilaiSumatif;
use App\Models\PerangkatPembelajaran;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
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
 * Pelaksanaan Pembelajaran:
 * RPM/PROSEM → Jurnal → realisasi TP/ATP → asesmen formatif/sumatif → Buku Administrasi.
 */
class PelaksanaanPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private User $guru;

    private TeacherAdminBook $book;

    private AlurTujuanPembelajaran $atp;

    private $prosemItem;

    private PerangkatPembelajaran $rpm;

    private string $studentId;

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
    // JURNAL TERHUBUNG RENCANA
    // ─────────────────────────────────────────────────────────────

    public function test_halaman_jurnal_menampilkan_rencana_dan_realisasi(): void
    {
        $this->actingAs($this->guru);

        $this->get("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2")
            ->assertOk()
            ->assertSee('Jurnal Pembelajaran')
            ->assertSee('Rencana Pekan (PROSEM)')
            ->assertSee('TP yang Diajarkan')
            ->assertSee('RPM Digunakan')
            ->assertSee('Realisasi TP');
    }

    public function test_jurnal_menyimpan_tautan_prosem_tp_dan_rpm(): void
    {
        $this->actingAs($this->guru);

        $this->post("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2", [
            'meeting_number' => 1,
            'meeting_date' => '2026-07-13', // pekan efektif pertama (setelah libur)
            'time_in' => '07:00',
            'time_out' => '08:30',
            'material' => 'Bilangan bulat dan operasinya.',
            'prosem_item_id' => $this->prosemItem->id,
            'perangkat_pembelajaran_id' => $this->rpm->id,
            'teacher_signature' => 'Terverifikasi',
        ])->assertStatus(302);

        $journal = AdminJurnalPembelajaran::firstOrFail();

        $this->assertSame($this->prosemItem->id, $journal->prosem_item_id);
        $this->assertSame($this->rpm->id, $journal->perangkat_pembelajaran_id);
        // TP diambil otomatis dari item PROSEM (tidak diinput ulang).
        $this->assertSame($this->prosemItem->tujuan_pembelajaran_id, $journal->tujuan_pembelajaran_id);
        $this->assertSame('Terverifikasi', $journal->teacher_signature);

        $this->get("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2")
            ->assertOk()
            ->assertSee('TP.01')
            ->assertSee('1/2'); // realisasi 1 dari 2 TP
    }

    public function test_jurnal_menolak_tautan_rencana_yang_tidak_sesuai(): void
    {
        // TP dari mapel lain (Fisika) → tidak boleh dihubungkan ke jurnal Matematika.
        $fisika = Subject::create(['school_id' => $this->school->id, 'code' => 'FIS', 'name' => 'Fisika', 'credit_hours' => 3, 'is_active' => true]);

        $tpFisika = TujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $fisika->id,
            'grade_level_id' => $this->grade->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'fase' => 'D',
            'kode_tp' => 'TP.FIS.01',
            'deskripsi' => 'TP fisika.',
            'alokasi_waktu' => 4,
            'urutan' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->guru);

        $response = $this->post("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2", [
            'meeting_number' => 2,
            'meeting_date' => '2026-07-20',
            'material' => 'Tidak sesuai rencana.',
            'tujuan_pembelajaran_id' => $tpFisika->id,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertSame(0, AdminJurnalPembelajaran::count());
    }

    public function test_jurnal_tanpa_tautan_rencana_tetap_kompatibel(): void
    {
        $this->actingAs($this->guru);

        $this->post("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2", [
            'meeting_number' => 1,
            'meeting_date' => '2026-07-13',
            'material' => 'Jurnal manual tanpa rencana.',
        ])->assertStatus(302);

        $journal = AdminJurnalPembelajaran::firstOrFail();
        $this->assertNull($journal->prosem_item_id);
        $this->assertNull($journal->tujuan_pembelajaran_id);
    }

    // ─────────────────────────────────────────────────────────────
    // REALISASI → ASESMEN → BUKU ADMINISTRASI
    // ─────────────────────────────────────────────────────────────

    public function test_realisasi_menghitung_progress_dan_status_asesmen(): void
    {
        $this->makeJournalForFirstTp();

        // Asesmen formatif & sumatif untuk buku ini (Buku Administrasi → Leger → Rapor).
        NilaiFormatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->studentId,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'skor_lkpd' => 85,
            'skor_kuis' => 90,
            'nr_final' => 87.5,
        ]);

        NilaiSumatif::create([
            'admin_book_id' => $this->book->id,
            'student_id' => $this->studentId,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            's1' => 80,
            'rs' => 80,
            'nr_final' => 80,
        ]);

        $this->actingAs($this->guru);

        $response = $this->get("/{$this->guru->id}/kurikulum/realisasi")
            ->assertOk()
            ->assertSee('Realisasi Pembelajaran')
            ->assertSee('Realisasi per Buku Administrasi')
            ->assertSee('50%'); // 1 dari 2 TP terealisasi

        // Nilai asesmen terbaca dari Buku Administrasi.
        $response->assertSee('1</span>', false);
    }

    public function test_prosem_dan_atp_menampilkan_realisasi_jurnal(): void
    {
        $this->makeJournalForFirstTp();

        $this->actingAs($this->guru);

        $this->get("/{$this->guru->id}/kurikulum/prosem/{$this->prosemItem->prosem_id}")
            ->assertOk()
            ->assertSee('1 pertemuan');

        $this->get("/{$this->guru->id}/kurikulum/atp/{$this->atp->id}")
            ->assertOk()
            ->assertSee('1×');
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE / HELPERS
    // ─────────────────────────────────────────────────────────────

    private function makeJournalForFirstTp(): void
    {
        $this->actingAs($this->guru);

        $this->post("/{$this->guru->id}/schools/guru-mapel/{$this->book->id}/w2", [
            'meeting_number' => 1,
            'meeting_date' => '2026-07-13',
            'material' => 'Bilangan bulat.',
            'prosem_item_id' => $this->prosemItem->id,
            'perangkat_pembelajaran_id' => $this->rpm->id,
            'teacher_signature' => 'Terverifikasi',
        ])->assertStatus(302);
    }

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Pelaksanaan',
            'code' => 'UTPLK',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '22222222',
            'name' => 'SMP Uji Pelaksanaan',
            'principal_name' => 'Kepala Uji',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->grade = GradeLevel::create(['school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $this->group = StudyGroup::create(['school_id' => $this->school->id, 'academic_year_id' => $this->ay->id, 'grade_level_id' => $this->grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true]);

        $this->math = Subject::create(['school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika', 'credit_hours' => 5, 'is_active' => true]);
        GradeLevelSubject::create(['grade_level_id' => $this->grade->id, 'subject_id' => $this->math->id, 'allocation_hours' => 5, 'is_active' => true]);

        // User + konteks sekolah.
        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->guru = User::create(['name' => 'Guru Jurnal', 'email' => 'guru.jurnal@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guru->assignRole($role);
        $this->seedPermissionSnapshot($this->guru, ['jadwalkbm.read']);

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('gtk_employments')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $this->guru->id,
                'school_id' => $this->school->id,
                'status_kepegawaian' => 'GTY',
                'jenis_gtk' => 'Pendidik / Guru',
                'jabatan' => 'Guru Mapel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Santri untuk nilai asesmen.
            $this->studentId = (string) Str::uuid();
            DB::table('students')->insert([
                'id' => $this->studentId,
                'school_id' => $this->school->id,
                'nisn' => '999000111',
                'name' => 'Santri Uji Jurnal',
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

        // Buku Administrasi guru.
        $this->book = TeacherAdminBook::create([
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->math->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        // Kalender → Pekan Efektif.
        Kaldik::create([
            'name' => 'Libur Awal Tahun',
            'category' => Kaldik::CATEGORY_KALDIK,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'type' => Kaldik::TYPE_LIBUR,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-12',
            'is_active' => true,
        ]);
        app(PekanEfektifService::class)->generate($this->school->id, $this->ay->id, 1);

        // CP → TP → ATP.
        $cp = CapaianPembelajaran::create([
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'fase' => 'D',
            'elemen' => 'Bilangan',
            'deskripsi' => 'CP Matematika fase D.',
            'urutan' => 1,
            'is_active' => true,
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
            'alokasi_waktu' => 60,
            'urutan' => 1,
            'is_active' => true,
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
            'alokasi_waktu' => 65,
            'urutan' => 2,
            'is_active' => true,
        ]);

        $this->atp = AlurTujuanPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'fase' => 'D',
            'teacher_id' => $this->guru->id,
            'status' => 'published',
            'total_jp' => 0,
            'created_by' => $this->guru->id,
        ]);

        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $this->atp->id,
            'tujuan_pembelajaran_id' => $tp1->id,
            'urutan' => 1,
            'jp_alokasi' => 60,
        ]);
        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $this->atp->id,
            'tujuan_pembelajaran_id' => $tp2->id,
            'urutan' => 2,
            'jp_alokasi' => 65,
        ]);
        $this->atp->recalculateTotal();

        // PROTA → PROSEM (rencana pekan).
        $prota = app(ProtaService::class)->createFromAtp($this->atp->fresh(), $this->guru);
        $prosem = app(ProsemService::class)->createFromProta($prota, $this->guru);
        $this->prosemItem = $prosem->items()->orderBy('urutan')->firstOrFail();

        // RPM.
        $this->rpm = PerangkatPembelajaran::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'subject_id' => $this->math->id,
            'grade_level_id' => $this->grade->id,
            'study_group_id' => $this->group->id,
            'atp_id' => $this->atp->id,
            'teacher_id' => $this->guru->id,
            'judul' => 'RPM Matematika 7A',
            'tipe' => 'umum',
            'status' => 'draft',
            'desain' => PerangkatPembelajaran::defaultDesain(),
            'created_by' => $this->guru->id,
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
