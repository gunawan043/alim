<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Domain extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'domains';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function positions(): HasMany
    {
        return $this->hasMany(StructuralPosition::class, 'domain_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'domain_role_permissions')
            ->using(DomainPermission::class)
            ->withTimestamps();
    }

    public function permissionNames(): array
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
