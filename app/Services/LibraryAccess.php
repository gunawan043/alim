<?php

namespace App\Services;

use App\Models\LibraryStaff;
use App\Models\StudentOperatorAssignment;
use App\Models\LibraryUnit;
use App\Models\User;

class LibraryAccess
{
    public function isSuperAdmin(User $user): bool
    {
        return (bool) $user->is_system_admin || $user->hasRole('Super Admin');
    }

    public function hasLibraryRole(User $user): bool
    {
        return $user->hasRole('Perpustakaan') || $this->isSuperAdmin($user);
    }

    public function isStudentOperator(User $user): bool
    {
        return $user->hasRole('Operator Perpustakaan Santri');
    }

    public function unitsFor(User $user)
    {
        $query = LibraryUnit::query()->active();

        if ($this->isStudentOperator($user)) {
            $unitIds = StudentOperatorAssignment::query()
                ->where('user_id', $user->id)
                ->where('operator_type', 'library')
                ->where('is_active', true)
                ->whereNotNull('library_unit_id')
                ->pluck('library_unit_id');

            return $query->whereIn('id', $unitIds)->orderBy('name')->get();
        }

        if ($this->isSuperAdmin($user)) {
            return $query->orderBy('name')->get();
        }

        $assignedIds = LibraryStaff::query()
            ->where('user_id', $user->id)
            ->active()
            ->pluck('library_unit_id');

        if ($assignedIds->isNotEmpty()) {
            return $query->whereIn('id', $assignedIds)->orderBy('name')->get();
        }

        if ($this->hasLibraryRole($user)) {
            $schoolId = $user->employment?->school_id;

            return $query->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->get();
        }

        return $query->whereRaw('1 = 0')->get();
    }

    public function canAccessUnit(User $user, ?LibraryUnit $unit): bool
    {
        return $unit !== null && $this->unitsFor($user)->contains('id', $unit->id);
    }

    public function canManage(User $user): bool
    {
        return $this->hasLibraryRole($user);
    }

    public function canOperate(User $user, ?LibraryUnit $unit = null): bool
    {
        if ($this->canManage($user)) {
            return $unit === null || $this->canAccessUnit($user, $unit);
        }

        if (! $this->isStudentOperator($user)) {
            return false;
        }

        return StudentOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->where('operator_type', 'library')
            ->where('is_active', true)
            ->when($unit, fn ($q) => $q->where('library_unit_id', $unit->id))
            ->exists();
    }

    public function canManageUnit(User $user, ?LibraryUnit $unit): bool
    {
        return $this->canManage($user) && $this->canAccessUnit($user, $unit);
    }
}
