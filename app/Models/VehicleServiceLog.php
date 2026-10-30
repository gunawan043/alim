<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleServiceLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'school_id',
        'vehicle_id',
        'service_type',
        'service_date',
        'odometer',
        'cost',
        'vendor_name',
        'items_replaced',
        'next_service_date',
        'status',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'odometer' => 'integer',
    ];

    public const TYPE_OPTIONS = ['rutin', 'perbaikan', 'ganti_oli', 'tune_up', 'lainnya'];

    public const STATUS_OPTIONS = ['terjadwal', 'berjalan', 'selesai'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
