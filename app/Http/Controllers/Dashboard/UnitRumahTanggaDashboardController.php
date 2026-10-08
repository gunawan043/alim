<?php

namespace App\Http\Controllers\Dashboard;

/**
 * Dashboard Unit Rumah Tangga (URT).
 *
 * Cakupan mengikuti konteks sekolah user (jika tidak terikat unit → global).
 */
class UnitRumahTanggaDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.urt';

    protected int $cacheTtl = 300;

    protected function getRoleSlug(): string
    {
        return 'unit-rumah-tangga';
    }

    protected function getRoleLabel(): string
    {
        return 'Unit Rumah Tangga';
    }
}
