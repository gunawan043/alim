<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbsensiGtkSetting;
use App\Models\TeacherAttendanceDevice;
use App\Models\User;

/**
 * Opsi B — kunci perangkat opsional untuk scan QR cetak.
 * Perangkat pertama yang dipakai GTK didaftarkan; scan berikutnya wajib dari
 * perangkat yang sama (mengurangi titip absen). Dapat dinonaktifkan per satuan.
 */
final class TeacherDeviceBinding
{
    public const KEY_ENABLED = 'qr_device_binding_enabled';

    public function enabled(): bool
    {
        return (bool) AbsensiGtkSetting::get(self::KEY_ENABLED, false);
    }

    /**
     * @return array{ok: bool, message: ?string}
     */
    public function verify(User $user, ?string $deviceId, ?string $userAgent = null): array
    {
        if (! $this->enabled()) {
            return ['ok' => true, 'message' => null];
        }

        $deviceId = trim((string) $deviceId);

        if ($deviceId === '') {
            return [
                'ok' => false,
                'message' => 'Perangkat Anda tidak dikenali. Muat ulang halaman scan lalu coba lagi, atau hubungi TU untuk mendaftarkan perangkat.',
            ];
        }

        $hash = hash('sha256', $deviceId);
        $registered = TeacherAttendanceDevice::where('user_id', $user->id)->first();

        if (! $registered) {
            TeacherAttendanceDevice::create([
                'user_id' => $user->id,
                'device_hash' => $hash,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
                'last_seen_at' => now(),
            ]);

            return ['ok' => true, 'message' => null];
        }

        if (! hash_equals((string) $registered->device_hash, $hash)) {
            return [
                'ok' => false,
                'message' => 'Scan hanya dapat dilakukan dari perangkat yang terdaftar. Hubungi TU untuk mengatur ulang perangkat absensi Anda.',
            ];
        }

        $registered->forceFill([
            'last_seen_at' => now(),
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : $registered->user_agent,
        ])->save();

        return ['ok' => true, 'message' => null];
    }
}
