<?php

namespace App\Http\Controllers\Dashboard;

/**
 * Dashboard Humas & Personalia (SDM).
 *
 * Cakupan GLOBAL lintas unit (HR mengelola seluruh GTK yayasan).
 */
class PersonaliaDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.personalia';

    protected int $cacheTtl = 300;

    /** Personalia melihat data seluruh unit. */
    protected bool $globalScope = true;

    protected function getRoleSlug(): string
    {
        return 'personalia';
    }

    protected function getRoleLabel(): string
    {
        return 'Humas Personalia';
    }
}
