<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\TahfidzTasmianScore;
use App\Services\TahfidzTasmiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Halaman referensi Tahfidz: standar penilaian dan pemetaan surah–juz.
 *
 * Keduanya read-only. Ambang predikat dibaca langsung dari
 * TahfidzTasmiService agar halaman tidak pernah menyimpang dari perhitungan
 * nilai yang sebenarnya dipakai sistem.
 */
class TahfidzStandardController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $service = app(TahfidzTasmiService::class);

        // Turunkan ambang tiap predikat dari service, bukan menulis ulang angkanya.
        $scale = [];
        for ($score = 100; $score >= 0; $score--) {
            $predicate = $service->predicate($score);

            if (! isset($scale[$predicate])) {
                $scale[$predicate] = [
                    'key' => $predicate,
                    'min' => $score,
                    'max' => $score,
                    'label' => (new TahfidzTasmianScore(['predikat' => $predicate]))->predikat_label,
                    'arabic' => $service->predicateArabic($predicate),
                ];
            } else {
                $scale[$predicate]['min'] = $score;
            }
        }

        $scale = collect($scale)->sortByDesc('min')->values();

        // Contoh perhitungan memakai bobot yang sama dengan service.
        $example = $service->calculate(90, 85, 80);

        return view('tahfidz.standards.index', [
            'userId' => $userId,
            'scale' => $scale,
            'example' => $example,
        ]);
    }

    public function mapping(Request $request, string $userId)
    {
        $surahs = DB::table('tahfidz_surah_master')
            ->orderBy('number')
            ->get(['number', 'name_latin', 'name_arabic', 'juz', 'total_ayat']);

        $juzList = DB::table('tahfidz_juz_master')
            ->orderBy('juz_number')
            ->get(['juz_number', 'name', 'halaman_start', 'halaman_end', 'total_halaman'])
            ->keyBy('juz_number');

        $byJuz = $surahs->groupBy('juz')->sortKeys();

        return view('tahfidz.mapping.index', [
            'userId' => $userId,
            'byJuz' => $byJuz,
            'juzList' => $juzList,
            'totalSurah' => $surahs->count(),
            'totalJuz' => $juzList->count(),
        ]);
    }
}
