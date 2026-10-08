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
use App\Models\Prosem;
use App\Models\ProsemItem;
use App\Models\Prota;
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
 * Manual Adjustment PROSEM:
 *   otomatis → [Atur Distribusi] → pilih pekan efektif → validasi JP → simpan → Disesuaikan.
 * Perubahan Kaldik/Pekan Efektif tidak menghapus penyesuaian — hanya menandai Tidak Valid.
 */
class ProsemAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private StudyGroup $group;

    private Subject $math;

    private User $kurikulum;

    private User $guru;

    private User $guruLain;

    private Prosem $prosem;

    private ProsemItem $item1;

    private ProsemItem $item2;

    private TeacherAdminBook $book;

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
    // HALAMAN & STATUS
    // ─────────────────────────────────────────────────────────────

    public function test_halaman_prosem_menampilkan_status_dan_summary(): void
    {
        $this->actingAs($this->guru);

        $this->get("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}")
            ->assertOk()
            ->assertSee('Program Semester')
            ->assertSee('Pekan Efektif')
            ->assertSee('JP Tersedia')
            ->assertSee('JP Terencana')
            ->assertSee('Otomatis')
            ->assertSee('Atur Distribusi')
            ->assertSee('Distribusi TP / Materi');
    }

    public function test_adjustment_valid_tersimpan_dan_status_berubah(): void
    {
        $this->actingAs($this->guru);

        $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30, 4 => 30],
        ])->assertStatus(302);

        $this->item1->refresh();

        $this->assertSame(ProsemItem::SUMBER_MANUAL, $this->item1->sumber);
        $this->assertSame(3, $this->item1->mulai_minggu_ke);
        $this->assertSame(4, $this->item1->selesai_minggu_ke);
        $this->assertSame(['3' => 30, '4' => 30], $this->item1->weeks()->pluck('jp', 'pekan_ke')->map(fn ($v) => (int) $v)->all());
        $this->assertNotNull($this->prosem->fresh()->adjusted_at);

        $this->get("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}")
            ->assertOk()
            ->assertSee('Disesuaikan')
            ->assertSee('Pekan 3: 30 JP');
    }

    // ─────────────────────────────────────────────────────────────
    // VALIDASI
    // ─────────────────────────────────────────────────────────────

    public function test_adjustment_menolak_pekan_libur(): void
    {
        $this->actingAs($this->guru);

        // Pekan 1–2 adalah libur awal tahun (Kaldik) — tidak boleh dipilih.
        $response = $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [1 => 60],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $this->assertSame(ProsemItem::SUMBER_OTOMATIS, $this->item1->fresh()->sumber);
    }

    public function test_adjustment_menolak_total_tidak_sesuai(): void
    {
        $this->actingAs($this->guru);

        $response = $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30], // kurang 30 JP
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('masih kurang', session('error'));

        $response = $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 40, 4 => 40], // melebihi 20 JP
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('melebihi alokasi', session('error'));
    }

    public function test_adjustment_menolak_pekan_di_luar_semester(): void
    {
        $this->actingAs($this->guru);

        $response = $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [99 => 60],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('di luar semester', session('error'));
    }

    // ─────────────────────────────────────────────────────────────
    // AUTHORIZATION
    // ─────────────────────────────────────────────────────────────

    public function test_adjustment_dibatasi_penyusun_dan_tim_kurikulum(): void
    {
        // Guru lain (bukan penyusun, bukan tim kurikulum) → ditolak.
        $this->actingAs($this->guruLain);
        $this->put("/{$this->guruLain->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 60],
        ])->assertStatus(403);

        // Tim kurikulum → boleh.
        $this->actingAs($this->kurikulum);
        $this->put("/{$this->kurikulum->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30, 4 => 30],
        ])->assertStatus(302);

        $this->assertSame(ProsemItem::SUMBER_MANUAL, $this->item1->fresh()->sumber);
    }

    // ─────────────────────────────────────────────────────────────
    // PERUBAHAN KALDIK → TIDAK VALID, ADJUSTMENT TIDAK DIHAPUS
    // ─────────────────────────────────────────────────────────────

    public function test_perubahan_kaldik_menandai_tidak_valid_tanpa_menghapus_adjustment(): void
    {
        $this->actingAs($this->guru);

        $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30, 4 => 30],
        ])->assertStatus(302);

        // Kalender berubah: pekan 4 menjadi libur, Pekan Efektif diregenerasi.
        Kaldik::create([
            'name' => 'Libur Tambahan',
            'category' => Kaldik::CATEGORY_KALDIK,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'type' => Kaldik::TYPE_LIBUR,
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-25',
            'is_active' => true,
        ]);
        app(PekanEfektifService::class)->generate($this->school->id, $this->ay->id, 1);

        $this->get("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}")
            ->assertOk()
            ->assertSee('Tidak Valid')
            ->assertSee('tidak lagi tersedia')
            ->assertSee('penyesuaian manual terdampak');

        // Adjustment TIDAK dihapus — guru menentukan ulang.
        $this->item1->refresh();
        $this->assertSame(ProsemItem::SUMBER_MANUAL, $this->item1->sumber);
        $this->assertSame(2, $this->item1->weeks()->count());

        // Jurnal & realisasi tetap terhubung ke item yang sama.
        $journal = AdminJurnalPembelajaran::where('admin_book_id', $this->book->id)->firstOrFail();
        $this->assertSame($this->item1->id, $journal->fresh()->prosem_item_id);
    }

    // ─────────────────────────────────────────────────────────────
    // SINKRON & RESET
    // ─────────────────────────────────────────────────────────────

    public function test_sinkron_mempertahankan_manual_dan_memperbarui_otomatis(): void
    {
        $this->actingAs($this->guru);

        $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30, 4 => 30],
        ])->assertStatus(302);

        $item2IdBefore = $this->item2->id;

        $this->post("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/sync")->assertStatus(302);

        $this->item1->refresh();
        $this->item2->refresh();

        // Manual tetap utuh; otomatis tetap otomatis; ID item tidak berubah (jurnal aman).
        $this->assertSame(ProsemItem::SUMBER_MANUAL, $this->item1->sumber);
        $this->assertSame(2, $this->item1->weeks()->count());
        $this->assertSame(ProsemItem::SUMBER_OTOMATIS, $this->item2->sumber);
        $this->assertSame($item2IdBefore, $this->item2->id);

        $journal = AdminJurnalPembelajaran::where('admin_book_id', $this->book->id)->firstOrFail();
        $this->assertSame($this->item1->id, $journal->fresh()->prosem_item_id);
    }

    public function test_reset_mengembalikan_distribusi_ke_otomatis(): void
    {
        $this->actingAs($this->guru);

        $this->put("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/distribusi", [
            'weeks' => [3 => 30, 4 => 30],
        ])->assertStatus(302);

        $this->post("/{$this->guru->id}/kurikulum/prosem/{$this->prosem->id}/items/{$this->item1->id}/reset")
            ->assertStatus(302);

        $this->item1->refresh();
        $this->assertSame(ProsemItem::SUMBER_OTOMATIS, $this->item1->sumber);
        $this->assertGreaterThan(2, $this->item1->weeks()->count()); // kembali ke sebaran otomatis (5 JP/pekan)

        $journal = AdminJurnalPembelajaran::where('admin_book_id', $this->book->id)->firstOrFail();
        $this->assertSame($this->item1->id, $journal->fresh()->prosem_item_id);
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Adjustment',
            'code' => 'UTADJ',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '33333333',
            'name' => 'SMP Uji Adjustment',
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

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        \App\Models\Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->kurikulum = User::create(['name' => 'Koor Kurikulum', 'email' => 'koor.adj@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guru = User::create(['name' => 'Guru PROSEM', 'email' => 'guru.adj@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->guruLain = User::create(['name' => 'Guru Lain', 'email' => 'guru.lain.adj@test.local', 'password' => bcrypt('password'), 'is_active' => true]);

        foreach ([$this->kurikulum, $this->guru, $this->guruLain] as $user) {
            $user->assignRole($role);
            $this->seedPermissionSnapshot($user, ['jadwalkbm.read']);
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                [$this->kurikulum, 'Koordinator Kurikulum'],
                [$this->guru, 'Guru Mapel'],
                [$this->guruLain, 'Guru Mapel'],
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

        // Kalender: 2 pekan libur awal tahun → 25 pekan efektif.
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

        $atp = AlurTujuanPembelajaran::create([
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
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp1->id,
            'urutan' => 1,
            'jp_alokasi' => 60,
        ]);
        AlurTujuanPembelajaranItem::create([
            'alur_tujuan_pembelajaran_id' => $atp->id,
            'tujuan_pembelajaran_id' => $tp2->id,
            'urutan' => 2,
            'jp_alokasi' => 65,
        ]);
        $atp->recalculateTotal();

        // PROTA → PROSEM (otomatis).
        $prota = app(ProtaService::class)->createFromAtp($atp->fresh(), $this->guru);
        $this->prosem = app(ProsemService::class)->createFromProta($prota, $this->guru);

        $items = $this->prosem->items()->orderBy('urutan')->get();
        $this->item1 = $items->first();
        $this->item2 = $items->last();

        // Buku administrasi + jurnal terhubung item1 (uji konsistensi realisasi).
        $this->book = TeacherAdminBook::create([
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->math->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        AdminJurnalPembelajaran::create([
            'admin_book_id' => $this->book->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'prosem_item_id' => $this->item1->id,
            'tujuan_pembelajaran_id' => $this->item1->tujuan_pembelajaran_id,
            'meeting_number' => 1,
            'meeting_date' => '2026-07-13',
            'material' => 'Bilangan bulat.',
            'teacher_signature' => 'Terverifikasi',
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
