<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseRack extends Model
{
    use HasFactory;

    protected $table = 'warehouse_racks';

    protected $fillable = ['warehouse_id', 'code', 'name', 'description', 'level'];

    protected $casts = ['level' => 'integer'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class, 'rack_id');
    }
}