<?php

namespace Tests\Feature;

use App\Models\AbsensiGtkSetting;
use App\Models\User;
use App\Services\TeacherDeviceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Opsi B — kunci perangkat opsional pada scan QR cetak.
 * Perangkat pertama didaftarkan; perangkat lain ditolak saat fitur aktif.
 */
class TeacherDeviceBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_kunci_perangkat_opsional_dan_perangkat_pertama_terdaftar(): void
    {
        $user = User::create([
            'name' => 'Guru Device', 'email' => 'guru.device@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $svc = app(TeacherDeviceBinding::class);

        // Nonaktif → semua perangkat diizinkan (default).
        $this->assertFalse($svc->enabled());
        $this->assertTrue($svc->verify($user, null)['ok']);

        // Aktif + tanpa device id → ditolak dengan pesan jelas.
        AbsensiGtkSetting::set(TeacherDeviceBinding::KEY_ENABLED, true, 'bool');
        $missing = $svc->verify($user, null);
        $this->assertFalse($missing['ok']);
        $this->assertStringContainsString('Perangkat', (string) $missing['message']);

        // Perangkat pertama → didaftarkan & diizinkan.
        $this->assertTrue($svc->verify($user, 'dev-abc-123', 'UA-Test')['ok']);
        $this->assertDatabaseHas('teacher_attendance_devices', ['user_id' => $user->id]);

        // Perangkat sama → diizinkan; perangkat berbeda → ditolak.
        $this->assertTrue($svc->verify($user, 'dev-abc-123')['ok']);
        $other = $svc->verify($user, 'dev-xyz-999');
        $this->assertFalse($other['ok']);
        $this->assertStringContainsString('terdaftar', (string) $other['message']);

        // Tetap satu baris per user (tidak menggandakan pendaftaran).
        $this->assertSame(1, \App\Models\TeacherAttendanceDevice::where('user_id', $user->id)->count());
    }
}
