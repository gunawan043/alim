<?php

namespace App\Http\Middleware;

use App\Authorization\ValueObjects\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BindOrganizationContext
 *
 * Resolves school, academic year, department, work unit, position, and tenant
 * from the authenticated user and binds OrganizationContext into the container.
 *
 * This must run after auth middleware (Authenticate) so $request->user() is guaranteed.
 * It is typically placed after or alongside SchoolContextMiddleware since that
 * middleware already computes school context.
 */
final class BindOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // --- Resolve school ID ---
        $schoolId = $request->attributes->get('schoolContextId')
            ?? $user->school_id
            ?? config('authorization.default_school_id', 'unknown');

        // --- Resolve academic year ---
        // First check session (user may have explicitly selected one)
        $academicYearId = $request->session()->get('selected_academic_year_id')
            ?? $user->academic_year_id
            ?? config('authorization.default_academic_year_id', 'global');

        // --- Resolve role dimension ---
        $roleDimension = $user->role ?? 'default';

        // --- Resolve tenant ---
        // In a multi-tenant deployment, this would come from a subdomain/domain resolver.
        // Fall back to default tenant.
        $tenant = config('tenant.default', 'local');

        $context = new OrganizationContext(
            schoolId: (string) $schoolId,
            academicYearId: (string) $academicYearId,
            roleDimension: (string) $roleDimension,
            tenant: (string) $tenant,
        );

        // Bind into container as per-request singleton
        app()->instance(OrganizationContext::class, $context);

        return $next($request);
    }
}