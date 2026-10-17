<?php

namespace App\Http\Controllers\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetRoom;
use App\Models\ProcurementRequest;
use App\Models\RoomBooking;
use App\Models\School;
use App\Services\SarprasCacheInvalidator;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Base controller for all Sarpras modules.
 * Provides canViewAll, authorizeXAccess helpers and ApiResponse trait.
 * Individual controllers should extend this to reuse.
 */
abstract class SarprasBaseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        view()->share('userId', request()->route('userId') ?? (auth()->check() ? auth()->id() : null));
    }

    /**
     * Cek apakah user bisa melihat SEMUA data sarpras (bukan scoped)
     */
    protected function canViewAll(Request $request): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return Gate::forUser($user)->allows('sarpras-view-all');
    }

    /**
     * Check if resource is accessible by current user via school-scope.
     * Returns true if user can view all, or resource's schoolId matches request scope.
     */
    protected function canAccess(Request $request, object $resource, string $schoolField = 'school_id'): bool
    {
        if ($this->canViewAll($request)) {
            return true;
        }
        $schoolId = $request->attributes->get('schoolContextId');

        return ! $schoolId || ($resource->{$schoolField} ?? null) === $schoolId;
    }

    /**
     * Scope query ke school berdasarkan schoolContextId
     * User tanpa akses all hanya bisa melihat data sekolahnya sendiri
     *
     * @param  Builder  $query
     * @return Builder
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
     * @param  AssetBuilding  $building
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeBuildingAccess($building, Request $request): void
    {
        if (! $this->canAccess($request, $building)) {
            abort(403, 'Anda tidak memiliki akses ke gedung ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses ruang tertentu
     *
     * @param  AssetRoom  $room
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeRoomAccess($room, Request $request): void
    {
        if (! $this->canAccess($request, $room)) {
            abort(403, 'Anda tidak memiliki akses ke ruang ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses aset tertentu
     *
     * @param  Asset  $asset
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeAssetAccess($asset, Request $request): void
    {
        if (! $this->canAccess($request, $asset)) {
            abort(403, 'Anda tidak memiliki akses ke aset ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses peminjaman tertentu
     *
     * @param  AssetLoan  $loan
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeLoanAccess($loan, Request $request): void
    {
        if (! $this->canAccess($request, $loan)) {
            abort(403, 'Anda tidak memiliki akses ke peminjaman ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses pengadaan tertentu
     *
     * @param  ProcurementRequest  $procurement
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeProcurementAccess($procurement, Request $request): void
    {
        if (! $this->canAccess($request, $procurement)) {
            abort(403, 'Anda tidak memiliki akses ke pengadaan ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses booking ruangan tertentu
     *
     * @param  RoomBooking  $booking
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeBookingAccess($booking, Request $request): void
    {
        if ($this->canViewAll($request)) {
            return;
        }

        $schoolId = $request->attributes->get('schoolContextId');
        $bookingSchoolId = $booking->room?->school_id ?? null;
        if ($schoolId && $bookingSchoolId && $bookingSchoolId !== $schoolId) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }
    }

    /**
     * Cek apakah user bisa mengakses jadwal pemeliharaan tertentu
     *
     * @param  AssetMaintenanceSchedule  $schedule
     *
     * @throws AccessDeniedHttpException
     */
    protected function authorizeMaintenanceAccess($schedule, Request $request): void
    {
        if (! $this->canAccess($request, $schedule)) {
            abort(403, 'Anda tidak memiliki akses ke jadwal pemeliharaan ini.');
        }
    }

    /**
     * Get work unit ID dari school
     */
    protected function getWorkUnitIdFromSchool(string $schoolId): ?string
    {
        $school = School::find($schoolId);

        return $school?->work_unit_id;
    }

    /**
     * Bump dashboard cache version to invalidate all cached stat queries.
     * Call this after any Sarpras data mutation.
     */
    protected function bumpDashboardCache(int $schoolId = 0): void
    {
        app(SarprasCacheInvalidator::class)->invalidateAll();
    }
}
