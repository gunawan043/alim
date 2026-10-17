<?php

namespace App\Http\Controllers\Dashboard;

/**
 * Dashboard Pimpinan (Mudir/Pengasuh, Wadir 1, Wadir 2).
 *
 * Cakupan GLOBAL lintas unit (tanpa filter sekolah) — executive dashboard.
 */
class PimpinanDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.pimpinan';

    protected int $cacheTtl = 300;

    /** Pimpinan melihat data seluruh unit. */
    protected bool $globalScope = true;

    protected function getRoleSlug(): string
    {
        return 'pimpinan';
    }

    protected function getRoleLabel(): string
    {
        return 'Pimpinan';
    }
}
