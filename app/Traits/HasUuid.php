<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            // Jika id belum di-set dan kolom id ada di model
            if (empty($model->id) && in_array('id', $model->getFillable())) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType()
    {
        return 'string';
    }
}