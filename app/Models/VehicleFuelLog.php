<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleFuelLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'vehicle_id',
        'fuel_date',
        'fuel_type',
        'liters',
        'cost',
        'odometer',
        'receipt_no',
        'recorded_by',
    ];

    protected $casts = [
        'fuel_date' => 'date',
        'liters' => 'float',
        'cost' => 'decimal:2',
        'odometer' => 'integer',
    ];

    public const TYPE_OPTIONS = ['bensin', 'solar', 'pertalite', 'pertamax'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
