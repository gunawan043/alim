<?php

namespace App\Models\Osipp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class OsippModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id = $model->id ?: (string) Str::uuid();
        });
    }
}
