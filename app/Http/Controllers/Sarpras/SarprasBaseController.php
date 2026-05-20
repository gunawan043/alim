<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\GtkWorkUnit;
use Illuminate\Http\Request;

abstract class SarprasBaseController extends Controller
{
    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }


    /**
     * Cek apakah user bisa melihat SEMUA data sarpras (bukan scoped)
     *
     * @return bool
     */
    protected function canViewAll(Request $request): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Permission-based access
        if ($user->can('sarpras_all_access')) return true;
        if ($user->can('inventory_view')) return true;

        // Work unit-based access (Unit Rumah Tangga - PAH-ADM-003)
        $hasRumahTangga = GtkWorkUnit::where('user_id', $user->id)
            ->whereHas('workUnit', fn($q) => $q->where('code', 'PAH-ADM-003'))
            ->exists();

        return $hasRumahTangga;
    }

    /**
     * Scope query ke school berdasarkan schoolContextId
     * User tanpa akses all hanya bisa melihat data sekolahnya sendiri
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function scopeToSchool(Request $request, $query)
    {
        if ($this->canViewAll($request)) {
            return $query;
        }

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }

        return $query;
    }

    /**
     * Cek apakah user bisa mengakses gedung tertentu
     *
     * @param \App\Models\AssetBuilding $building
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeBuildingAccess($building, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $building->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke gedung ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses ruang tertentu
     *
     * @param \App\Models\AssetRoom $room
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeRoomAccess($room, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $room->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke ruang ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses aset tertentu
     *
     * @param \App\Models\Asset $asset
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeAssetAccess($asset, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $asset->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke aset ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses peminjaman tertentu
     *
     * @param \App\Models\AssetLoan $loan
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeLoanAccess($loan, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $loan->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke peminjaman ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses pengadaan tertentu
     *
     * @param \App\Models\ProcurementRequest $procurement
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeProcurementAccess($procurement, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $procurement->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke pengadaan ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses booking ruangan tertentu
     *
     * @param \App\Models\RoomBooking $booking
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeBookingAccess($booking, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $booking->room->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses jadwal pemeliharaan tertentu
     *
     * @param \App\Models\AssetMaintenanceSchedule $schedule
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function authorizeMaintenanceAccess($schedule, Request $request): void
    {
        if ($this->canViewAll($request)) return;

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $schedule->school_id !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke jadwal pemeliharaan ini.');
        }
    }

    /**
     * Get work unit ID dari school
     *
     * @param string $schoolId
     * @return string|null
     */
    protected function getWorkUnitIdFromSchool(string $schoolId): ?string
    {
        $school = \App\Models\School::find($schoolId);
        return $school?->work_unit_id;
    }
}
