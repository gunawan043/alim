<?php

namespace Tests\Feature;

use App\Http\Controllers\Personalia\KehadiranController;
use App\Models\AbsensiGtk;
use App\Models\CutiRequest;
use App\Models\CutiTemplate;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Monitoring kehadiran: rekap bulanan per GTK dan daftar cuti/izin
 * terisi dari data nyata.
 */
class KehadiranMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $viewer;

    private User $guru;

    private GtkProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate([
            'name' => 'impersonate_role', 'guard_name' => 'web',
        ]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Kehadiran', 'code' => 'UUK',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '44444444', 'name' => 'Sekolah Uji Kehadiran',
        ]);

        $this->viewer = User::create([
            'name' => 'Pimpinan Kehadiran', 'email' => 'pimpinan.kehadiran@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Kehadiran', 'email' => 'guru.kehadiran@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->profile = GtkProfile::create(['user_id' => $this->guru->id]);

        GtkEmployment::create([
            'user_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'jenis_gtk' => 'guru',
            'jabatan' => 'Guru Fiqih',
            'status_kepegawaian' => 'tetap',
        ]);

        AbsensiGtk::create([
            'gtk_id' => $this->profile->id, 'tanggal' => now()->startOfMonth()->toDateString(),
            'status' => 'hadir', 'jam_masuk' => '07:00:00', 'terlambat_menit' => 0,
        ]);
        AbsensiGtk::create([
            'gtk_id' => $this->profile->id, 'tanggal' => now()->startOfMonth()->addDay()->toDateString(),
            'status' => 'hadir', 'jam_masuk' => '07:20:00', 'terlambat_menit' => 20,
        ]);
        AbsensiGtk::create([
            'gtk_id' => $this->profile->id, 'tanggal' => now()->startOfMonth()->addDays(2)->toDateString(),
            'status' => 'izin', 'keterangan' => 'Keperluan keluarga',
        ]);

        $template = CutiTemplate::create([
            'nama' => 'Cuti Tahunan', 'jenis' => 'TAHUNAN', 'jumlah_hari' => 12,
            'paid' => true, 'is_active' => true, 'urutan' => 1,
        ]);

        CutiRequest::create([
            'user_id' => $this->guru->id,
            'cuti_template_id' => $template->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'jumlah_hari' => 2,
            'alasan' => 'Acara keluarga',
            'status' => CutiRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->viewer);
    }

    public function test_rekap_kehadiran_bulanan(): void
    {
        $response = app(KehadiranController::class)->rekap(
            $this->request(['periode' => now()->format('Y-m')]),
            $this->viewer->id
        );

        $html = $response->render();

        $this->assertStringContainsString('Rekap Kehadiran GTK', $html);
        $this->assertStringContainsString('Guru Kehadiran', $html);

        $data = $response->getData();
        $this->assertSame(1, $data['totalGtk']);
        $this->assertSame(2, $data['totalHadir']);
        $this->assertSame(1, $data['tidakHadir']);
        $this->assertSame(1, $data['terlambat']);
    }

    public function test_daftar_cuti_izin_dan_filter_status(): void
    {
        $controller = app(KehadiranController::class);

        $response = $controller->cutiIzin($this->request(), $this->viewer->id);
        $html = $response->render();

        $this->assertStringContainsString('Cuti & Izin GTK', $html);
        $this->assertStringContainsString('Guru Kehadiran', $html);
        $this->assertStringContainsString('Acara keluarga', $html);

        $data = $response->getData();
        $this->assertSame(1, $data['total']);
        $this->assertSame(1, $data['menunggu']);

        // Filter status disetujui → kosong.
        $approved = $controller->cutiIzin(
            $this->request(['status' => 'disetujui']),
            $this->viewer->id
        );
        $this->assertSame(0, $approved->getData()['total']);
    }

    private function request(array $query = []): Request
    {
        $request = Request::create('/kehadiran', 'GET', $query);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->viewer);

        return $request;
    }
}
