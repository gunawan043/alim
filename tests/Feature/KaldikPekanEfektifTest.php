<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Http\Controllers\PekanEfektifPrintController;
use App\Http\Controllers\Waka\PekanEfektifController;
use App\Models\AcademicYear;
use App\Models\Kaldik;
use App\Models\PekanEfektif;
use App\Models\PekanEfektifPrintSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Policies\KaldikPolicy;
use App\Services\PekanEfektifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kalender Pendidikan → Pekan Efektif → data perencanaan pembelajaran.
 */
class KaldikPekanEfektifTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private User $pimpinan;

    private User $satuan;

    private User $guru;

    private StudyGroup $group;

    private Subject $math;

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
    // SERVICE: KALENDER → PEKAN EFEKTIF
    // ─────────────────────────────────────────────────────────────

    public function test_semester_range_fallback_membagi_semester_dari_tahun_ajaran(): void
    {
        $svc = app(PekanEfektifService::class);

        [$gs, $ge] = $svc->semesterRange($this->ay, PekanEfektif::SEMESTER_GANJIL);
        $this->assertSame('2026-07-01', $gs->toDateString());
        $this->assertSame('2026-12-31', $ge->toDateString());

        [$ns, $ne] = $svc->semesterRange($this->ay, PekanEfektif::SEMESTER_GENAP);
        $this->assertSame('2027-01-01', $ns->toDateString());
        $this->assertSame('2027-06-30', $ne->toDateString());
    }

    public function test_generate_pekan_efektif_dari_kaldik(): void
    {
        $this->makeKaldik('Libur Awal Tahun Ajaran', Kaldik::TYPE_LIBUR, '2026-07-01', '2026-07-12');
        $this->makeKaldik('Sumatif Akhir Semester', Kaldik::TYPE_UJIAN, '2026-12-01', '2026-12-05');

        $svc = app(PekanEfektifService::class);
        $result = $svc->generate($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL, $this->pimpinan->id);
        $summary = $result['summary'];

        $this->assertSame(27, $summary['total_minggu']);
        $this->assertSame(25, $summary['minggu_efektif']);
        $this->assertSame(2, $summary['minggu_libur']);
        $this->assertSame(1, $summary['minggu_ujian']);
        $this->assertSame(148, $summary['total_hari_efektif']);

        $rows = PekanEfektif::where('school_id', $this->school->id)
            ->where('semester', 1)
            ->orderBy('minggu_ke')
            ->get();

        $this->assertCount(27, $rows);
        $this->assertTrue($rows->every(fn ($p) => (bool) $p->is_generated));

        // Pekan 1 & 2 libur karena Libur Awal Tahun Ajaran.
        $this->assertSame('libur', $rows->firstWhere('minggu_ke', 1)->jenis);
        $this->assertSame(0, $rows->firstWhere('minggu_ke', 1)->jumlah_hari);
        $this->assertStringContainsString('Libur Awal Tahun Ajaran', $rows->firstWhere('minggu_ke', 1)->keterangan);

        // Pekan ujian terdeteksi.
        $ujian = $rows->firstWhere('jenis', PekanEfektif::JENIS_UJIAN);
        $this->assertNotNull($ujian);
        $this->assertSame(6, $ujian->jumlah_hari);
        $this->assertStringContainsString('Sumatif Akhir Semester', $ujian->keterangan);

        // Summary tersimpan dipakai modul lain.
        $persisted = $svc->summary($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL);
        $this->assertTrue($persisted['is_persisted']);
        $this->assertSame(25, $persisted['minggu_efektif']);
    }

    public function test_semester_genap_menggunakan_tahun_ajaran_semester_genap(): void
    {
        $genap = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'genap',
            'is_active' => false,
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
        ]);

        $service = app(PekanEfektifService::class);
        $resolved = $service->academicYearForSemester($this->ay, PekanEfektif::SEMESTER_GENAP);

        $this->assertSame($genap->id, $resolved?->id);
        $this->assertSame('genap', $resolved?->semester);
    }

    public function test_agenda_dari_info_tidak_ikut_menghitung_pekan_efektif(): void
    {
        $this->makeKaldik('Libur Resmi Pondok', Kaldik::TYPE_LIBUR, '2026-07-01', '2026-07-12');

        // Agenda dari Info/Broadcast: bukan Kaldik resmi, meski tipenya libur.
        Kaldik::create([
            'name' => 'Libur Kegiatan Internal',
            'category' => Kaldik::CATEGORY_AGENDA,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'type' => Kaldik::TYPE_LIBUR,
            'start_date' => '2026-07-13',
            'end_date' => '2026-07-19',
            'is_active' => true,
            'created_by' => $this->pimpinan->id,
        ]);

        $svc = app(PekanEfektifService::class);
        $result = $svc->generate($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL, $this->pimpinan->id);
        $summary = $result['summary'];

        // Hanya libur resmi 2 pekan yang dihitung.
        $this->assertSame(2, $summary['minggu_libur']);
        $this->assertSame(25, $summary['minggu_efektif']);
        $this->assertSame(148, $summary['total_hari_efektif']);
    }

    public function test_pekan_dengan_hari_efektif_kurang_dari_batas_tidak_dihitung_efektif(): void
    {
        $this->school->update(['min_effective_days_per_week' => 3]);
        $this->makeKaldik('Libur Sebagian Pekan', Kaldik::TYPE_LIBUR, '2026-07-06', '2026-07-09');

        $svc = app(PekanEfektifService::class);
        $result = $svc->computeWeeks($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL);

        $partial = collect($result['rows'])->firstWhere('tanggal_mulai', '2026-07-06');
        $this->assertNotNull($partial);
        $this->assertSame(2, $partial['jumlah_hari']);
        $this->assertSame(PekanEfektif::JENIS_TIDAK_EFEKTIF, $partial['jenis']);

        $summary = $svc->summarize($result['rows'], 3);
        $this->assertGreaterThan(0, $summary['minggu_tidak_efektif']);
    }

    public function test_batas_minimal_hari_pekan_dapat_diubah_per_sekolah(): void
    {
        $this->school->update(['min_effective_days_per_week' => 2]);
        $this->makeKaldik('Libur Sebagian Pekan', Kaldik::TYPE_LIBUR, '2026-07-06', '2026-07-09');

        $svc = app(PekanEfektifService::class);
        $result = $svc->computeWeeks($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL);

        $partial = collect($result['rows'])->firstWhere('tanggal_mulai', '2026-07-06');
        $this->assertNotNull($partial);
        $this->assertSame(2, $partial['jumlah_hari']);
        $this->assertSame(PekanEfektif::JENIS_EFEKTIF, $partial['jenis']);
    }

    public function test_pengaturan_cetak_dan_pdf_analisis_pekan_efektif_per_sekolah(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin Cetak Pekan',
            'email' => 'sa.cetak.pekan@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $request = Request::create('/'.$superAdmin->id.'/pekan-efektif/print-settings', 'POST', [
            'school_id' => $this->school->id,
            'margin_top' => 12,
            'margin_right' => 11,
            'margin_bottom' => 13,
            'margin_left' => 14,
            'font_size' => 10,
            'title_font_size' => 14,
            'table_font_size' => 9,
            'info_font_size' => 10,
            'footer_font_size' => 8,
            'line_height' => 1.2,
            'paper_size' => 'a4',
            'show_kop' => 1,
            'show_signature' => 1,
            'city' => 'Mataram',
            'guru_nama' => 'Guru Pekan',
            'guru_nip' => '123456789',
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $superAdmin);

        $controller = app(PekanEfektifPrintController::class);
        $controller->updateSettings($request, $superAdmin->id);

        $setting = PekanEfektifPrintSetting::where('school_id', $this->school->id)->firstOrFail();
        $this->assertSame(12.0, $setting->margin_top);
        $this->assertSame('Guru Pekan', $setting->guru_nama);

        $pdfRequest = Request::create('/'.$superAdmin->id.'/pekan-efektif/pdf', 'GET', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
        ]);
        $pdfRequest->attributes->set('schoolContextId', $this->school->id);
        $pdfRequest->setUserResolver(fn () => $superAdmin);

        $response = $controller->pdf($pdfRequest, $superAdmin->id);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_alokasi_jp_efektif_per_kelas(): void
    {
        $this->makeKaldik('Libur Awal Tahun Ajaran', Kaldik::TYPE_LIBUR, '2026-07-01', '2026-07-12');

        $svc = app(PekanEfektifService::class);
        $svc->generate($this->school->id, $this->ay->id, PekanEfektif::SEMESTER_GANJIL, $this->pimpinan->id);

        $jp = $svc->effectiveJpForStudyGroup(
            $this->school->id,
            $this->group->id,
            $this->ay->id,
            PekanEfektif::SEMESTER_GANJIL
        );

        $this->assertCount(1, $jp);
        $this->assertSame('Matematika', $jp[0]['subject']);
        $this->assertSame(6, $jp[0]['weekly_hours']);
        $this->assertSame(25, $jp[0]['minggu_efektif']); // 27 pekan - 2 pekan libur penuh
        $this->assertSame(150, $jp[0]['jp_efektif']);    // 6 JP/minggu × 25 minggu
    }

    // ─────────────────────────────────────────────────────────────
    // AKSES: SIAPA YANG BOLEH MENGELOLA KALENDER
    // ─────────────────────────────────────────────────────────────

    public function test_pengelolaan_kaldik_hanya_super_admin_dan_pimpinan(): void
    {
        $policy = app(KaldikPolicy::class);

        // Pimpinan boleh mengelola.
        $this->assertTrue($policy->create($this->pimpinan));

        // Satuan Pendidikan & Guru tidak boleh mengelola.
        $this->assertFalse($policy->create($this->satuan));
        $this->assertFalse($policy->create($this->guru));

        $kaldik = $this->makeKaldik('Libur Semester', Kaldik::TYPE_LIBUR, '2026-12-21', '2026-12-31');

        $this->assertFalse($policy->update($this->satuan, $kaldik));
        $this->assertTrue($policy->update($this->pimpinan, $kaldik));
    }

    public function test_http_store_kaldik_ditolak_untuk_satuan_pendidikan(): void
    {
        $this->actingAs($this->satuan);

        $this->post("/{$this->satuan->id}/kaldik", [
            'name' => 'Libur Tidak Sah',
            'category' => Kaldik::CATEGORY_KALDIK,
            'type' => Kaldik::TYPE_LIBUR,
            'semester' => 'ganjil',
            'start_date' => '2026-12-21',
            'end_date' => '2026-12-31',
        ])->assertStatus(302)->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('kaldik', ['name' => 'Libur Tidak Sah']);
    }

    public function test_http_store_kaldik_berhasil_untuk_pimpinan(): void
    {
        $this->actingAs($this->pimpinan);

        $this->post("/{$this->pimpinan->id}/kaldik", [
            'name' => 'Libur Semester Ganjil',
            'category' => Kaldik::CATEGORY_KALDIK,
            'type' => Kaldik::TYPE_LIBUR,
            'semester' => 'ganjil',
            'academic_year_id' => $this->ay->id,
            'start_date' => '2026-12-21',
            'end_date' => '2026-12-31',
            'is_active' => 1,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('kaldik', [
            'name' => 'Libur Semester Ganjil',
            'category' => Kaldik::CATEGORY_KALDIK,
            'semester' => 'ganjil',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HALAMAN PEKAN EFEKTIF (GURU & SATUAN PENDIDIKAN)
    // ─────────────────────────────────────────────────────────────

    public function test_halaman_pekan_efektif_dapat_diakses_guru_dan_satuan_pendidikan(): void
    {
        $this->makeKaldik('Libur Awal Tahun Ajaran', Kaldik::TYPE_LIBUR, '2026-07-01', '2026-07-12');

        // Guru
        $this->actingAs($this->guru);
        $this->get("/{$this->guru->id}/pekan-efektif")
            ->assertOk()
            ->assertSee('Pekan Efektif')
            ->assertSee('Alokasi JP Efektif')
            ->assertSee('Matematika');

        // Satuan Pendidikan
        $this->actingAs($this->satuan);
        $this->get("/{$this->satuan->id}/pekan-efektif")
            ->assertOk()
            ->assertSee('Pekan Efektif')
            ->assertSee('Minggu Efektif')
            // Ringkasan yang sebelumnya tidak tampil kini tersedia:
            ->assertSee('minggu semester ini')
            ->assertSee('hari libur tercatat')
            // Badge sumber data (belum digenerate → hitung langsung dari Kaldik)
            ->assertSee('Hitung langsung dari Kaldik')
            ->assertSee('Rincian Pekan');
    }

    public function test_generate_controller_menyimpan_pekan_efektif(): void
    {
        $this->makeKaldik('Libur Awal Tahun Ajaran', Kaldik::TYPE_LIBUR, '2026-07-01', '2026-07-12');

        $request = Request::create('/pekan-efektif/generate', 'POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => PekanEfektif::SEMESTER_GANJIL,
        ]);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->pimpinan);

        $controller = app(PekanEfektifController::class);
        $response = $controller->generate($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'Pekan efektif berhasil digenerate dari Kalender Pendidikan: 25 minggu efektif, 148 hari efektif, 2 minggu libur.',
            session('success')
        );

        $this->assertDatabaseHas('pekan_efektif', [
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 1,
            'minggu_ke' => 1,
            'jenis' => PekanEfektif::JENIS_LIBUR,
            'jumlah_hari' => 0,
            'is_generated' => 1,
        ]);

        $this->assertSame(
            27,
            PekanEfektif::where('school_id', $this->school->id)->where('semester', 1)->count()
        );
    }

    public function test_http_halaman_kaldik_render_sesuai_kewenangan(): void
    {
        // Pimpinan: boleh melihat + membuka form tambah.
        $this->actingAs($this->pimpinan);
        $this->get("/{$this->pimpinan->id}/kaldik")
            ->assertOk()
            ->assertSee('kaldik-calendar', false);
        $this->get("/{$this->pimpinan->id}/kaldik/create")
            ->assertOk()
            ->assertSee('name="semester"', false);

        // Satuan Pendidikan: boleh melihat dan menambah agenda satuan/pribadi.
        $this->actingAs($this->satuan);
        $this->get("/{$this->satuan->id}/kaldik")
            ->assertOk()
            ->assertSee('kaldik-calendar', false);
        $this->get("/{$this->satuan->id}/kaldik/create")
            ->assertOk()
            ->assertSee('Agenda Kegiatan', false);
    }

    public function test_super_admin_boleh_mengelola_kaldik(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['level' => 1]
        );

        $superAdmin = User::create([
            'name' => 'Super Admin Kaldik',
            'email' => 'sa.kaldik@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $superAdmin->assignRole($superAdminRole);

        $policy = app(KaldikPolicy::class);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertTrue($policy->update($superAdmin, new Kaldik));
    }

    // ─────────────────────────────────────────────────────────────
    // FIXTURE
    // ─────────────────────────────────────────────────────────────

    private function seedFixture(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Kaldik',
            'code' => 'UTK',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji Kaldik',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId,
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $gradeLevelId,
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

        $pimpinanRole = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web'], ['level' => 3]);
        $satuanRole = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web'], ['level' => 12]);

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'teacher-attendance_manual', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'teacher-attendance_view', 'guard_name' => 'web']);

        $this->pimpinan = User::create([
            'name' => 'Pimpinan Kaldik',
            'email' => 'pimpinan.kaldik@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->pimpinan->assignRole($pimpinanRole);

        $this->satuan = User::create([
            'name' => 'Admin Satuan Kaldik',
            'email' => 'satuan.kaldik@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->satuan->assignRole($satuanRole);

        $this->guru = User::create([
            'name' => 'Guru Kaldik',
            'email' => 'guru.kaldik@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->guru->assignRole($guruRole);

        $this->seedPermissionSnapshot($this->pimpinan, ['kaldik-create', 'kaldik-update-all']);
        $this->seedPermissionSnapshot($this->satuan, []);
        $this->seedPermissionSnapshot($this->guru, []);

        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                [$this->pimpinan, 'Pimpinan & Struktural Pendidikan', 'Kepala Pondok'],
                [$this->satuan, 'Tenaga Kependidikan', 'Staf Tata Usaha'],
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

        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'TEST/KALDik/001',
            'decree_type' => 'teaching_assignment',
            'title' => 'SK Pembagian Tugas Mengajar (Uji Kaldik)',
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
            'weekly_hours' => 6,
            'status' => 'active',
        ]);
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
            'created_by' => $this->pimpinan->id,
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
