<?php

namespace App\Models\Concerns;

use App\Services\CurriculumProfileService;

trait HasCurriculumProfile
{
    protected static function bootHasCurriculumProfile(): void
    {
        static::creating(function ($model) {
            if (filled($model->curriculum_profile_id) || blank($model->school_id)) {
                return;
            }

            $model->curriculum_profile_id = app(CurriculumProfileService::class)->resolveId(
                $model->school_id,
                $model->academic_year_id ?? null
            );
        });
    }
}
