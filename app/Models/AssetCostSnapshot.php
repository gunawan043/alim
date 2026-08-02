<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssetCostSnapshot extends Model
{
    use HasFactory;

    protected $table = 'asset_cost_snapshots';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'asset_id',
        'purchase_cost',
        'repair_cost',
        'maintenance_cost',
        'sparepart_cost',
        'operational_cost',
        'total_cost',
        'computed_at',
    ];

    protected $casts = [
        'purchase_cost' => 'decimal:2',
        'repair_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'sparepart_cost' => 'decimal:2',
        'operational_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'computed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
