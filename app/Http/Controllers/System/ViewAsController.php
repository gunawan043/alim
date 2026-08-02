<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\ViewAsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

class ViewAsController extends Controller
{
    public function __construct(private readonly ViewAsService $viewAs) {}

    private function authorizeSystemAdminOrSuperAdmin(Request $request): void
    {
        $user = $request->user();
        $isSA = $user && method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
        $isSuper = $user && method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate_role');
        if (! $isSA && ! $isSuper) {
            abort(403, 'System Administrator or Super Admin only.');
        }
    }

    /**
     * Return URL the user came from (same page). Defaults to system dashboard.
     */
    private function backUrl(Request $request): string
    {
        $back = $request->input('redirect_to');
        if (is_string($back) && $back !== '' && URL::isValidUrl($back) === false) {
            // allow relative path
            return $back;
        }
        if (is_string($back) && $back !== '') {
            // absolute URL — only accept if same host
            $referer = $request->headers->get('referer');
            if ($referer && str_starts_with($back, parse_url($referer, PHP_URL_HOST ?: '') ?: '#invalid-host')) {
                return $back;
            }
        }
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, $request->getHost())) {
            return $referer;
        }

        return route('system.dashboard');
    }

    public function setRole(Request $request)
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);

        $payload = $request->validate([
            'role' => ['nullable', 'string', 'max:191'],
            'user_id' => ['nullable', 'string', 'max:64'],
        ]);

        $roleName = $payload['role'] ?? null;
        $userId = $payload['user_id'] ?? null;

        // ── Login As flow (specific user) ──
        if ($userId) {
            $target = \App\Models\User::find($userId);
            if (! $target) {
                return back()->withErrors(['user_id' => 'User not found.']);
            }
            $this->viewAs->loginAs($userId, $request->user());
            $this->viewAs->clearCurrentViewContext();

            return redirect($this->backUrl($request))
                ->with('status', "Login As: {$target->name}");
        }

        // ── View As (role only) ──
        if ($roleName !== null && $roleName !== '') {
            $exists = \App\Models\Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->whereNotIn('name', ['Super Admin'])
                ->exists();
            if (! $exists) {
                return back()->withErrors(['role' => "Role '{$roleName}' is not available for View As."]);
            }
        }

        // If a role was picked, also bind a target user (first user with role) so
        // routes grouped by {userId} resolve to a valid dashboard.
        if ($roleName !== null && $roleName !== '') {
            $target = \App\Models\User::role($roleName)->first()
                ?? \App\Models\User::whereHas('roles', fn ($r) => $r->where('name', $roleName))->first();
            if ($target) {
                $this->viewAs->loginAs($target->id, $request->user());
            } else {
                $this->viewAs->setCurrentViewRole($roleName);
                $this->viewAs->clearCurrentViewContext();
            }
        } else {
            $this->viewAs->clearAll();
        }

        $ctxSchool = $request->input('school_id');
        if (is_string($ctxSchool) && $ctxSchool !== '') {
            $this->viewAs->setCurrentViewContext(['school_id' => $ctxSchool]);
        }

        return redirect($this->backUrl($request))
            ->with('status', $roleName ? "Viewing as: {$roleName}" : 'System Admin mode');
    }

    public function loginAs(Request $request)
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);

        $payload = $request->validate([
            'user_id' => ['required', 'string', 'max:64'],
        ]);

        $target = \App\Models\User::find($payload['user_id']);
        if (! $target) {
            return back()->withErrors(['user_id' => 'User not found.']);
        }

        $this->viewAs->loginAs($target->id, $request->user());
        $this->viewAs->clearCurrentViewContext();

        return redirect($this->backUrl($request))
            ->with('status', "Login As: {$target->name}");
    }

    public function restore(Request $request)
    {
        $this->viewAs->clearAll();

        return redirect($this->backUrl($request))
            ->with('status', 'Restored to System Admin identity.');
    }

    public function listUsers(Request $request): JsonResponse
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);

        $payload = $request->validate([
            'q' => ['nullable', 'string', 'max:191'],
            'role' => ['nullable', 'string', 'max:191'],
        ]);

        $query = \App\Models\User::query()
            ->whereNull('users.deleted_at')
            ->select('users.id', 'users.name', 'users.email');

        if (! empty($payload['q'])) {
            $term = '%'.$payload['q'].'%';
            $query->where(function ($w) use ($term) {
                $w->where('users.name', 'ilike', $term)
                    ->orWhere('users.email', 'ilike', $term);
            });
        }

        if (! empty($payload['role'])) {
            $query->whereHas('roles', fn ($r) => $r->where('name', $payload['role']));
        }

        $users = $query->orderBy('users.name')->limit(25)->get();

        $users->each(function ($u) {
            $u->roles = $u->getRoleNames();
        });

        return response()->json(['users' => $users]);
    }

    public function setContext(Request $request): JsonResponse
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);

        $payload = $request->validate([
            'school_id' => ['nullable', 'string', 'max:64'],
            'academic_year_id' => ['nullable', 'string', 'max:64'],
            'dormitory_id' => ['nullable', 'string', 'max:64'],
            'wing_id' => ['nullable', 'string', 'max:64'],
            'room_id' => ['nullable', 'string', 'max:64'],
        ]);

        $this->viewAs->setCurrentViewContext($payload);

        return response()->json([
            'ok' => true,
            'view_as_role' => $this->viewAs->getCurrentViewRole(),
            'view_as_context' => $this->viewAs->getCurrentViewContext(),
            'message' => 'View context updated.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);
        $this->viewAs->clearAll();

        return response()->json([
            'ok' => true,
            'view_as_role' => null,
            'view_as_context' => [],
            'message' => 'View As reset to System Admin mode.',
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        $this->authorizeSystemAdminOrSuperAdmin($request);
        $roles = $this->viewAs->getAvailableViewRoles()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'level' => $r->level])
            ->values();

        return response()->json([
            'view_as_role' => $this->viewAs->getCurrentViewRole(),
            'view_as_context' => $this->viewAs->getCurrentViewContext(),
            'available_roles' => $roles,
        ]);
    }
}
