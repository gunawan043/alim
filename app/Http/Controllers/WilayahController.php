<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    /**
     * Get provinces
     */
    public function provinces()
    {
        $provinces = Province::orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $provinces
        ]);
    }

    /**
     * Get cities by province
     */
    public function cities($provinceCode)
    {
        $cities = City::where('province_code', $provinceCode)
            ->orderBy('name')
            ->get(['code', 'name', 'province_code']);
            
        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    /**
     * Get districts by city
     */
    public function districts($cityCode)
    {
        $districts = District::where('city_code', $cityCode)
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $districts
        ]);
    }

    /**
     * Get villages by district
     */
    public function villages($districtCode)
    {
        $villages = Village::where('district_code', $districtCode)
            ->orderBy('name')
            ->get()
            ->map(function ($village) {
                $meta = is_string($village->meta)
                    ? json_decode($village->meta, true)
                    : ($village->meta ?? []);

                return [
                    'code'        => $village->code,
                    'name'        => $village->name,
                    'postal_code' => $meta['postal_code'] ?? $meta['pos'] ?? $meta['kode_pos'] ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $villages
        ]);
    }
}