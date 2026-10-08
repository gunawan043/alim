<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Departemen Tahfidz.
 *
 * Scope otomatis:
 *  - Musyrif (guru halaqah)  → hanya halaqah yang diampu (tahfidz_groups.teacher_id)
 *  - Koordinator Halaqah     → halaqah yang dikoordinasikan (coordinator_id)
 *  - Penguji Tasmi'          → sesi tasmi' yang melibatkan dirinya (mustami/evaluator)
 *  - Kepala/Wakil/TU         → seluruh data unit (mengikuti konteks sekolah)
 */
class TahfidzDashboardController extends DashboardController
{
    protected string $cachePrefix = 'dashboard.tahfidz';

    protected int $cacheTtl = 300;

    protected function getRoleSlug(): string
    {
        return 'tahfidz';
    }

    protected function getRoleLabel(): string
    {
        return 'Departemen Tahfidz';
    }

    /**
     * Scope halaqah user: group id yang diampu/dikoordinasikan.
     * Kepala/Wakil/TU melihat seluruh unit, kecuali punya tugas operasional
     * (Musyrif Tahfidz / Koordinator Halaqah) → dibatasi halaqahnya sendiri.
     */
    protected function getTahfidzGroupIds(User $user): array
    {
        try {
            $code = $this->detectJabatanCode($user);
            $isLead = str_contains($code, 'kepala_departemen_tahfidz')
                || str_contains($code, 'wakil_kepala_departemen_tahfidz')
                || str_contains($code, 'tu_departemen_tahfidz')
                || str_contains($code, 'tata_usaha_departemen_tahfidz');

            $tugas = DB::table('gtk_additional_tasks')
                ->where('user_id', $user->id)
                ->pluck('nama_tugas')
                ->all();

            $hasOperationalTask = in_array('Musyrif Tahfidz', $tugas, true)
                || in_array('Koordinator Halaqah', $tugas, true);

            if ($isLead && ! $hasOperationalTask) {
                return [];
            }

            return DB::table('tahfidz_groups')
                ->where(fn ($q) => $q->where('teacher_id', $user->id)->orWhere('coordinator_id', $user->id))
                ->pluck('id')
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('getTahfidzGroupIds failed: ' . $e->getMessage());

            return [];
        }
    }

    /** Terapkan scope halaqah ke query. */
    protected function scopeTahfidzQuery($query, User $user, string $groupCol = 'tahfidz_group_id')
    {
        $groupIds = $this->getTahfidzGroupIds($user);

        if (! empty($groupIds)) {
            $query->whereIn($groupCol, $groupIds);
        }

        return $query;
    }

    /** Apakah user terlibat sebagai penguji tasmi' (mustami / evaluator)? */
    protected function getTasmianMustamiId(User $user): ?string
    {
        try {
            $asMustami = DB::table('tahfidz_tasmian_participants')->where('mustami_id', $user->id)->exists();
            $asEvaluator = DB::table('tahfidz_tasmian_scores')->where('evaluator_id', $user->id)->exists();

            return ($asMustami || $asEvaluator) ? $user->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
