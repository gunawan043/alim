<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentAchievement extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'school_id',
        'academic_year_id',
        'achievement_type',
        'hafalan_category',
        'event_name',
        'organizer',
        'level',
        'position',
        'position_detail',
        'event_date',
        'event_location',
        'coach_id',
        'certificate_path',
        'photo_path',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());

        static::deleted(function ($model) {
            if ($model->certificate_path && Storage::exists($model->certificate_path)) {
                Storage::delete($model->certificate_path);
            }
            if ($model->photo_path && Storage::exists($model->photo_path)) {
                Storage::delete($model->photo_path);
            }
        });
    }

    // ─── Attribute helpers ────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->achievement_type) {
            'akademik' => 'Prestasi Akademik',
            'non_akademik' => 'Non Akademik',
            'hafalan' => match ($this->hafalan_category) {
                'quran' => 'Hafalan Qur\'an',
                'hadits' => 'Hafalan Hadits',
                default => 'Hafalan',
            },
            'olahraga' => 'Olahraga',
            'seni' => 'Seni',
            'sains' => 'Sains',
            'lainnya' => 'Lainnya',
            default => $this->achievement_type,
        };
    }

    public function getLevelLabelAttribute(): string
    {
        return match ($this->level) {
            'internal' => 'Internal',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten/Kota',
            'provinsi' => 'Provinsi',
            'nasional' => 'Nasional',
            'internasional' => 'Internasional',
            default => $this->level,
        };
    }

    public function getPositionLabelAttribute(): string
    {
        return match ($this->position) {
            'juara_1' => 'Juara 1',
            'juara_2' => 'Juara 2',
            'juara_3' => 'Juara 3',
            'harapan_1' => 'Harapan 1',
            'harapan_2' => 'Harapan 2',
            'harapan_3' => 'Harapan 3',
            'peserta' => 'Peserta',
            'mumtaz_murtafi' => 'Mumtaz Murtafi',
            'lainnya' => $this->position_detail ?? 'Lainnya',
            default => $this->position,
        };
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return $this->certificate_path ? asset('storage/'.$this->certificate_path) : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    // ─── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
