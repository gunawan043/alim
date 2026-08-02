<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sparepart extends Model
{
    use HasFactory;

    protected $table = 'spareparts';

    protected $fillable = [
        'part_number', 'name', 'slug', 'description', 'category_id',
        'unit_id', 'primary_vendor_id', 'warehouse_id', 'bin_id',
        'barcode', 'qr_path', 'stock', 'min_stock', 'max_stock',
        'reorder_point', 'reorder_quantity', 'unit_price', 'average_cost',
        'last_purchase_price', 'currency', 'lead_time_days',
        'weight_kg', 'dimensions', 'brand', 'manufacturer',
        'manufacturer_part', 'is_hazardous', 'is_consumable',
        'is_active', 'lifetime_days',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'is_hazardous' => 'boolean',
        'is_consumable' => 'boolean',
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'lifetime_days' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SparepartCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function primaryVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'primary_vendor_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bin(): BelongsTo
    {
        return $this->belongsTo(WarehouseBin::class, 'bin_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(SparepartStockMovement::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(SparepartReservation::class);
    }

    public function getAvailableStockAttribute(): float
    {
        return (float) $this->stock - $this->reservedQuantity();
    }

    public function reservedQuantity(): float
    {
        return (float) $this->reservations()
            ->where('status', 'active')
            ->whereColumn('consumed_quantity', '<', 'quantity')
            ->sum('quantity') - sum('consumed_quantity');
    }

    public function needsReorder(): bool
    {
        return $this->available <= $this->reorder_point;
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock <= $this->min_stock;
    }

    public function isDeadStock(): bool
    {
        if ($this->stock_movements()->where('occurred_at', '>=', now()->subMonths(6))->doesntExist()) {
            return true;
        }
        if ($this->lifetime_days && now()->diffInDays($this->stock_movements()->latest()->first()?->occurred_at ?? now()) > 365) {
            return true;
        }

        return false;
    }
}
