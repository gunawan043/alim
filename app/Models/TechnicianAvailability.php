<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianAvailability extends Model
{
    protected $table = 'technician_availabilities';

    protected $fillable = [
        'user_id',
        'status',
        'current_active_orders',
        'max_concurrent_orders',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        return $this->current_active_orders < $this->max_concurrent_orders;
    }

    public function workloadRatio(): float
    {
        if ($this->max_concurrent_orders === 0) {
            return 1.0;
        }

        return round($this->current_active_orders / $this->max_concurrent_orders, 3);
    }
}
