<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DormitoryRoom extends Model
{
    protected $table = 'dormitory_rooms';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'wing_id',
        'code',
        'name',
        'floor',
        'gender',
        'capacity',
        'room_type',
        'facility_notes',
        'is_active',
    ];

    protected $casts = [
        'floor' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function wing(): BelongsTo
    {
        return $this->belongsTo(DormitoryWing::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(DormitoryResident::class, 'room_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(RoomSupervisor::class, 'room_id');
    }

    public function activeSupervisor()
    {
        return $this->hasOne(RoomSupervisor::class, 'room_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('start_date', '<=', now()->toDateString())
            ->latestOfMany('start_date');
    }
}
