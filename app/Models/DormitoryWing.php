<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DormitoryWing extends Model
{
    protected $table = 'dormitory_wings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'sarpras_building_id',
        'dormitory_id',
        'code',
        'name',
        'floor',
        'gender',
        'capacity',
        'total_rooms',
        'supervisor_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'floor' => 'integer',
        'capacity' => 'integer',
        'total_rooms' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function sarprasBuilding(): BelongsTo
    {
        return $this->belongsTo(SarprasBuilding::class, 'sarpras_building_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(DormitoryRoom::class, 'wing_id');
    }

    /**
     * Display name composed from sarpras building + floor.
     * Example: "Blok Abu Bakar Lantai 2"
     */
    public function getDisplayNameAttribute(): string
    {
        $buildingName = $this->sarprasBuilding?->name ?? $this->name;
        $floor = $this->floor ?? '';
        if (is_numeric($floor) && $floor > 0) {
            return "{$buildingName} Lantai {$floor}";
        }

        return $buildingName;
    }
}
