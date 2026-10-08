<?php

namespace App\Http\Controllers\Dashboard;

/**
 * Dashboard Super Admin / System Engineer.
 *
 * Cakupan GLOBAL seluruh sistem.
 */
class SuperAdminDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.superadmin';

    protected int $cacheTtl = 300;

    protected bool $globalScope = true;

    protected function getRoleSlug(): string
    {
        return 'super-admin';
    }

    protected function getRoleLabel(): string
    {
        return 'Super Admin';
    }
}
