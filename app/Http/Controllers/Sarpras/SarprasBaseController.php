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
use App\Models\RoomSupervisor;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\WorkUnitSarprasOfficer;
use App\Services\SarprasCacheInvalidator;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Unit Rumah Tangga / Admin Sarpras = pengelola pusat.
     * Unit kerja hanya mengelola data miliknya sendiri.
     */
    protected function isCentralSarpras(?object $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'Admin Sarpras', 'Admin Tata Usaha', 'Unit Rumah Tangga',
        ])) {
            return true;
        }

        if (function_exists('canUserPermission') && canUserPermission($user, 'sarpras_all_access')) {
            return true;
        }

        $jabatan = method_exists($user, 'jabatan')
            ? trim((string) ($user->jabatan->nama ?? $user->jabatan ?? ''))
            : '';

        return in_array($jabatan, [
            'Kepala Unit Rumah Tangga',
            'Koordinator Sarana Prasarana',
            'Koordinator Sarpras',
        ], true);
    }

    /**
     * School context untuk halaman analitik. Pengguna pusat tanpa konteks sekolah
     * memakai sekolah pertama agar dashboard tetap dapat dibuka.
     */
    protected function resolveSchoolContextId(Request $request): string
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            return (string) $schoolId;
        }

        if ($this->isCentralSarpras()) {
            $requested = $request->query('school_id');
            if ($requested && School::query()->whereKey($requested)->exists()) {
                return (string) $requested;
            }

            $fallback = School::query()->orderBy('name')->value('id');
            if ($fallback) {
                return (string) $fallback;
            }
        }

        abort(403, 'Konteks sekolah belum dipilih.');
    }

    protected function authorizeCentralSarpras(): void
    {
        if (! $this->isCentralSarpras()) {
            abort(403, 'Hanya Kepala/TU Unit Rumah Tangga yang dapat mengelola data pusat Sarpras.');
        }
    }

    /**
     * TU/Kepala Unit Rumah Tangga (bukan TU satuan pendidikan).
     */
    protected function isUrtLeadership(?object $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Unit Rumah Tangga', 'Admin Sarpras'])) {
            return true;
        }

        $urtUnitIds = \App\Models\WorkUnit::query()
            ->where('code', 'DEP-URT')
            ->orWhere('name', 'like', '%Rumah Tangga%')
            ->pluck('id')
            ->all();

        if ($urtUnitIds === []) {
            return false;
        }

        return array_intersect($this->accessibleWorkUnitIds($user), $urtUnitIds) !== [];
    }

    /**
     * Work unit yang boleh diakses user: unit GTK + penugasan petugas sarpras + unit sekolah.
     *
     * @return array<int, string>
     */
    protected function accessibleWorkUnitIds(?object $user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }

        $ids = DB::table('gtk_work_unit')
            ->where('user_id', $user->id)
            ->pluck('work_unit_id')
            ->all();

        $ids = array_merge($ids, WorkUnitSarprasOfficer::query()
            ->active()
            ->where('user_id', $user->id)
            ->pluck('work_unit_id')
            ->all());

        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId) {
            $workUnitId = School::query()->whereKey($schoolId)->value('work_unit_id');
            if ($workUnitId) {
                $ids[] = $workUnitId;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Scope query ke work unit user. Pengelola pusat melihat semua.
     */
    protected function scopeToAccessibleWorkUnits($query, Request $request, string $column = 'work_unit_id')
    {
        if ($this->isCentralSarpras() || $this->canViewAll($request)) {
            return $query;
        }

        $workUnitIds = $this->accessibleWorkUnitIds();
        if ($workUnitIds !== []) {
            return $query->whereIn($column, $workUnitIds);
        }

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function isUnitSarprasOfficer(?object $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        return WorkUnitSarprasOfficer::query()
            ->active()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Ruang yang menjadi tanggung jawab wali kelas / wali kamar.
     *
     * @return array<int, string>
     */
    protected function responsibleRoomIds(?object $user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }

        $studyGroupIds = StudyGroup::query()
            ->where('homeroom_teacher_id', $user->id)
            ->pluck('id')
            ->all();

        $dormRoomIds = RoomSupervisor::query()
            ->active()
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->all();

        return AssetRoom::query()
            ->where(function ($query) use ($user, $studyGroupIds, $dormRoomIds) {
                $query->where('responsible_user_id', $user->id);

                if ($studyGroupIds !== []) {
                    $query->orWhereIn('study_group_id', $studyGroupIds);
                }
                if ($dormRoomIds !== []) {
                    $query->orWhereIn('dormitory_room_id', $dormRoomIds);
                }
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Scope aset/ruang sesuai tanggung jawab:
     * - URT pusat: semua
     * - Petugas Sarpras unit: seluruh unit
     * - Wali kelas/kamar: hanya ruang tanggung jawabnya
     */
    protected function scopeToResponsibility($query, Request $request, string $roomColumn = 'room_id')
    {
        if ($this->isCentralSarpras() || $this->canViewAll($request)) {
            return $query;
        }

        if ($this->isUnitSarprasOfficer()) {
            return $this->scopeToAccessibleWorkUnits($query, $request);
        }

        $roomIds = $this->responsibleRoomIds();
        if ($roomIds !== []) {
            return $query->whereIn($roomColumn, $roomIds);
        }

        return $query->whereRaw('1 = 0');
    }

    protected function authorizeResponsibleRoom(?string $roomId, Request $request): void
    {
        if ($this->isCentralSarpras() || $this->isUnitSarprasOfficer()) {
            return;
        }

        abort_unless($roomId && in_array($roomId, $this->responsibleRoomIds(), true), 403, 'Ruang ini bukan tanggung jawab Anda.');
    }

    protected function authorizeWorkUnitAccess(?string $workUnitId, Request $request): void
    {
        if ($this->isCentralSarpras() || $this->canViewAll($request)) {
            return;
        }

        if ($workUnitId && in_array($workUnitId, $this->accessibleWorkUnitIds(), true)) {
            return;
        }

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $workUnitId) {
            $schoolWorkUnitId = School::query()->whereKey($schoolId)->value('work_unit_id');
            if ($schoolWorkUnitId === $workUnitId) {
                return;
            }
        }

        abort(403, 'Anda tidak memiliki akses ke satuan kerja ini.');
    }

    /**
     * Check if resource is accessible by current user via school-scope.
     * Returns true if user can view all, or resource's schoolId matches request scope.
     */
    protected function canAccess(Request $request, object $resource, string $schoolField = 'school_id'): bool
    {
        if ($this->canViewAll($request) || $this->isCentralSarpras()) {
            return true;
        }

        if (! empty($resource->work_unit_id)
            && in_array($resource->work_unit_id, $this->accessibleWorkUnitIds(), true)) {
            return true;
        }

        $schoolId = $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            return false;
        }

        return ($resource->{$schoolField} ?? null) === $schoolId;
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
