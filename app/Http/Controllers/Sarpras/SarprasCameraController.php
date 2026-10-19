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
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $cacheKey = 'sarpras:geocode:'.round($lat, 4).':'.round($lng, 4);

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
                $address = $data['address'] ?? [];
                $road = trim(($address['road'] ?? $data['name'] ?? '').' '.($address['house_number'] ?? ''));
                $parts = array_values(array_unique(array_filter([
                    $road ?: null,
                    $address['neighbourhood'] ?? $address['hamlet'] ?? null,
                    $address['village'] ?? $address['suburb'] ?? null,
                    $address['city'] ?? $address['town'] ?? $address['county'] ?? null,
                    $address['state'] ?? null,
                ])));

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
}
