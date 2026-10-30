<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Base untuk API integrasi antar-aplikasi (POS, recruitment, ...).
 *
 * Menyediakan envelope respons standar dan helper query umum.
 */
abstract class IntegrationController extends Controller
{
    use ApiResponse;

    /**
     * Resolve the requested page size, clamped to a sane range.
     */
    protected function perPage(Request $request, int $default = 50, int $max = 200): int
    {
        return min(max($request->integer('per_page', $default), 1), $max);
    }

    /**
     * Resolve an optional ISO-8601 / date-string filter into a Carbon instance.
     */
    protected function dateFilter(Request $request, string $key): ?Carbon
    {
        if (! $request->filled($key)) {
            return null;
        }

        return $request->date($key);
    }

    /**
     * Ambil kata kunci pencarian dengan batas panjang (mitigasi DoS pada LIKE).
     */
    protected function searchTerm(Request $request, int $maxLength = 100): ?string
    {
        if (! $request->filled('search')) {
            return null;
        }

        return mb_substr($request->string('search')->toString(), 0, $maxLength);
    }

    /**
     * Normalize an Indonesian phone number to the 62-prefixed form.
     */
    protected function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return '62'.$digits;
    }
}
