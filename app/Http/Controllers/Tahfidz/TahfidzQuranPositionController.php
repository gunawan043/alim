<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Services\QuranPositionService;
use Illuminate\Http\Request;

class TahfidzQuranPositionController extends Controller
{
    public function __invoke(Request $request, string $userId)
    {
        $validated = $request->validate([
            'surah_start' => 'required|integer|between:1,114',
            'ayah_start' => 'required|integer|min:1',
            'surah_end' => 'nullable|integer|between:1,114',
            'ayah_end' => 'nullable|integer|min:1',
        ]);

        $position = app(QuranPositionService::class)->range(
            (int) $validated['surah_start'],
            (int) $validated['ayah_start'],
            isset($validated['surah_end']) ? (int) $validated['surah_end'] : null,
            isset($validated['ayah_end']) ? (int) $validated['ayah_end'] : null,
        );

        return response()->json([
            'found' => $position !== null,
            'position' => $position,
        ]);
    }
}
