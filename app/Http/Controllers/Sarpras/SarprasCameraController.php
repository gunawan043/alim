<?php

namespace App\Http\Controllers\Sarpras;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SarprasCameraController extends SarprasBaseController
{
    public function index()
    {
        return view('sarpras.camera.index');
    }

    /**
     * Reverse geocoding server-side agar tidak terhalang CORS browser.
     * Nominatim dipakai lebih dulu; bila gagal, frontend memakai fallback.
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $accuracy = isset($validated['accuracy']) ? (float) $validated['accuracy'] : null;

        // Jangan pernah memberi nama tempat dari titik yang masih kasar
        // (mis. lokasi berbasis WiFi/IP yang bisa meleset kilometer).
        if ($accuracy !== null && $accuracy > 100) {
            return response()->json([
                'place' => null,
                'reason' => 'accuracy_too_low',
            ]);
        }

        $cacheKey = 'sarpras:geocode:v2:'.round($lat, 4).':'.round($lng, 4);

        $place = Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'ALIM-Sarpras/1.0',
                    'Accept' => 'application/json',
                    'Accept-Language' => 'id',
                ])->timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => 'id',
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                if (! is_array($data)) {
                    return null;
                }

                // Pastikan hasil reverse geocode memang berada di sekitar titik
                // yang diminta. Nominatim bisa mengembalikan objek terdekat yang
                // jauh bila titik berada di area tanpa nama.
                $resultLat = isset($data['lat']) ? (float) $data['lat'] : null;
                $resultLng = isset($data['lon']) ? (float) $data['lon'] : null;
                if ($resultLat !== null && $resultLng !== null) {
                    $distance = $this->distanceMeters($lat, $lng, $resultLat, $resultLng);
                    if ($distance > 500) {
                        Log::info('Reverse geocode result ignored (too far)', [
                            'lat' => $lat,
                            'lng' => $lng,
                            'result_lat' => $resultLat,
                            'result_lng' => $resultLng,
                            'distance_m' => round($distance),
                        ]);

                        return null;
                    }
                }

                $address = $data['address'] ?? [];
                $road = trim(($address['road'] ?? '').' '.($address['house_number'] ?? ''));
                $poi = $address['amenity'] ?? $address['building'] ?? $address['school']
                    ?? $address['university'] ?? $address['office'] ?? $address['leisure']
                    ?? $address['tourism'] ?? $address['shop'] ?? $address['healthcare']
                    ?? $address['historic'] ?? null;
                $parts = array_values(array_unique(array_filter([
                    $poi ?: null,
                    $road ?: null,
                    $address['neighbourhood'] ?? $address['hamlet'] ?? null,
                    $address['village'] ?? $address['suburb'] ?? null,
                    $address['city'] ?? $address['town'] ?? $address['county'] ?? null,
                ])));

                // Jika tidak ada nama jalan/POI, gunakan nama objek Nominatim
                // (mis. gang, area, atau bangunan) alih-alih display_name yang
                // bisa memuat wilayah yang jauh.
                if ($parts === [] && ! empty($data['name'])) {
                    $parts[] = trim((string) $data['name']);
                }

                if ($parts !== []) {
                    return implode(', ', array_slice($parts, 0, 4));
                }

                if (! empty($data['display_name'])) {
                    return implode(', ', array_slice(array_map('trim', explode(',', $data['display_name'])), 0, 4));
                }
            } catch (\Throwable $e) {
                Log::warning('Reverse geocode failed', ['lat' => $lat, 'lng' => $lng, 'error' => $e->getMessage()]);
            }

            return null;
        });

        return response()->json(['place' => $place]);
    }

    /**
     * Jarak dua koordinat dalam meter (haversine).
     */
    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $a = min(1.0, max(0.0, $a));

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }
}
