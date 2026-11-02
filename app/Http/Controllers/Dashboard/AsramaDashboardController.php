<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Asrama.
 *
 * Scope otomatis per peran:
 *  - Kepala/Wakil/TU  → seluruh asrama unit (kecuali dibatasi data penugasan)
 *  - Musrif/Musyrif   → asrama dari dormitory_staff_assignments & kamar dari room_supervisors
 *  - Wali Kamar       → kamar dari room_supervisors
 */
class AsramaDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.asrama';

    protected int $cacheTtl = 300;

    /** Cache scope per user (per request). */
    protected array $asramaScopes = [];

    protected function getRoleSlug(): string
    {
        return 'asrama';
    }

    protected function getRoleLabel(): string
    {
        return 'Asrama';
    }

    /**
     * Rangkap jabatan: kepala asrama tetap boleh membuka dashboard asrama
     * walau role utamanya saat ini Satuan Pendidikan.
     */
    protected function canAccessDashboard(User $user): bool
    {
        if ($user->hasRole($this->getRoleLabel())) {
            return true;
        }

        try {
            if (\App\Models\Dormitory::where('head_id', $user->id)->where('is_active', true)->exists()) {
                return true;
            }

            $today = now()->toDateString();

            return DB::table('dormitory_staff_assignments')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                ->exists()
                || DB::table('room_supervisors')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                    ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function detectJabatanCode(User $user): string
    {
        try {
            if (\App\Models\Dormitory::where('head_id', $user->id)->where('is_active', true)->exists()) {
                $code = DB::table('structural_positions')
                    ->where('name', 'Kepala Asrama')
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

    /**
     * Scope asrama user.
     * Array kosong = tidak dibatasi (melihat semua asrama unit).
     */
    protected function getAsramaScope(User $user): object
    {
        if (isset($this->asramaScopes[$user->id])) {
            return $this->asramaScopes[$user->id];
        }

        $roomIds = [];
        $dormitoryIds = [];

        try {
            $today = now()->toDateString();

            $roomIds = DB::table('room_supervisors')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                ->pluck('room_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $dormitoryIds = DB::table('dormitory_staff_assignments')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                ->pluck('dormitory_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Kepala asrama ditandai di master asrama
            $dormitoryIds = array_merge($dormitoryIds, DB::table('dormitories')
                ->where('head_id', $user->id)
                ->where('is_active', 1)
                ->pluck('id')
                ->all());

            // Kamar yang dibina → tambahkan asrama induknya
            if (! empty($roomIds)) {
                $dormitoryIds = array_merge(
                    $dormitoryIds,
                    DB::table('dormitory_rooms')->whereIn('id', $roomIds)->pluck('dormitory_id')->all()
                );
            }
        } catch (\Throwable $e) {
            Log::warning('getAsramaScope failed: '.$e->getMessage());
        }

        return $this->asramaScopes[$user->id] = (object) [
            'roomIds' => array_values(array_unique(array_filter($roomIds))),
            'dormitoryIds' => array_values(array_unique(array_filter($dormitoryIds))),
        ];
    }

    /**
     * Terapkan scope asrama ke query builder.
     * Prioritas: kamar (paling spesifik) → asrama → tanpa filter.
     */
    protected function scopeAsramaQuery($query, User $user, string $roomCol = 'room_id', string $dormCol = 'dormitory_id')
    {
        $scope = $this->getAsramaScope($user);

        if (! empty($scope->roomIds)) {
            return $query->whereIn($roomCol, $scope->roomIds);
        }

        if (! empty($scope->dormitoryIds)) {
            return $query->whereIn($dormCol, $scope->dormitoryIds);
        }

        return $query;
    }
}
