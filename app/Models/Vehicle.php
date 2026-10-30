<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'plate_number',
        'name',
        'brand',
        'model',
        'type',
        'capacity',
        'status',
        'driver_id',
        'last_service_date',
        'next_service_date',
        'odometer',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
    ];

    public const STATUS_OPTIONS = ['tersedia', 'digunakan', 'perawatan', 'nonaktif'];

    public const TYPE_OPTIONS = ['mobil', 'motor', 'bus', 'truk', 'ambulance', 'lainnya'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(VehicleTrip::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(VehicleFuelLog::class);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(VehicleServiceLog::class);
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }
}
