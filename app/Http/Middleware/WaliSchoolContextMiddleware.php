<?php

namespace App\Http\Middleware;

use App\Models\WaliSantri;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * WaliSchoolContextMiddleware
 *
 * Resolves the school context for Wali API requests.
 * The authoritative tenant source is the wali_santri relation, NEVER users.school_id.
 *
 * Resolution priority:
 *   1. X-Active-School-Id header (client hint → server validates against wali_santri)
 *   2. Distinct active school_ids from WaliSantri where user_id = request user
 *   3. null (registration / bootstrap / multi-school — never guess)
 *
 * Sets request attribute:
 *   - schoolContextId (canonical)
 */
class WaliSchoolContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // Auth middleware should catch this; safe-pass-through
            return $next($request);
        }

        $schoolId = null;

        // --- Priority 1: Client hint (X-Active-School-Id) ---
        // NEVER trust the client alone — validate it against the actual relation.
        $hint = $request->headers->get('X-Active-School-Id');
        if (is_string($hint) && $hint !== '') {
            $valid = WaliSantri::where('user_id', $user->id)
                ->where('school_id', $hint)
                ->exists();

            if ($valid) {
                $schoolId = $hint;
            }
            // Invalid hint → ignore, fall through to Priority 2
        }

        // --- Priority 2: Derive from wali_santri relation ---
        if ($schoolId === null) {
            $activeSchoolIds = WaliSantri::where('user_id', $user->id)
                ->where('status', WaliSantri::STATUS_ACTIVE)
                ->whereNotNull('school_id')
                ->distinct()
                ->pluck('school_id')
                ->filter()
                ->values();

            if ($activeSchoolIds->count() === 1) {
                $schoolId = $activeSchoolIds->first();
            }
            // 0 schools → registration/bootstrap → stay null
            // 2+ schools → multi-school guardian → stay null (never guess)
        }

        // Canonical attribute (matches SchoolContextMiddleware pattern)
        $request->attributes->set('schoolContextId', $schoolId);

        return $next($request);
    }
}
