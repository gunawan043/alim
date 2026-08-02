<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UksBed extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_beds';

    protected $fillable = [
        'dormitory_id',
        'gender',
        'building_or_room',
        'building',
        'room',
        'section',
        'bed_number',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'dormitory_id' => 'string',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function dormitory()
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UksBedAssignment::class, 'bed_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', 'tersedia');
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    // ── Accessors ───────────────────────────────────────────────

    public function currentAssignment()
    {
        return $this->assignments()
            ->where('status', 'assigned')
            ->orderByDesc('assigned_at')
            ->first();
    }

    /**
     * Returns a display label like "Ruang A - A-01".
     */
    protected function identifier(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = array_filter([
                $this->section ?? $this->building_or_room,
                $this->bed_number,
            ]);

            return implode(' - ', $parts) ?: $this->bed_number;
        });
    }

    /**
     * Is this bed currently occupied?
     */
    protected function isOccupied(): Attribute
    {
        return Attribute::get(fn (): bool => $this->currentAssignment !== null);
    }
}
