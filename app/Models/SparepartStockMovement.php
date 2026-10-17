<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparepartStockMovement extends Model
{
    use HasFactory;

    protected $table = 'sparepart_stock_movements';

    protected $fillable = [
        'movement_code', 'sparepart_id', 'movement_type', 'quantity',
        'balance_after', 'unit_cost', 'total_cost',
        'from_warehouse_id', 'to_warehouse_id',
        'from_bin_id', 'to_bin_id',
        'reference_type', 'reference_id',
        'performed_by', 'occurred_at',
        'reason', 'metadata', 'is_immutable',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'occurred_at' => 'datetime',
        'is_immutable' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (! $model->movement_code) {
                $prefix = 'MOVE-'.now()->format('Ymd').'-';
                $model->movement_code = $prefix.strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
