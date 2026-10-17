<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbsensiGtkSetting;

/**
 * Pengaturan Absensi QR Guru: toleransi keterlambatan & geofence lokasi.
 *
 * - Toleransi keterlambatan default 5 menit (dapat diatur per satuan pendidikan).
 * - Geolokasi opsional: bila diaktifkan, scan divalidasi di BACKEND terhadap
 *   titik & radius satuan. Koordinat guru TIDAK disimpan (hanya keputusan
 *   valid/tidak) — meminimalkan pengumpulan data lokasi.
 * - Perubahan konfigurasi TIDAK mengubah riwayat lama: status & late_minutes
 *   tersimpan pada saat scan (histori tetap).
 */
final class QrAttendanceSettings
{
    public const DEFAULT_LATE_THRESHOLD_MINUTES = 5;

    public const KEY_QR_SETTINGS = 'qr_settings';

    public const KEY_GEO_ENABLED = 'qr_geo_enabled';

    public const KEY_GEO_LAT = 'qr_geo_lat';

    public const KEY_GEO_LNG = 'qr_geo_lng';

    public const KEY_GEO_RADIUS = 'qr_geo_radius_m';

    /**
     * Pengaturan waktu scan (default + override tersimpan).
     *
     * @return array<string, int>
     */
    public function timeSettings(): array
    {
        $defaults = [
            'qr_tolerance_before_minutes' => 15,
            'qr_tolerance_after_minutes' => 5,
            'qr_late_threshold_minutes' => self::DEFAULT_LATE_THRESHOLD_MINUTES,
            'qr_checkout_window_before' => 10,
            'qr_checkout_window_after' => 30,
            'qr_early_leave_threshold_minutes' => 10,
        ];

        $overrides = AbsensiGtkSetting::get(self::KEY_QR_SETTINGS, []) ?? [];

        return array_merge($defaults, is_array($overrides) ? $overrides : []);
    }

    public function lateThresholdMinutes(): int
    {
        $value = (int) ($this->timeSettings()['qr_late_threshold_minutes'] ?? self::DEFAULT_LATE_THRESHOLD_MINUTES);

        return max(0, $value);
    }

    /**
     * Konfigurasi geofence.
     *
     * @return array{enabled: bool, lat: ?float, lng: ?float, radius_m: int}
     */
    public function geoSettings(): array
    {
        $lat = AbsensiGtkSetting::get(self::KEY_GEO_LAT);
        $lng = AbsensiGtkSetting::get(self::KEY_GEO_LNG);
        $radius = AbsensiGtkSetting::get(self::KEY_GEO_RADIUS);

        return [
            'enabled' => (bool) AbsensiGtkSetting::get(self::KEY_GEO_ENABLED, false),
            'lat' => is_numeric($lat) ? (float) $lat : null,
            'lng' => is_numeric($lng) ? (float) $lng : null,
            'radius_m' => is_numeric($radius) ? max(10, (int) $radius) : 150,
        ];
    }

    /**
     * Jarak dua koordinat (meter) — formula haversine.
     */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Validasi lokasi scan terhadap geofence satuan.
     *
     * @return array{required: bool, ok: bool, distance_m: ?float, message: ?string}
     */
    public function geofenceStatus(?float $lat, ?float $lng, ?float $accuracyM = null): array
    {
        $geo = $this->geoSettings();

        if (! $geo['enabled']) {
            return ['required' => false, 'ok' => true, 'distance_m' => null, 'message' => null];
        }

        if ($geo['lat'] === null || $geo['lng'] === null) {
            // Dinyalakan tetapi titik belum diatur — jangan blokir, beri info.
            return ['required' => true, 'ok' => true, 'distance_m' => null, 'message' => 'Lokasi satuan belum diatur.'];
        }

        if ($lat === null || $lng === null) {
            return [
                'required' => true,
                'ok' => false,
                'distance_m' => null,
                'message' => 'Lokasi Anda belum terdeteksi. Izinkan akses lokasi pada perangkat lalu coba scan lagi.',
            ];
        }

        $distance = $this->distanceMeters($geo['lat'], $geo['lng'], $lat, $lng);

        // Toleransi akurasi GPS: beri kelonggaran sebesar akurasi perangkat agar
        // tidak sering menolak palsu di dalam gedung (bukan klaim anti-palsu).
        $effectiveDistance = max(0.0, $distance - max(0.0, (float) ($accuracyM ?? 0)));

        if ($effectiveDistance > $geo['radius_m']) {
            $accuracyNote = $accuracyM ? ' (akurasi GPS ± '.round($accuracyM).' m)' : '';

            return [
                'required' => true,
                'ok' => false,
                'distance_m' => $distance,
                'message' => 'Scan berada di luar area sekolah: jarak ± '.round($distance).' m'.$accuracyNote.', batas '.$geo['radius_m'].' m. Pastikan Anda berada di lingkungan satuan pendidikan.',
            ];
        }

        return ['required' => true, 'ok' => true, 'distance_m' => $distance, 'message' => null];
    }
}
