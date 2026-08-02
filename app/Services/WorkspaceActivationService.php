<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CoordinatorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\StructuralAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Resolve which workspaces a user is currently active in, based on
 * assignment tables (homeroom, coordinator, structural) rather than
 * the static role-based permission snapshot.
 */
class WorkspaceActivationService
{
    /**
     * Return the set of active workspace keys for a user.
     *
     * @return array{wali_kelas: bool, koordinator_rumpun: bool, waka_kurikulum: bool, structural: bool, primary: ?string}
     */
    public function resolve(User $user): array
    {
        $today = Carbon::today()->toDateString();

        $hasHomeroom = HomeroomAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->exists();

        $hasCoordinator = CoordinatorAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->exists();

        $hasStructural = StructuralAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->exists();

        // Waka = structural positions with hierarchy_level in mid-range (3..8).
        $hasWaka = $hasStructural && StructuralAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('position', fn ($q) => $q->whereBetween('hierarchy_level', [3, 8]))
            ->exists();

        return [
            'wali_kelas' => $hasHomeroom,
            'koordinator_rumpun' => $hasCoordinator,
            'waka_kurikulum' => $hasWaka,
            'structural' => $hasStructural,
            'primary' => $this->resolvePrimary([
                'wali_kelas' => $hasHomeroom,
                'koordinator_rumpun' => $hasCoordinator,
                'waka_kurikulum' => $hasWaka,
                'structural' => $hasStructural,
            ]),
        ];
    }

    /**
     * @param  array<string, bool>  $flags
     */
    private function resolvePrimary(array $flags): ?string
    {
        foreach (['waka_kurikulum', 'koordinator_rumpun', 'structural', 'wali_kelas'] as $key) {
            if ($flags[$key] ?? false) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Cache-bypassing helper for sidebar render.
     */
    public function forUser(?User $user): array
    {
        if (! $user) {
            return [
                'wali_kelas' => false,
                'koordinator_rumpun' => false,
                'waka_kurikulum' => false,
                'structural' => false,
                'primary' => null,
            ];
        }

        return $this->resolve($user);
    }
}
