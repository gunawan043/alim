<?php

namespace App\Http\Controllers\Dashboard;

class SatuanPendidikanDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.sp';

    protected int $cacheTtl = 300;

    protected function getRoleSlug(): string
    {
        return 'satuan-pendidikan';
    }

    protected function getRoleLabel(): string
    {
        return 'Satuan Pendidikan';
    }
}
