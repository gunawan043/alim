<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomBooking extends Model
{
    use HasFactory;

    protected $table = 'room_bookings';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'room_id',
        'work_unit_id',
        'school_id',
        'booked_by',
        'purpose',
        'event_name',
        'participants_count',
        'booking_date',
        'start_time',
        'end_time',
        'setup_time',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'actual_start_time',
        'actual_end_time',
        'condition_before',
        'condition_after',
        'related_agenda_id',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_OPTIONS = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];

    // RELATIONSHIPS
    public function room()
    {
        return $this->belongsTo(AssetRoom::class, 'room_id');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('booking_date', '>=', now())
            ->whereIn('status', ['approved']);
    }
}
