<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CurriculumProfileFilterService
{
    public const SCOPES = ['active', 'all', 'legacy'];

    public function scope(Request $request): string
    {
        $scope = (string) $request->input('profile_scope', 'active');

        return in_array($scope, self::SCOPES, true) ? $scope : 'active';
    }

    public function apply(Builder $query, ?string $profileId, string $scope): Builder
    {
        if (! $profileId || $scope === 'all') {
            return $query;
        }

        if ($scope === 'legacy') {
            return $query->whereNull('curriculum_profile_id');
        }

        // Profil aktif + dokumen lama yang belum ditandai.
        return $query->where(function (Builder $q) use ($profileId) {
            $q->where('curriculum_profile_id', $profileId)
                ->orWhereNull('curriculum_profile_id');
        });
    }
}
