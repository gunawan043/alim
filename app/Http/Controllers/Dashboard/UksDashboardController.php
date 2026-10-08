<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
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
