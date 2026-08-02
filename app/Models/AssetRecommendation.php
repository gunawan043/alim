<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetRecommendation extends Model
{
    use HasFactory;

    protected $table = 'asset_recommendations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    public const RECOMMENDATIONS = ['GOOD', 'MONITOR', 'REPAIR', 'REPLACE', 'CRITICAL'];

    protected $fillable = [
        'asset_id',
        'recommendation',
        'score',
        'repair_cost_ratio',
        'estimated_repair_cost',
        'replacement_value',
        'age_years',
        'damage_count',
        'downtime_minutes',
        'availability_pct',
        'criticality',
        'health_score',
        'factor_breakdown',
        'rationale',
        'computed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'repair_cost_ratio' => 'decimal:3',
        'estimated_repair_cost' => 'decimal:2',
        'replacement_value' => 'decimal:2',
        'age_years' => 'integer',
        'damage_count' => 'integer',
        'downtime_minutes' => 'integer',
        'availability_pct' => 'integer',
        'criticality' => 'integer',
        'health_score' => 'integer',
        'factor_breakdown' => 'array',
        'rationale' => 'array',
        'computed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
