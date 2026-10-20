<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CurriculumProfile extends Model
{
    protected $table = 'curriculum_profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'code',
        'name',
        'authority',
        'version',
        'effective_from',
        'effective_until',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(SchoolCurriculumSetting::class, 'curriculum_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function configValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    public function label(string $key, ?string $default = null): string
    {
        return (string) $this->configValue('labels.'.$key, $default ?? $key);
    }

    public function values(): array
    {
        return (array) $this->configValue('values', []);
    }

    public function isKemenag(): bool
    {
        return $this->authority === 'kemenag';
    }

    public function isDinas(): bool
    {
        return $this->authority === 'dinas';
    }
}
