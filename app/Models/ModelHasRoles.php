<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class ModelHasRoles extends Pivot
{
    protected $table = 'model_has_roles';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'role_id',
        'model_type',
        'model_uuid',
    ];

    public $timestamps = false;
}
