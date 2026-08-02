<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait UuidTrait
{
    protected static function bootUuidTrait()
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()}) && in_array('uuid', $model->getFillable())) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (in_array('uuid', $model->getFillable()) && empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function scopeWhereUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}
