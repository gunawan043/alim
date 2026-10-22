<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Services\Uks\UksMode;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard UKS.
 *
 * Scope:
 *  - Sekolah mengikuti konteks user (base controller).
 *  - Staf UKS Putra hanya melihat pasien santra putra (L);
 *    Staf UKS Putri hanya santri putri (P); Kepala UKS melihat semua.
 */
class UksDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.uks';

    protected int $cacheTtl = 300;

    protected function getRoleSlug(): string
    {
        return 'uks';
    }

    protected function getRoleLabel(): string
    {
        return 'UKS';
    }

    protected function resolveWidgets(array $config, string $jabatanCode, array $tugasTambahan): array
    {
        $widgets = parent::resolveWidgets($config, $jabatanCode, $tugasTambahan);

        if (UksMode::isSimple()) {
            $widgets = array_values(array_filter(
                $widgets,
                fn ($widget) => ! in_array($widget, [
                    'stat-antrean-hari-ini',
                    'table-antrean-hari-ini',
                ], true)
            ));
        }

        return $widgets;
    }

    protected function canAccessDashboard(User $user): bool
    {
        if (parent::canAccessDashboard($user)) {
            return true;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'UKS',
            'Kepala UKS',
            'Admin UKS Putra',
            'Admin UKS Putri',
        ])) {
            return true;
        }

        return function_exists('canPermission') && canPermission('menu-uks-sidebar');
    }

    /** Gender scope dari jabatan: 'L' (putra), 'P' (putri), null (semua). */
    protected function getUksGender(User $user): ?string
    {
        $code = $this->detectJabatanCode($user);

        if (str_contains($code, 'staf_uks_putra')) {
            return 'L';
        }

        if (str_contains($code, 'staf_uks_putri')) {
            return 'P';
        }

        return null;
    }

    /**
     * Terapkan scope gender UKS ke query (via tabel students).
     */
    protected function applyUksScope($query, User $user, string $studentCol = 'student_id')
    {
        $gender = $this->getUksGender($user);

        if ($gender !== null) {
            $query->whereExists(function ($q) use ($studentCol, $gender) {
                $q->select(DB::raw(1))
                    ->from('students as st')
                    ->whereColumn('st.id', $studentCol)
                    ->where('st.gender', $gender);
            });
        }

        return $query;
    }
}
