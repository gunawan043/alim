<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeAccess
{
    /**
     * Redirect rules based on user role/authentication state.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for public routes (login, logout, password reset)
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        $user = Auth::user();

        // ============================================================
        // RULE 1: Unauthenticated users → let auth middleware handle
        // ============================================================
        if (! $user) {
            return $next($request);
        }

        // ============================================================
        // RULE 2: Applicants → redirect to external recruitment portal
        // Detection: user has RecruitmentProfile but NO Spatie role
        // ============================================================
        if ($this->isApplicant($user)) {
            return $this->redirectAway(
                config('app.recruitment_url', 'https://recruitment.abuhurairah.id'),
                'Akses ditolak. Silakan gunakan portal recruitment.'
            );
        }

        // ============================================================
        // RULE 3: Wali Santri → hard block, no access
        // Detection: is_wali flag OR role name = "Wali Santri"
        // ============================================================
        if ($this->isWaliSantri($user)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Akses ditolak. Website ini hanya untuk pegawai.'], 403);
            }

            return response()->view('errors.wali-santri-blocked', [], 403);
        }

        // ============================================================
        // RULE 4: Unknown role → redirect to validator page
        // Detection: user has NO active Spatie role
        // ============================================================
        if (! $this->hasValidEmployeeRole($user)) {
            if (! $request->is('access-denied') && ! $request->is('auth/validator')) {
                return redirect()->route('auth.validator')->with('warning', 'Akun Anda tidak memiliki role yang dikenali di sistem ini.');
            }
        }

        return $next($request);
    }

    // ================================================================
    // DETECTION HELPERS
    // ================================================================

    private function isApplicant($user): bool
    {
        // Applicant = has RecruitmentProfile but NO Spatie role at all
        // OR has a role that matches "applicant" keyword
        if (! $user->relationLoaded('recruitmentProfile') && ! $user->recruitmentProfile) {
            // Eager load check skipped, do direct query if needed
            try {
                if ($user->recruitmentProfile) {
                    // Has recruitment profile
                    $roles = $user->getRoleNames();

                    // If no roles, they're an applicant
                    return $roles->isEmpty();
                }
            } catch (\Throwable) {
                // relation doesn't exist or not loaded
            }
        }

        // Check via relationship if loaded
        try {
            if ($user->recruitmentProfile) {
                return $user->getRoleNames()->isEmpty();
            }
        } catch (\Throwable) {
            // not loaded, skip
        }

        // Fallback: check if role name contains "applicant"
        $roleNames = $user->getRoleNames();

        return $roleNames->contains(fn ($name) => stripos($name, 'applicant') !== false);
    }

    private function isWaliSantri($user): bool
    {
        // Method 1: is_wali boolean flag on users table
        if (property_exists($user, 'is_wali') || isset($user->is_wali)) {
            if ($user->is_wali) {
                return true;
            }
        }

        // Method 2: Check via raw attribute
        try {
            if ($user->getAttribute('is_wali') === true || $user->getAttribute('is_wali') === 1) {
                return true;
            }
        } catch (\Throwable) {
            // skip
        }

        // Method 3: Has "Wali Santri" Spatie role
        return canPermission('wali-santri');
    }

    private function hasValidEmployeeRole($user): bool
    {
        // Valid employee = has at least one Spatie role (excluding Wali Santri)
        $roles = $user->getRoleNames();

        if ($roles->isEmpty()) {
            return false;
        }

        // Exclude "Wali Santri" from valid employee roles
        return ! $roles->contains(fn ($name) => $name === 'Wali Santri');
    }

    private function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'login',
            'logout',
            'password.request',
            'password.email',
            'password.otp.form',
            'password.otp.verify',
            'password.otp.resend',
            'password.reset.form',
            'password.update',
            'password.cancel',
            'register',
            'auth.validator',
            'access-denied',
            'password/forgot',
            'password/otp',
            'password/reset',
        ];

        foreach ($publicRoutes as $route) {
            if ($request->is($route) || $request->routeIs($route)) {
                return true;
            }
        }

        // Also allow static paths
        $publicPaths = [
            'password/forgot',
            'password/otp',
            'password/reset',
        ];

        foreach ($publicPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    private function redirectAway(string $url, string $message): Response
    {
        // Server-side redirect (more secure than client-side)
        // Use 302 for temporary redirect, 301 for permanent
        if (config('app.env') === 'production') {
            return response('<script>window.location.href="'.e($url).'?msg='.urlencode($message).'";</script><meta http-equiv="refresh" content="0;url='.e($url).'">', 200)
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return redirect($url)->with('info', $message);
    }

    private function waliSantriBlockedView(Request $request): string
    {
        return view('errors.wali-santri-blocked', [
            'message' => 'Website ini hanya untuk Pegawai. Aplikasi Wali Santri tersedia di aplikasi mobile.',
        ])->render();
    }
}
