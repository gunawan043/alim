<?php

namespace App\Http\Middleware;

use App\Authorization\ValueObjects\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BindOrganizationContext
 *
 * Resolves school, academic year, role dimension, and tenant from the request
 * (never from scalar columns on User) and binds OrganizationContext into the
 * container.
 *
 * Resolution sources (in order):
 *   - schoolId:        request attribute `schoolContextId` → config default → null
 *   - academicYearId:  request attribute `schoolContextAcademicYearId` → session
 *                      → config default
 *   - roleDimension:   user->effectiveRoles() (Spatie via User contract)
 *   - tenant:          config `tenant.default`
 *
 * schoolId is intentionally nullable: when no real school ID can be derived
 * from the request the field is left as null.  Sentinel strings such as
 * 'global' or 'unknown' are never written.  Tenant-aware consumers
 * (WaliSantriService, policies) must call hasValidSchool() / currentSchoolId()
 * and enforce their own guard logic.
 *
 * This middleware intentionally never reads $user->school_id, $user->academic_year_id,
 * or $user->role — those columns/fields do not exist on the User model.
 */
final class BindOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // --- Resolve school ID ---
        // Authority is the request attribute set by SchoolContextMiddleware /
        // WaliSchoolContextMiddleware. We never read $user->school_id.
        $schoolId = $request->attributes->get('schoolContextId')
            ?? config('authorization.default_school_id');

        // --- Resolve academic year ---
        // Prefer the request attribute, then session selection, then config default.
        // We never read $user->academic_year_id — User does not own this dimension.
        $academicYearId = $request->attributes->get('schoolContextAcademicYearId')
            ?? $request->session()->get('selected_academic_year_id')
            ?? config('authorization.default_academic_year_id');

        // --- Resolve role dimension ---
        // Derive from Spatie roles via User::effectiveRoles(). Never read $user->role.
        $roleDimension = 'default';
        if ($user !== null) {
            $roles = $user->effectiveRoles();
            if (! empty($roles)) {
                $roleDimension = implode(',', $roles);
            }
        }

        // --- Resolve tenant ---
        // In a multi-tenant deployment, this would come from a subdomain/domain resolver.
        // Fall back to default tenant.
        $tenant = config('tenant.default', 'local');

        $context = new OrganizationContext(
            // Emit null rather than a sentinel string when no real school ID is
            // available.  Tenant-aware consumers guard against null explicitly
            // (WaliSantriService throws TENANT_CONTEXT_REQUIRED; policies return
            // false because null !== uuid).
            schoolId: $schoolId !== null ? (string) $schoolId : null,
            academicYearId: (string) ($academicYearId ?? 'global'),
            roleDimension: (string) $roleDimension,
            tenant: (string) $tenant,
        );

        // Bind into container as per-request singleton
        app()->instance(OrganizationContext::class, $context);

        return $next($request);
    }
}
