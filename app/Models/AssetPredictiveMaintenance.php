<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetPredictiveMaintenance extends Model
{
    use HasFactory;

    protected $table = 'asset_predictive_maintenance';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    public const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    public const STATUSES = ['scheduled', 'overdue', 'completed', 'cancelled'];

    protected $fillable = [
        'asset_id',
        'status',
        'maintenance_due_date',
        'estimated_cost',
        'priority',
        'confidence_score',
        'input_metrics',
        'predicted_actions',
        'notes',
        'computed_at',
    ];

    protected $casts = [
        'maintenance_due_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'confidence_score' => 'integer',
        'input_metrics' => 'array',
        'predicted_actions' => 'array',
        'computed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
