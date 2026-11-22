<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DormitoryResident;
use App\Models\DormitoryStaffAssignment;
use App\Support\Reports\ReportScope;
use App\Support\Reports\ReportScopeResolver;
use Illuminate\Http\Request;

/**
 * Membatasi laporan ke cakupan unit pengguna.
 *
 * null  = tanpa batasan (global)
 * []    = tidak ada data yang boleh dibaca
 */
trait ResolvesReportScope
{
    private ?ReportScope $resolvedReportScope = null;

    protected function reportScope(Request $request): ReportScope
    {
        return $this->resolvedReportScope ??= app(ReportScopeResolver::class)->resolve($request);
    }

    /**
     * ID santri yang boleh dibaca. null bila tidak dibatasi.
     *
     * @return array<int, string>|null
     */
    protected function scopeStudentIds(ReportScope $scope): ?array
    {
        if ($scope->isGlobal()) {
            return null;
        }

        if ($scope->isEmpty()) {
            return [];
        }

        if ($scope->isSchool()) {
            return null;
        }

        return DormitoryResident::query()
            ->whereIn('dormitory_id', $scope->dormitoryIds)
            ->where('is_active', true)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * ID pengguna GTK yang boleh dibaca. null bila tidak dibatasi.
     *
     * @return array<int, string>|null
     */
    protected function scopeStaffIds(ReportScope $scope): ?array
    {
        if ($scope->isGlobal()) {
            return null;
        }

        if ($scope->isEmpty()) {
            return [];
        }

        if ($scope->isSchool()) {
            return null;
        }

        return DormitoryStaffAssignment::query()
            ->active()
            ->whereIn('dormitory_id', $scope->dormitoryIds)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Filter siap pakai untuk query santri.
     */
    protected function applyStudentScope($query, ReportScope $scope)
    {
        if ($scope->isSchool()) {
            return $query->where('school_id', $scope->schoolId);
        }

        if ($scope->isDormitory()) {
            return $query->whereIn('id', $this->scopeStudentIds($scope) ?? []);
        }

        if ($scope->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Filter siap pakai untuk query pengguna GTK.
     */
    protected function applyGtkScope($query, ReportScope $scope)
    {
        if ($scope->isSchool()) {
            return $query->whereHas('employment', fn ($e) => $e->where('school_id', $scope->schoolId));
        }

        if ($scope->isDormitory()) {
            return $query->whereIn('id', $this->scopeStaffIds($scope) ?? []);
        }

        if ($scope->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Filter siap pakai untuk query yang punya kolom school_id.
     */
    protected function applySchoolScope($query, ReportScope $scope, string $column = 'school_id')
    {
        if ($scope->isSchool()) {
            return $query->where($column, $scope->schoolId);
        }

        if ($scope->isDormitory()) {
            // Laporan per satuan pendidikan tidak berlaku untuk asrama.
            return $query->whereRaw('1 = 0');
        }

        if ($scope->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
