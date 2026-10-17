<?php

namespace Tests\Feature;

use App\Http\Controllers\Personalia\AbsensiGtkController;
use App\Models\AbsensiGtkSetting;
use App\Models\User;
use App\Services\QrAttendanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Pengaturan Absensi QR: toleransi keterlambatan (default 5 menit),
 * geofence opsional, penyimpanan pengaturan, dan pembatasan kewenangan.
 */
class QrAttendanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_toleransi_keterlambatan_5_menit(): void
    {
        $this->assertSame(5, app(QrAttendanceSettings::class)->lateThresholdMinutes());
    }

    public function test_geofence_opsional_dan_validasi_radius(): void
    {
        $svc = app(QrAttendanceSettings::class);

        // Nonaktif → semua lokasi diizinkan.
        $this->assertTrue($svc->geofenceStatus(-8.58, 116.11)['ok']);

        // Aktif TANPA titik satuan → jangan blokir siapa pun.
        AbsensiGtkSetting::set(QrAttendanceSettings::KEY_GEO_ENABLED, true, 'bool');
        $this->assertTrue($svc->geofenceStatus(null, null)['ok']);

        // Aktif + titik → koordinat guru wajib.
        AbsensiGtkSetting::set(QrAttendanceSettings::KEY_GEO_LAT, '-8.583300', 'string');
        AbsensiGtkSetting::set(QrAttendanceSettings::KEY_GEO_LNG, '116.116700', 'string');
        AbsensiGtkSetting::set(QrAttendanceSettings::KEY_GEO_RADIUS, 150, 'int');

        $missing = $svc->geofenceStatus(null, null);
        $this->assertFalse($missing['ok']);
        $this->assertStringContainsString('lokasi', strtolower((string) $missing['message']));

        // Di dalam radius (± 55 m dari titik).
        $inside = $svc->geofenceStatus(-8.5838, 116.1167);
        $this->assertTrue($inside['ok']);
        $this->assertLessThan(150, $inside['distance_m']);

        // Akurasi GPS buruk diberi kelonggaran (jarak ± 200 m, akurasi ± 100 m → efektif 100 m ≤ 150 m).
        $this->assertFalse($svc->geofenceStatus(-8.5851, 116.1167)['ok'], 'Tanpa akurasi: ± 200 m di luar radius.');
        $this->assertTrue($svc->geofenceStatus(-8.5851, 116.1167, 100.0)['ok'], 'Dengan akurasi ± 100 m: diberi kelonggaran.');

        // Di luar radius (± 2,2 km dari titik).
        $outside = $svc->geofenceStatus(-8.6033, 116.1167);
        $this->assertFalse($outside['ok']);
        $this->assertGreaterThan(150, $outside['distance_m']);
        $this->assertStringContainsString('luar area', strtolower((string) $outside['message']));
    }

    public function test_simpan_pengaturan_qr_tersimpan_dan_dibaca_ulang(): void
    {
        $user = $this->makeUser('TU QR', 'tu.qr@test.local', 'Staf Tata Usaha');

        $request = Request::create('/absensi-gtk/settings/qr', 'POST', [
            'late_threshold_minutes' => 7,
            'tolerance_before_minutes' => 20,
            'tolerance_after_minutes' => 8,
            'early_leave_threshold_minutes' => 12,
            'geo_enabled' => 1,
            'geo_lat' => '-8.5833',
            'geo_lng' => '116.1167',
            'geo_radius_m' => 200,
            'device_binding_enabled' => 1,
        ]);
        $request->setUserResolver(fn () => $user);

        app(AbsensiGtkController::class)->qrSettingsStore($request, $user->id);

        $svc = app(QrAttendanceSettings::class);
        $this->assertSame(7, $svc->lateThresholdMinutes());

        $geo = $svc->geoSettings();
        $this->assertTrue($geo['enabled']);
        $this->assertEqualsWithDelta(-8.5833, $geo['lat'], 0.0001);
        $this->assertEqualsWithDelta(116.1167, $geo['lng'], 0.0001);
        $this->assertSame(200, $geo['radius_m']);

        // Toleransi per jenis tersimpan.
        $time = $svc->timeSettings();
        $this->assertSame(20, (int) $time['qr_tolerance_before_minutes']);
        $this->assertSame(8, (int) $time['qr_tolerance_after_minutes']);
        $this->assertSame(12, (int) $time['qr_early_leave_threshold_minutes']);

        // Audit log pengaturan tercatat.
        $this->assertDatabaseHas('activity_log', ['log_name' => 'qr-settings']);

        // Opsi B: kunci perangkat aktif.
        $this->assertTrue(app(\App\Services\TeacherDeviceBinding::class)->enabled());
    }

    public function test_guru_biasa_tidak_boleh_mengubah_pengaturan_qr(): void
    {
        $guru = $this->makeUser('Guru QR', 'guru.qr@test.local', 'Guru Umum');

        $request = Request::create('/absensi-gtk/settings/qr', 'POST', [
            'late_threshold_minutes' => 1,
        ]);
        $request->setUserResolver(fn () => $guru);

        try {
            app(AbsensiGtkController::class)->qrSettingsStore($request, $guru->id);
            $this->fail('Guru biasa seharusnya tidak boleh mengubah pengaturan QR.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Nilai tidak berubah dari default.
        $this->assertSame(5, app(QrAttendanceSettings::class)->lateThresholdMinutes());
    }

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => $jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
