<?php

namespace Tests\Feature;

use App\Authorization\ValueObjects\ScopeKey;
use App\Http\Controllers\Waka\SupervisiController;
use App\Http\Controllers\Waka\SupervisiInstrumenController;
use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Supervisi;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiInstrumenItem;
use App\Models\SupervisiPenilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Smoke test modul Supervisi (domain waka).
 *
 * Menjamin error yang pernah terjadi tidak kembali:
 * - route sidebar `waka.supervisi` / `waka.ekstrakurikuler` yang tidak terdaftar,
 * - eager load/kolom yang tidak ada di `gtk_profiles` (`name`, `school_id`).
 *
 * Route waka memakai domain `waka.{APP_DOMAIN}` dengan APP_DOMAIN berupa IP,
 * sehingga request HTTP penuh tidak valid — kontroler dipanggil langsung
 * (pola yang sama dipakai test Kaldik untuk controller waka).
 */
class SupervisiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private User $waka;

    private GtkProfile $guruProfile;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Supervisi', 'code' => 'UUS',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '44444444',
            'name' => 'Sekolah Uji Supervisi',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wakaRole = Role::firstOrCreate(['name' => 'Wakil Kepala Sekolah', 'guard_name' => 'web'], ['level' => 5]);
        $this->waka = User::create([
            'name' => 'Waka Supervisi',
            'email' => 'waka.supervisi@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->waka->assignRole($wakaRole);
        $this->seedSnapshot($this->waka);
        $this->attachEmployment($this->waka, 'Wakil Kepala Satuan Pendidikan');

        $guru = User::create([
            'name' => 'Guru Supervisi',
            'email' => 'guru.supervisi@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $guru->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]));
        $this->attachEmployment($guru, 'Guru Umum');

        $this->guruProfile = GtkProfile::create([
            'user_id' => $guru->id,
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_rute_sidebar_waka_supervisi_dan_ekstrakurikuler_terdaftar(): void
    {
        // Regresi: sidebar waka pernah memanggil route('waka.supervisi') &
        // route('waka.ekstrakurikuler') yang tidak terdaftar (RouteNotFoundException).
        $this->assertTrue(\Route::has('waka.supervisi.index'), 'Route waka.supervisi.index harus terdaftar.');
        $this->assertTrue(\Route::has('waka.supervisi.create'), 'Route waka.supervisi.create harus terdaftar.');
        $this->assertTrue(\Route::has('waka.ekstrakurikuler.index'), 'Route waka.ekstrakurikuler.index harus terdaftar.');
    }

    public function test_halaman_supervisi_memuat_daftar_gtk_tanpa_kolom_yang_tidak_ada(): void
    {
        $controller = app(SupervisiController::class);

        // Index: query tidak boleh menyentuh kolom gtk_profiles yang tidak ada.
        $indexRequest = $this->makeRequest('GET');
        $indexView = $controller->index($indexRequest);
        $this->assertSame('waka.supervisi.index', $indexView->name());
        $this->assertNotNull($indexView->getData()['supervisiList']);

        // Create: daftar GTK memakai gtk_employments (bukan gtk_profiles.school_id),
        // dan nama diambil dari relasi user.
        $createView = $controller->create($this->makeRequest('GET'));
        $this->assertSame('waka.supervisi.create', $createView->name());

        $gtks = $createView->getData()['gtks'];
        $this->assertTrue(
            $gtks->contains(fn ($gtk) => $gtk->user?->name === 'Guru Supervisi'),
            'GTK satuan harus muncul di daftar supervisi.',
        );
    }

    public function test_simpan_supervisi_menyinkronkan_nama_gtk_dari_relasi(): void
    {
        $request = $this->makeRequest('POST', [
            'gtk_id' => $this->guruProfile->id,
            'gtk_name' => '',
            'academic_year_id' => $this->ay->id,
            'semester' => '1',
            'mata_pelajaran' => 'Matematika',
            'tanggal_supervisi' => now()->toDateString(),
            'jenis_supervisi' => 'proses_pembelajaran',
            'status' => 'terjadwal',
        ]);

        app(SupervisiController::class)->store($request);

        $row = Supervisi::where('school_id', $this->school->id)->first();
        $this->assertNotNull($row, 'Data supervisi harus tersimpan.');
        $this->assertSame('Guru Supervisi', $row->gtk_name);
        $this->assertSame($this->guruProfile->id, $row->gtk_id);
        $this->assertSame('Matematika', $row->mata_pelajaran);
    }

    public function test_halaman_rapor_gtk_tahunan_target_menu_supervisi_tidak_error(): void
    {
        // Menu "Supervisi & Kinerja GTK" mengarah ke halaman ini; sebelumnya
        // error "Undefined variable $tahunAjaran" pada pemakaian kedua tanpa `??`.
        $struktural = $this->strukturalUser();

        $this->actingAs($struktural)
            ->get("/{$struktural->id}/rapor-gtk/tahunan")
            ->assertOk()
            ->assertSee('Rekap Penilaian GTK');
    }

    public function test_pengaturan_instrumen_duplikat_ke_satuan_dan_item_baru(): void
    {
        $this->seed(\Database\Seeders\SupervisiInstrumenSeeder::class);

        $controller = app(SupervisiInstrumenController::class);
        $global = SupervisiInstrumen::whereNull('school_id')->where('jenis', SupervisiInstrumen::JENIS_GURU_UMUM)->firstOrFail();

        // Halaman pengaturan dapat dirender.
        $indexView = $controller->index($this->makeRequest('GET'));
        $this->assertSame('waka.supervisi.instrumen.index', $indexView->name());

        $editView = $controller->edit($this->makeRequest('GET'), $global->id);
        $this->assertSame('waka.supervisi.instrumen.edit', $editView->name());

        // Duplikat template global ke satuan.
        $controller->duplicate($this->makeRequest('POST'), $global->id);
        $copy = SupervisiInstrumen::where('school_id', $this->school->id)->first();
        $this->assertNotNull($copy, 'Salinan instrumen milik satuan harus dibuat.');
        $this->assertSame($global->bagian()->count(), $copy->bagian()->count());
        $this->assertSame(
            SupervisiInstrumenItem::whereIn('bagian_id', $global->bagian()->pluck('id'))->count(),
            SupervisiInstrumenItem::whereIn('bagian_id', $copy->bagian()->pluck('id'))->count(),
        );

        // Tambah item baru + atur poinnya pada salinan.
        $bagian = $copy->bagian()->orderBy('urutan')->first();
        $controller->storeItem($this->makeRequest('POST', ['teks' => 'Butir tambahan', 'bobot' => 3]), $bagian->id);

        $item = SupervisiInstrumenItem::where('bagian_id', $bagian->id)->where('teks', 'Butir tambahan')->first();
        $this->assertNotNull($item, 'Item baru harus tersimpan.');
        $this->assertEquals(3.0, (float) $item->bobot, 'Poin item harus sesuai isian.');

        // Template global bersifat baca-saja: edit item global ditolak 403.
        $globalItem = SupervisiInstrumenItem::whereIn('bagian_id', $global->bagian()->pluck('id'))->first();
        try {
            $controller->updateItem($this->makeRequest('PUT', ['teks' => 'Diubah']), $globalItem->id);
            $this->fail('Edit item template global seharusnya ditolak.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_semua_halaman_rapor_gtk_dapat_dibuka_dengan_ui_standar(): void
    {
        $struktural = $this->strukturalUser();

        $pages = [
            'akademik' => 'Penilaian Akademik',
            'disiplin' => 'Penilaian Disiplin',
            'kepribadian' => 'Penilaian Kepribadian',
            'administrasi' => 'Penilaian Administrasi',
        ];

        foreach ($pages as $slug => $title) {
            $this->actingAs($struktural)
                ->get("/{$struktural->id}/rapor-gtk/{$slug}")
                ->assertOk()
                ->assertSee($title)
                // Pola UI standar: breadcrumb + style bersama + tabel house-style.
                ->assertSee('Rapor GTK')
                ->assertSee('card-animate')
                ->assertSee('table-hover');
        }
    }

    private function strukturalUser(): User
    {
        $user = User::create([
            'name' => 'Waka Struktural',
            'email' => 'waka.struktural@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]));
        $this->seedSnapshot($user);
        $this->attachEmployment($user, 'Wakil Kepala Satuan Pendidikan');

        return $user;
    }

    public function test_penilaian_supervisi_menghitung_nilai_dan_ketercapaian(): void
    {
        $this->seed(\Database\Seeders\SupervisiInstrumenSeeder::class);

        $supervisi = Supervisi::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'gtk_id' => $this->guruProfile->id,
            'observer_id' => $this->guruProfile->id,
            'gtk_name' => 'Guru Supervisi',
            'semester' => 1,
            'mata_pelajaran' => 'Matematika',
            'tanggal_supervisi' => now()->toDateString(),
            'jenis_supervisi' => 'proses_pembelajaran',
            'status' => 'berlangsung',
        ]);

        $instrumen = SupervisiInstrumen::whereNull('school_id')
            ->where('jenis', SupervisiInstrumen::JENIS_GURU_UMUM)->firstOrFail();

        $items = SupervisiInstrumenItem::whereIn('bagian_id', $instrumen->bagian()->pluck('id'))->get();
        $skor = $items->mapWithKeys(fn ($item) => [$item->id => 3])->all(); // semua B(3)

        $controller = app(SupervisiController::class);
        $controller->penilaianStore($this->makeRequest('POST', [
            'instrumen_id' => $instrumen->id,
            'skor' => $skor,
            'kondisi' => $items->mapWithKeys(fn ($item) => [$item->id => 'ya'])->all(),
            'catatan_temuan' => 'Pertahankan kualitas pembelajaran.',
        ]), $supervisi->id);

        $fresh = $supervisi->fresh();
        $this->assertSame($instrumen->id, $fresh->instrumen_id);
        $this->assertEquals(75.0, (float) $fresh->nilai_akhir, 'Semua skor B(3) dari maks 4 → 75.');
        $this->assertSame('Baik', $fresh->ketercapaian);
        $this->assertSame($items->count(), SupervisiPenilaian::where('supervisi_id', $supervisi->id)->count());

        // Halaman penilaian & cetak dapat dirender.
        $penilaianView = $controller->penilaian($this->makeRequest('GET'), $supervisi->id);
        $this->assertSame('waka.supervisi.penilaian', $penilaianView->name());

        $cetakPdf = $controller->cetak($this->makeRequest('GET'), $supervisi->id);
        $this->assertSame('application/pdf', $cetakPdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $cetakPdf->getContent());
    }

    private function makeRequest(string $method, array $data = []): Request
    {
        $request = Request::create('/supervisi', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->waka);

        return $request;
    }

    private function attachEmployment(User $user, string $jabatan): void
    {
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

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSnapshot(User $user): void
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
            'permissions' => json_encode(['jadwalkbm.read']),
            'revoked' => json_encode([]),
            'is_current' => 1,
            'created_at' => now(),
            'archived_at' => null,
        ]);
    }
}
