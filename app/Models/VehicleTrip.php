<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTrip extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'vehicle_id',
        'driver_id',
        'destination',
        'purpose',
        'passengers',
        'departure_at',
        'return_at',
        'odometer_start',
        'odometer_end',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'return_at' => 'datetime',
        'passengers' => 'integer',
        'odometer_start' => 'integer',
        'odometer_end' => 'integer',
    ];

    public const STATUS_OPTIONS = ['dijadwalkan', 'berangkat', 'selesai', 'batal'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
