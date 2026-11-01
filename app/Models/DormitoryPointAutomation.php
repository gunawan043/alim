<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryPointAutomation extends Model
{
    protected $table = 'dormitory_point_automations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'source_key',
        'label',
        'point_catalog_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    public function pointCatalog(): BelongsTo
    {
        return $this->belongsTo(DormitoryPointCatalog::class, 'point_catalog_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
