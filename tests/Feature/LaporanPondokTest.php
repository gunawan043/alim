<?php

namespace Tests\Feature;

use App\Http\Controllers\UserLaporanController;
use App\Models\AbsensiGtk;
use App\Models\Dormitory;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Tests\TestCase;

/**
 * Menu Laporan: halaman terisi data nyata (bukan placeholder),
 * filter bekerja, dan ekspor CSV tersedia.
 */
class LaporanPondokTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private string $workUnitId;

    private User $viewer;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        // Layout memeriksa permission ini langsung (topbar).
        \App\Models\Permission::firstOrCreate([
            'name' => 'impersonate_role', 'guard_name' => 'web',
        ]);

        $this->workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $this->workUnitId, 'name' => 'Unit Uji Laporan', 'code' => 'UUL',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $this->workUnitId,
            'npsn' => '55555555',
            'name' => 'Sekolah Uji Laporan',
        ]);

        $this->viewer = User::create([
            'name' => 'Pimpinan Laporan',
            'email' => 'pimpinan.laporan@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Laporan',
            'email' => 'guru.laporan@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $profile = GtkProfile::create(['user_id' => $this->guru->id]);

        GtkEmployment::create([
            'user_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'jenis_gtk' => 'guru',
            'jabatan' => 'Guru Matematika',
            'status_kepegawaian' => 'tetap',
        ]);

        Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Laki',
            'nisn' => '1111111111', 'nis' => 'S-001', 'gender' => 'L', 'status' => 'active',
        ]);
        Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Perempuan',
            'nisn' => '2222222222', 'nis' => 'S-002', 'gender' => 'P', 'status' => 'active',
        ]);
        Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Nonaktif',
            'nisn' => '3333333333', 'nis' => 'S-003', 'gender' => 'L', 'status' => 'inactive',
        ]);

        AbsensiGtk::create([
            'gtk_id' => $profile->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '07:05:00',
            'terlambat_menit' => 5,
            'keterangan' => 'Test presensi',
        ]);

        Dormitory::create([
            'work_unit_id' => $this->workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-UJI',
            'name' => 'Asrama Uji',
            'gender' => 'putra',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($this->viewer);
    }

    public function test_dashboard_laporan_menampilkan_data(): void
    {
        $view = app(UserLaporanController::class)->index($this->request(), $this->viewer->id);

        $this->assertInstanceOf(View::class, $view);
        $html = $view->render();
        $this->assertStringContainsString('Laporan Pondok', $html);
        $this->assertStringContainsString('Santri Aktif', $html);
    }

    public function test_laporan_santri_filter_gender_dan_ekspor_csv(): void
    {
        $controller = app(UserLaporanController::class);

        $view = $controller->santri($this->request(['gender' => 'P']), $this->viewer->id);
        $html = $view->render();
        $this->assertStringContainsString('Santri Perempuan', $html);
        $this->assertStringNotContainsString('Santri Laki', $html);

        $response = $controller->export($this->request(['gender' => 'P']), $this->viewer->id, 'santri');
        $this->assertSame(200, $response->getStatusCode());

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('Nama', $csv);
        $this->assertStringContainsString('Santri Perempuan', $csv);
        $this->assertStringNotContainsString('Santri Laki', $csv);
    }

    public function test_laporan_gtk_dan_presensi_render(): void
    {
        $controller = app(UserLaporanController::class);

        $gtk = $controller->gtk($this->request(), $this->viewer->id)->render();
        $this->assertStringContainsString('Guru Laporan', $gtk);
        $this->assertStringContainsString('Guru Matematika', $gtk);

        $presensiView = $controller->presensi($this->request([
            'dari' => now()->subDay()->toDateString(),
            'sampai' => now()->toDateString(),
        ]), $this->viewer->id);
        $presensi = $presensiView->render();
        $this->assertStringContainsString('Rekap Presensi GTK', $presensi);
        $this->assertStringContainsString('Guru Laporan', $presensi);
        $this->assertStringContainsString('5 menit', $presensi);
        $this->assertStringContainsString('presensiTrend', $presensi);
        $this->assertNotEmpty($presensiView->getData()['trend']);
    }

    public function test_cetak_pdf_laporan(): void
    {
        $controller = app(UserLaporanController::class);

        foreach (['presensi', 'santri', 'gtk', 'asrama'] as $type) {
            $response = $controller->pdf($this->request([
                'dari' => now()->subDay()->toDateString(),
                'sampai' => now()->toDateString(),
            ]), $this->viewer->id, $type);

            $this->assertSame(200, $response->getStatusCode(), "PDF {$type} gagal.");
            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_laporan_asrama_render(): void
    {
        $html = app(UserLaporanController::class)
            ->asrama($this->request(), $this->viewer->id)
            ->render();

        $this->assertStringContainsString('Asrama Uji', $html);
        $this->assertStringContainsString('Daftar Asrama', $html);
    }

    private function request(array $query = []): Request
    {
        $request = Request::create('/laporan', 'GET', $query);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->viewer);

        return $request;
    }
}
