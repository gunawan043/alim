<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProcurementRequestItem extends Model
{
    use HasFactory;

    protected $table = 'procurement_request_items';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'procurement_request_id',
        'asset_category_id',
        'item_name',
        'specification',
        'quantity',
        'unit',
        'estimated_price_per_unit',
        'total_estimated_price',
        'purpose',
        'room_id',
        'actual_quantity_received',
        'actual_price_per_unit',
        'received_date',
        'notes',
    ];

    protected $casts = [
        'estimated_price_per_unit' => 'decimal:2',
        'total_estimated_price' => 'decimal:2',
        'actual_price_per_unit' => 'decimal:2',
        'received_date' => 'date',
    ];

    // RELATIONSHIPS
    public function procurementRequest()
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function room()
    {
        return $this->belongsTo(AssetRoom::class, 'room_id');
    }
}
