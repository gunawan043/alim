<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

    /**
     * Rangkap jabatan: kepala sekolah tetap boleh membuka dashboard satuan
     * pendidikan walau role utamanya saat ini Asrama.
     */
    protected function canAccessDashboard(User $user): bool
    {
        return $user->hasRole($this->getRoleLabel())
            || School::where('principal_user_id', $user->id)->exists();
    }

    protected function detectJabatanCode(User $user): string
    {
        try {
            if (School::where('principal_user_id', $user->id)->exists()) {
                $code = DB::table('structural_positions')
                    ->where('name', 'Kepala Satuan Pendidikan')
                    ->value('code');

                if ($code) {
                    return $code;
                }
            }
        } catch (\Throwable) {
            // fallback ke deteksi bawaan
        }

        return parent::detectJabatanCode($user);
    }
}
