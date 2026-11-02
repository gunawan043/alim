<?php

namespace App\Models;

use App\Observers\DormitoryRoomObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

        // Setiap kamar asrama otomatis menjadi ruang di Sarpras.
        static::observe(DormitoryRoomObserver::class);
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

    /**
     * Wali kamar yang sedang aktif.
     *
     * Tidak memakai latestOfMany(): subquery agregatnya mengabaikan filter
     * status/tanggal, sehingga bila ada penugasan lama dengan start_date sama
     * relasi bisa mengembalikan null. Cukup urutkan, hasOne mengambil yang
     * pertama.
     */
    public function activeSupervisor(): HasOne
    {
        return $this->hasOne(RoomSupervisor::class, 'room_id')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->whereDate('start_date', '<=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('created_at');
    }
}
