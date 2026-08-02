<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Remove primary key from guarded so Eloquent can fill it during create()
        $this->guarded = array_filter($this->guarded, fn ($col) => $col !== $this->primaryKey);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id) || ! Str::isUuid((string) $model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'guard_name',
        'level',
        'description',
        'id', // ensure id is fillable via fillable list when guarded has been cleared
    ];

    protected $casts = [
        'id' => 'string',
        'level' => 'integer',
    ];
}
