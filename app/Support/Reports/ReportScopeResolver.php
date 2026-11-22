<?php

namespace App\Support\Reports;

use App\Models\Dormitory;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Menentukan cakupan data laporan untuk pengguna yang sedang membuka halaman.
 *
 * Urutan resolusi (yang pertama menang):
 * 1. schoolContextId dari SchoolContextMiddleware — sudah menghormati pilihan
 *    sekolah Super Admin dan View-As.
 * 2. isGlobalView — pemegang view_global_school_data tanpa pilihan sekolah.
 * 3. Satuan kerja pengguna yang dipetakan ke asrama.
 * 4. Tidak terpetakan — pondok-wide, sesuai keputusan pemilik aplikasi:
 *    unit pendukung (keuangan, gizi, perpustakaan, UKS, rumah tangga, humas)
 *    tetap membaca data pondok, bukan data satuan pendidikan lain.
 */
class ReportScopeResolver
{
    public function resolve(Request $request): ReportScope
    {
        $user = $request->user();

        if (! $user) {
            return ReportScope::none('Sesi pengguna tidak dikenali.');
        }

        $schoolId = $request->attributes->get('schoolContextId');

        // View-As tanpa sekolah: jangan jatuh ke cakupan pengguna asli.
        if (! $schoolId && $request->attributes->get('viewAsForced')) {
            return ReportScope::none('Pratinjau peran ini tidak memiliki satuan pendidikan.');
        }

        if ($schoolId) {
            $label = $request->attributes->get('schoolContext')?->name
                ?? \App\Models\School::whereKey($schoolId)->value('name')
                ?? 'Satuan pendidikan';

            return ReportScope::school($schoolId, $label);
        }

        if ($request->attributes->get('isGlobalView')) {
            return ReportScope::global();
        }

        $dormitoryScope = $this->dormitoryScope($user);
        if ($dormitoryScope !== null) {
            return $dormitoryScope;
        }

        return ReportScope::global();
    }

    private function dormitoryScope(User $user): ?ReportScope
    {
        $workUnitId = $user->primaryWorkUnit?->work_unit_id;

        if (! $workUnitId) {
            return null;
        }

        $dormitories = Dormitory::query()
            ->where('work_unit_id', $workUnitId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($dormitories->isEmpty()) {
            return null;
        }

        return ReportScope::dormitory(
            $dormitories->pluck('id')->all(),
            $dormitories->pluck('name')->implode(', '),
            $workUnitId,
        );
    }
}
