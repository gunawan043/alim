<?php

namespace App\Services;

use App\Models\CurriculumProfile;
use App\Models\SchoolCurriculumSetting;
use Illuminate\Support\Facades\Schema;

class CurriculumProfileService
{
    private array $cache = [];

    public function resolve(?string $schoolId, ?string $academicYearId): ?CurriculumProfile
    {
        if (! Schema::hasTable('curriculum_profiles')) {
            return null;
        }

        $cacheKey = ($schoolId ?? '-').'|'.($academicYearId ?? '-');
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $profile = null;

        if ($schoolId && $academicYearId && Schema::hasTable('school_curriculum_settings')) {
            $profile = SchoolCurriculumSetting::query()
                ->with('profile')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->first()
                ?->profile;
        }

        if (! $profile) {
            $profile = $this->defaultProfile();
        }

        return $this->cache[$cacheKey] = $profile;
    }

    public function resolveId(?string $schoolId, ?string $academicYearId): ?string
    {
        return $this->resolve($schoolId, $academicYearId)?->id;
    }

    public function defaultProfile(): ?CurriculumProfile
    {
        if (! Schema::hasTable('curriculum_profiles')) {
            return null;
        }

        $code = config('curriculum.default_profile', 'merdeka_dinas');

        return CurriculumProfile::query()->active()->where('code', $code)->first()
            ?? CurriculumProfile::query()->active()->orderBy('code')->first();
    }

    public function setting(?string $schoolId, ?string $academicYearId): ?SchoolCurriculumSetting
    {
        return SchoolCurriculumSetting::forSchoolYear($schoolId, $academicYearId);
    }

    public function labels(?CurriculumProfile $profile): array
    {
        $defaults = config('curriculum.default_labels', []);

        if (! $profile) {
            return $defaults;
        }

        return array_merge($defaults, (array) $profile->configValue('labels', []));
    }

    public function kbcValues(?CurriculumProfile $profile): array
    {
        if (! $profile || ! $profile->isKemenag()) {
            return [];
        }

        return (array) $profile->configValue('values', []);
    }
}
