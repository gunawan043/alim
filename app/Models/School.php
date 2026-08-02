<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class School extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id',
        'school_code',
        'npsn',
        'nss',
        'name',
        'address',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
        'postal_code',
        'phone',
        'email',
        'website',
        'school_level',
        'school_status',
        'accreditation',
        'accreditation_year',
        'principal_name',
        'principal_nip',
        'principal_nupy',
        'principal_user_id',
        'operational_hours',
        'established_date',
        'established_decree',
        'land_area',
        'building_area',
        'is_active',
        'logo_path',
        'kop_path',
        'ttd_ksp_path',
        'stamp_path',
        'kop_nama',
        'kop_alamat',
        'kop_telp',
        'kop_email',
        'kop_website',
        'kop_npsn',
        'kopsis_active',
        'bank_name',
        'bank_cabang',
        'bank_rekening',
        'bank_an',
        'npwp',
    ];

    protected $casts = [
        'established_date' => 'date',
        'accreditation_year' => 'integer',
        'land_area' => 'decimal:2',
        'building_area' => 'decimal:2',
        'is_active' => 'boolean',
        'kopsis_active' => 'boolean',
    ];

    protected $appends = ['level_text', 'status_text'];

    // ── Relationships ──────────────────────────────────────────────

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function principalUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'principal_user_id');
    }

    public function province()
    {
        return $this->belongsTo(\App\Models\Province::class, 'province_code', 'code');
    }

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(\App\Models\District::class, 'district_code', 'code');
    }

    public function village()
    {
        return $this->belongsTo(\App\Models\Village::class, 'village_code', 'code');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getLevelTextAttribute(): string
    {
        return match ($this->school_level) {
            'sd' => 'Sekolah Dasar (SD)',
            'smp' => 'Sekolah Menengah Pertama (SMP)',
            'sma' => 'Sekolah Menengah Atas (SMA)',
            'smk' => 'Sekolah Menengah Kejuruan (SMK)',
            default => ucfirst($this->school_level ?? ''),
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->school_status) {
            'negeri' => 'Negeri',
            'swasta' => 'Swasta',
            default => ucfirst($this->school_status ?? ''),
        };
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village?->name,
            $this->district?->name,
            $this->city?->name,
            $this->province?->name,
            $this->postal_code ? "{$this->postal_code}" : null,
        ]);

        return implode(', ', $parts);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return asset('build/images/avatar-1.jpg');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeLevel($q, $l)
    {
        return $q->where('school_level', $l);
    }
}
