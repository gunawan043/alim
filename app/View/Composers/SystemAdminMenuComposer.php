<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\School;
use App\Services\ViewAsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SystemAdminMenuComposer
{
    public function compose(View $view): void
    {
        $view->with('systemAdminMenu', $this->buildMenu());

        // Only share view-as data when a system admin or super admin is viewing
        $user = Auth::user();
        $isSystemAdmin = $user && method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
        $isSuperAdmin = $user && method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate_role');

        if ($isSystemAdmin || $isSuperAdmin) {
            $viewAsService = app(ViewAsService::class);
            $viewAsRole = $viewAsService->getCurrentViewRole();
            $viewAsUserId = $viewAsService->getCurrentViewUserId();
            $originalUserId = $viewAsService->originalUserId();

            $view->with('viewAsSwitcherVisible', true);
            $view->with('viewAsRole', $viewAsRole);
            $view->with('viewAsUserId', $viewAsUserId);
            $view->with('originalUserId', $originalUserId);
            $view->with('isViewingAs', $viewAsService->isViewingAs());
            $view->with('systemRoles', \App\Models\Role::where('guard_name', 'web')
                ->whereNotIn('name', ['Super Admin', 'System Admin'])
                ->orderBy('name')
                ->get());
            $view->with('schools', School::active()->orderBy('name')->get(['id', 'name']));
        }
    }

    private function buildMenu(): array
    {
        return [
            [
                'group' => 'Sistem',
                'icon' => 'bx-server',
                'items' => [
                    ['label' => 'Dashboard Sistem',   'route' => 'system.dashboard',  'icon' => 'bx-tachometer'],
                    ['label' => 'Monitoring',         'route' => 'system.monitoring', 'icon' => 'bx-pulse'],
                    ['label' => 'Maintenance',        'route' => 'system.maintenance', 'icon' => 'bx-wrench'],
                ],
            ],
            [
                'group' => 'Identitas & Akses',
                'icon' => 'bx-shield-quarter',
                'items' => [
                    ['label' => 'Manajemen User',    'route' => 'users.index',      'icon' => 'bx-user'],
                    ['label' => 'Role',              'route' => 'roles.index',      'icon' => 'bx-group'],
                    ['label' => 'Permission',        'route' => 'permissions.index', 'icon' => 'bx-key'],
                ],
            ],
            [
                'group' => 'Konfigurasi',
                'icon' => 'bx-cog',
                'items' => [
                    ['label' => 'Feature Activation', 'route' => 'system.features',  'icon' => 'bx-toggle-right'],
                    ['label' => 'Master Data',       'route' => 'master-data.index', 'icon' => 'bx-data'],
                    ['label' => 'System Configuration', 'route' => 'system.config', 'icon' => 'bx-slider-alt'],
                ],
            ],
            [
                'group' => 'Operasional',
                'icon' => 'bx-wrench',
                'items' => [
                    ['label' => 'Audit Log',         'route' => 'audit-logs.index', 'icon' => 'bx-history'],
                    ['label' => 'Developer Tools',   'route' => 'system.devtools',  'icon' => 'bx-code-alt'],
                ],
            ],
        ];
    }
}
