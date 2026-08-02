<?php

namespace App\Http\Middleware;

use App\Services\SchoolGroupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SchoolContextMiddleware
 *
 * Runs after auth and role.access middleware.
 * Determines the "school context" for the current user request:
 *
 * - `$request->schoolContext`    : School|null  — the school this user is scoped to
 * - `$request->isGlobalView`     : bool         — can see all schools
 * - `$request->schoolContextId`  : string|null  — UUID of scoped school
 *
 * Logic:
 * 1. If user has `view_global_school_data` permission → global view (no filter)
 * 2. Otherwise, determine school from gtk_employments
 * 3. If no school assigned → user must be redirected to school selection
 *
 * Usage in controllers:
 *   $schoolId = request()->schoolContextId;
 *   if (request()->isGlobalView) { ... } // show all
 */
class SchoolContextMiddleware
{
    public function __construct(
        private SchoolGroupService $schoolGroupService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Can this user see all schools?
        $isGlobalView = SchoolGroupService::userCanGlobalView($user);
        $request->attributes->set('isGlobalView', $isGlobalView);

        $isSystemAdmin = method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
        $isSuperAdmin = ! $isSystemAdmin
            && method_exists($user, 'hasPermissionTo')
            && $user->hasPermissionTo('impersonate_role');

        $viewAs = app(\App\Services\ViewAsService::class);
        $canUseViewAs = ($isSystemAdmin || $isSuperAdmin) && $viewAs->isViewingAs();
        $effectiveUserId = $viewAs->getCurrentViewUserId();

        // View-As context: when SA or Super Admin is Viewing-As a role/user, force a
        // scoped school context from the ViewAsService so the impersonated user
        // is NOT granted global access (they wouldn't have it in reality).
        if ($canUseViewAs) {
            // If Login-As (specific user), resolve their school
            if ($effectiveUserId !== null) {
                $targetUser = \App\Models\User::find($effectiveUserId);
                if ($targetUser) {
                    $viewAs->setCurrentViewRole($targetUser->getRoleNames()->first());
                    $school = \App\Services\SchoolGroupService::getUserSchool($targetUser);
                    $request->attributes->set('schoolContextId', $school?->id);
                    $request->attributes->set('schoolContext', $school);
                    $request->attributes->set('schoolGender', $school?->school_gender);
                    $request->attributes->set('saSchoolScoped', true);
                    $request->attributes->set('isGlobalView', false);
                    $request->attributes->set('viewAsForced', true);

                    return $next($request);
                }
            }

            $viewAsRole = $viewAs->getCurrentViewRole();
            if ($viewAsRole !== null) {
                $ctx = app(\App\Services\ViewAsService::class)->getCurrentViewContext();
                $ctxSchoolId = $ctx['school_id'] ?? null;
                if ($ctxSchoolId) {
                    $school = \App\Models\School::find($ctxSchoolId);
                    $request->attributes->set('schoolContextId', $school?->id);
                    $request->attributes->set('schoolContext', $school);
                    $request->attributes->set('schoolGender', $school?->school_gender);
                    $request->attributes->set('saSchoolScoped', true);
                    $request->attributes->set('isGlobalView', false);
                    $request->attributes->set('viewAsForced', true);

                    return $next($request);
                }

                // View-as active but no explicit school picked — force scoped
                // based on the real user's own school employment (so SA/Super
                // Admin can preview data scoped to their own school).
                if ($isSystemAdmin) {
                    return $next($request);
                }
                $school = SchoolGroupService::getUserSchool($user);
                $request->attributes->set('schoolContext', $school);
                $request->attributes->set('schoolContextId', $school?->id);
                $request->attributes->set('schoolGender', $school?->school_gender);
                $request->attributes->set('saSchoolScoped', true);
                $request->attributes->set('isGlobalView', false);
                $request->attributes->set('viewAsForced', true);

                return $next($request);
            }
        }

        if ($isGlobalView) {
            // Super Admin: check session for scoped view
            // Session stores 'sa_school_id' when SA picks a specific school
            $saSchoolId = $request->session()->get('sa_school_id');

            if ($saSchoolId) {
                $school = \App\Models\School::find($saSchoolId);
                $request->attributes->set('schoolContextId', $school?->id);
                $request->attributes->set('schoolContext', $school);
                $request->attributes->set('schoolGender', $school?->school_gender);
                $request->attributes->set('saSchoolScoped', true);
            } else {
                // True global view — no restriction
                $request->attributes->set('schoolContextId', null);
                $request->attributes->set('schoolContext', null);
                $request->attributes->set('schoolGender', null);
                $request->attributes->set('saSchoolScoped', false);
            }

            return $next($request);
        }

        // Scoped user: determine their school from employment/work unit
        $school = SchoolGroupService::getUserSchool($user);
        $request->attributes->set('schoolContext', $school);
        $request->attributes->set('schoolContextId', $school?->id);
        $request->attributes->set('schoolGender', $school?->school_gender);
        $request->attributes->set('saSchoolScoped', false);

        return $next($request);
    }
}
