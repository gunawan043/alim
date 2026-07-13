<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'code', 'name', 'type', 'work_unit_id', 'building_id',
        'manager_user_id', 'phone', 'address', 'is_active',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function racks(): HasMany
    {
        return $this->hasMany(WarehouseRack::class, 'warehouse_id');
    }

    public function bins(): HasManyThrough
    {
        return $this->hasManyThrough(WarehouseBin::class, WarehouseRack::class, 'warehouse_id', 'rack_id');
    }
}