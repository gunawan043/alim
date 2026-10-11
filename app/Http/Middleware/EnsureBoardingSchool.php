<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gating modul asrama berdasarkan tipe satuan pendidikan:
 *  - `non_boarding` → akses modul asrama ditolak (403).
 *  - `boarding` / `both` → diizinkan.
 *  - Tanpa konteks sekolah (Super Admin/global) → diizinkan.
 */
class EnsureBoardingSchool
{
    public function handle(Request $request, Closure $next): Response
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            $school = School::find($schoolId);

            if ($school && $school->boarding_type === 'non_boarding') {
                abort(403, 'Modul asrama tidak tersedia untuk satuan pendidikan non-boarding.');
            }
        }

        return $next($request);
    }
}
