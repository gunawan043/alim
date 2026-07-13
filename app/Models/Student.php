<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Traits\LogsDeletion;

class Student extends Model
{
    use LogsDeletion;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id', 'school_id',
        // Identitas
        'nisn', 'nis', 'nik', 'no_kk', 'name', 'gender',
        'birth_place', 'birth_date', 'religion', 'special_needs',
        // Alamat
        'address', 'rt', 'rw', 'hamlet',
        'village_code', 'district_code', 'city_code', 'province_code', 'postal_code',
        // Kontak
        'phone', 'mobile_phone', 'email',
        // Tempat tinggal
        'residence_type', 'transportation', 'distance_to_school', 'latitude', 'longitude',
        // Kesehatan
        'height', 'weight', 'head_circumference', 'sibling_count',
        // Ayah
        'father_name', 'father_birth_year', 'father_education', 'father_occupation',
        'father_income', 'father_nik',
        // Ibu
        'mother_name', 'mother_birth_year', 'mother_education', 'mother_occupation',
        'mother_income', 'mother_nik',
        // Wali
        'guardian_name', 'guardian_birth_year', 'guardian_education', 'guardian_occupation',
        'guardian_income', 'guardian_nik',
        // Pendaftaran
        'child_number', 'previous_school', 'entry_date', 'entry_grade_level',
        'skhun', 'ujian_national_number', 'certificate_number', 'birth_certificate_number',
        // PIP/KIP/KPS
        'is_kps_receiver', 'kps_number',
        'is_kip_receiver', 'kip_number', 'kip_name', 'kks_number',
        'is_pip_eligible', 'pip_reason',
        // Bank
        'bank_name', 'bank_account_number', 'bank_account_name',
        // Status
        'status', 'graduation_year', 'graduation_date',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'entry_date'       => 'date',
        'graduation_date'  => 'date',
        'father_birth_year'    => 'integer',
        'mother_birth_year'    => 'integer',
        'guardian_birth_year' => 'integer',
        'distance_to_school'  => 'decimal:2',
        'father_income'       => 'decimal:2',
        'mother_income'       => 'decimal:2',
        'guardian_income'     => 'decimal:2',
        'latitude'           => 'decimal:8',
        'longitude'          => 'decimal:8',
        'is_kps_receiver' => 'boolean',
        'is_kip_receiver' => 'boolean',
        'is_pip_eligible' => 'boolean',
    ];

    protected $appends = ['gender_text', 'status_text', 'special_needs_text'];

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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

    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class)->with('studyGroup');
    }

    public function violationPoints(): HasMany
    {
        return $this->hasMany(ViolationPoint::class);
    }

    public function mahroms(): HasMany
    {
        return $this->hasMany(StudentMahrom::class);
    }

    public function primaryMahrom()
    {
        return $this->hasOne(StudentMahrom::class)->where('is_primary', true);
    }

    public function currentClassHistory()
    {
        return $this->hasOne(StudentClassHistory::class)->latestOfMany();
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getGenderTextAttribute(): string
    {
        return $this->gender === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'active'       => 'Aktif',
            'inactive'     => 'Nonaktif',
            'graduate'     => 'Lulus',
            'dropped'      => 'Dropout',
            'transfer'     => 'Pindah',
            'transfer_out' => 'Pindah (Keluar)',
            'transfer_in'  => 'Pindah (Masuk)',
            default        => ucfirst($this->status ?? ''),
        };
    }

    public function getSpecialNeedsTextAttribute(): string
    {
        return match ($this->special_needs) {
            'tidak'     => 'Tidak ada',
            'fisik'     => 'Fisik',
            'intelektual' => 'Intelektual',
            'mental'    => 'Mental',
            'sosial'    => 'Sosial',
            default     => ucfirst($this->special_needs ?? ''),
        };
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->hamlet,
            $this->village?->name,
            $this->district?->name,
            $this->city?->name,
            $this->province?->name,
            $this->postal_code ? "{$this->postal_code}" : null,
        ]);
        return implode(', ', $parts);
    }

    public function getPhotoUrlAttribute(): string
    {
        // photo_path stored relative to storage/app/public/students/photos/
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        // Fallback initials avatar
        $color = $this->gender === 'P' ? 'pink' : 'primary';
        return null; // handled in view
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeBySchool($q, $sid) { return $q->where('school_id', $sid); }

    // ── Achievements ────────────────────────────────────────────

    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    /**
     * Unified timeline events across all modules (boarding, academic, clinic, etc).
     */
    public function timelineEvents(): HasMany
    {
        return $this->hasMany(BoardingTimelineEvent::class)->orderByDesc('event_at');
    }

    /**
     * Convenience accessor for the unified timeline service.
     */
    public function unifiedTimeline(): \App\Services\UnifiedStudentTimelineService
    {
        return new \App\Services\UnifiedStudentTimelineService($this);
    }
}
