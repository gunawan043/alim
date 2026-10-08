<?php

namespace App\Http\Controllers\Dashboard;

/**
 * Dashboard Keuangan.
 *
 * Cakupan mengikuti konteks sekolah user (bendahara/kasir umumnya per unit).
 * Jika user tidak terikat sekolah (level yayasan), data tampil global.
 */
class KeuanganDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.keuangan';

    protected int $cacheTtl = 300;

    protected function getRoleSlug(): string
    {
        return 'keuangan';
    }

    protected function getRoleLabel(): string
    {
        return 'Keuangan';
    }
}
