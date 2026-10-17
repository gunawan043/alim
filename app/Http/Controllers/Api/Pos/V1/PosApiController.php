<?php

namespace App\Http\Controllers\Api\Pos\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Shared base for the POS / Kasir integration API (read-only).
 *
 * All endpoints reply with the standard envelope:
 * { success, message, data, meta, errors } — see App\Support\ApiResponse.
 */
abstract class PosApiController extends Controller
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
     * Normalize an Indonesian phone number to the 62-prefixed form.
     *
     * Examples: 0812xxx → 62812xxx, 812xxx → 62812xxx, +62 812xxx → 62812xxx.
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
