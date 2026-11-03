<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityVehicleLog extends SecurityModel
{
    protected $table = 'security_vehicle_logs';

    public const STATUS_INSIDE = 'di_dalam';

    public const STATUS_OUT = 'keluar';

    public const VEHICLE_TYPES = [
        'mobil' => 'Mobil',
        'motor' => 'Motor',
        'truk' => 'Truk',
        'bus' => 'Bus',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'post_id', 'code', 'plate_number', 'vehicle_type', 'driver_name',
        'driver_phone', 'purpose', 'passenger_count', 'check_in_at',
        'check_out_at', 'status', 'notes', 'recorded_by', 'checked_out_by',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'passenger_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeInside(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INSIDE);
    }
}
