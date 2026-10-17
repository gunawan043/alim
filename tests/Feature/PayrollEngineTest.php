<?php

namespace Tests\Feature;

use App\Http\Controllers\Personalia\PayrollController;
use App\Models\AbsensiGtk;
use App\Models\AcademicYear;
use App\Models\GtkEducation;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\Payroll;
use App\Models\PayrollSetting;
use App\Models\Payroll\Masakerja;
use App\Models\Payroll\Pendidikan;
use App\Models\Payroll\SkalaGajiGuru;
use App\Models\Payroll\Tunjangan;
use App\Models\School;
use App\Models\User;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mesin payroll: skala gaji + tunjangan + kehadiran, generate massal,
 * dan alur publish → paid.
 */
class PayrollEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private GtkProfile $profile;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Payroll', 'code' => 'UUP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '11112222', 'name' => 'Sekolah Uji Payroll',
        ]);

        $ay = AcademicYear::create(['name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin Payroll', 'email' => 'admin.payroll@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Payroll', 'email' => 'guru.payroll@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->profile = GtkProfile::create(['user_id' => $this->guru->id]);

        GtkEmployment::create([
            'user_id' => $this->guru->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $ay->id,
            'jenis_gtk' => 'guru',
            'jabatan' => 'Guru Matematika',
            'status_kepegawaian' => 'tetap',
            'tmt' => now()->subYears(5)->toDateString(),
        ]);

        GtkEducation::create([
            'user_id' => $this->guru->id,
            'jenjang_pendidikan' => 'S1',
            'nama_satuan_pendidikan' => 'Universitas Uji',
            'tahun_lulus' => now()->subYears(4)->year,
            'is_aktif' => true,
        ]);

        Masakerja::create(['masa_kerja' => 5, 'level' => 2]);
        Pendidikan::create(['Strata' => 'S1', 'Golongan' => 3]);

        SkalaGajiGuru::create([
            'masa_kerja' => 5, 'level' => 2,
            'golongan_1' => 3000000, 'golongan_2' => 4000000,
            'golongan_3' => 5000000, 'golongan_4' => 6000000, 'golongan_5' => 7000000,
        ]);

        Tunjangan::create([
            'Kode' => 'TUNJ-TETAP', 'Subjek' => 'Tunjangan Keluarga', 'Nominal' => 500000,
            'is_active' => true, 'is_attendance_based' => false, 'per_hari' => false,
        ]);
        Tunjangan::create([
            'Kode' => 'TUNJ-HADIR', 'Subjek' => 'Uang Makan', 'Nominal' => 50000,
            'is_active' => true, 'is_attendance_based' => true, 'per_hari' => true,
        ]);

        PayrollSetting::set('payroll_late_deduction_per_minute', 10000, 'float');
        PayrollSetting::set('payroll_absent_deduction_per_day', 100000, 'float');
        PayrollSetting::set('payroll_attendance_allowance_enabled', false, 'bool');

        // 20 hari hadir, 15 menit terlambat, 1 alpa pada bulan berjalan.
        for ($i = 1; $i <= 20; $i++) {
            AbsensiGtk::create([
                'gtk_id' => $this->profile->id,
                'tanggal' => now()->startOfMonth()->addDays($i - 1)->toDateString(),
                'status' => 'hadir',
                'terlambat_menit' => $i === 1 ? 15 : 0,
                'jam_masuk' => '07:00:00',
            ]);
        }
        AbsensiGtk::create([
            'gtk_id' => $this->profile->id,
            'tanggal' => now()->startOfMonth()->addDays(20)->toDateString(),
            'status' => 'alpa',
        ]);
    }

    public function test_kalkulasi_gaji_dari_skala_tunjangan_dan_kehadiran(): void
    {
        $calc = app(PayrollCalculator::class)->calculate(
            $this->profile,
            (int) now()->month,
            (int) now()->year,
        );

        $this->assertTrue($calc['ok'], implode(' ', $calc['errors']));
        $this->assertSame(5000000.0, (float) $calc['gaji_pokok']);
        $this->assertSame(1500000.0, (float) $calc['total_tunjangan']); // 500k + 20×50k
        $this->assertSame(250000.0, (float) $calc['total_potongan']); // 15×10k + 1×100k
        $this->assertSame(6250000.0, (float) $calc['gaji_bersih']);
        $this->assertSame('guru', $calc['meta']['jenis']);
        $this->assertSame(20, $calc['meta']['kehadiran']['hadir']);
    }

    public function test_generate_publish_pay_dan_tidak_menimpa_yang_sudah_dibayar(): void
    {
        $this->actingAs($this->admin);
        $controller = app(PayrollController::class);

        $request = Request::create('/payroll/generate', 'POST', [
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller->generate($request, $this->admin->id);

        $payroll = Payroll::where('gtk_id', $this->profile->id)
            ->where('bulan', now()->month)->where('tahun', now()->year)->firstOrFail();

        $this->assertSame('draft', $payroll->status);
        $this->assertSame('6250000.00', $payroll->gaji_bersih);

        $controller->publish($this->request('POST'), $this->admin->id, $payroll->id);
        $this->assertSame('published', $payroll->fresh()->status);

        $controller->pay($this->request('POST'), $this->admin->id, $payroll->id);
        $this->assertSame('paid', $payroll->fresh()->status);
        $this->assertNotNull($payroll->fresh()->paid_at);

        // Regenerate tidak menimpa slip paid.
        $controller->generate($request, $this->admin->id);
        $this->assertSame('paid', $payroll->fresh()->status);

        // Slip PDF dapat dibuat.
        $pdf = $controller->slipPdf($this->request('GET'), $this->admin->id, $payroll->id);
        $this->assertSame(200, $pdf->getStatusCode());
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type'));
    }

    public function test_halaman_payroll_dan_master_dapat_dirender(): void
    {
        $this->actingAs($this->admin);
        $controller = app(PayrollController::class);

        $index = $controller->index($this->request('GET'), $this->admin->id)->render();
        $this->assertStringContainsString('Daftar Gaji GTK', $index);
        $this->assertStringContainsString('Generate Payroll', $index);

        $master = $controller->master($this->request('GET', ['tab' => 'tunjangan']), $this->admin->id)->render();
        $this->assertStringContainsString('Master Gaji', $master);
        $this->assertStringContainsString('Tunjangan Keluarga', $master);

        $generate = $controller->generateForm($this->request('GET'), $this->admin->id)->render();
        $this->assertStringContainsString('Generate Payroll Massal', $generate);
    }

    private function request(string $method, array $data = []): Request
    {
        $request = Request::create('/payroll', $method, $data);
        $request->setUserResolver(fn () => $this->admin);
        $request->headers->set('referer', '/payroll');

        return $request;
    }
}
