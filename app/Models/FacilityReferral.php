<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FacilityReferral extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'facility_name',
        'facility_type',
        'address',
        'phone',
        'email',
        'distance_km',
        'is_available_24h',
        'services',
        'operating_hours',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'is_available_24h' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getFacilityTypeTextAttribute(): string
    {
        return match ($this->facility_type) {
            'puskesmas' => 'Puskesmas',
            'rumah_sakit' => 'Rumah Sakit',
            'klinik' => 'Klinik',
            'dokter_praktik' => 'Dokter Praktik',
            'rs_psychologist' => 'RS Psychologists',
            'posyandu' => 'Posyandu',
            default => $this->facility_type,
        };
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeByType($q, $type)
    {
        return $q->where('facility_type', $type);
    }
}
