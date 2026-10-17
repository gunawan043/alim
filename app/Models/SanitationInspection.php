<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SanitationInspection extends Model
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
        'academic_year_id',
        'inspection_date',
        'inspected_by',
        'location_type',
        'location_id',
        'score',
        'findings',
        'photo_path',
        'recommendations',
        'follow_up_deadline',
        'follow_up_completed_at',
        'is_passed',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'follow_up_deadline' => 'date',
        'follow_up_completed_at' => 'datetime',
        'score' => 'integer',
        'is_passed' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getLocationTypeTextAttribute(): string
    {
        return match ($this->location_type) {
            'asrama' => 'Asrama',
            'kantin' => 'Kantin',
            'toilet' => 'Toilet',
            'tempat_sampah' => 'Tempat Sampah',
            'sumber_air' => 'Sumber Air',
            'ruang_kelas' => 'Ruang Kelas',
            'halaman' => 'Halaman',
            'dapur' => 'Dapur',
            default => $this->location_type,
        };
    }

    public function getScoreLabelAttribute(): string
    {
        return match (true) {
            $this->score >= 80 => 'Baik',
            $this->score >= 60 => 'Cukup',
            $this->score >= 40 => 'Kurang',
            default => 'Buruk',
        };
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopePendingFollowUp($q)
    {
        return $q->whereNotNull('follow_up_deadline')
            ->whereNull('follow_up_completed_at')
            ->where('follow_up_deadline', '<', now());
    }

    public function scopeByAcademicYear($q, $yearId)
    {
        return $q->where('academic_year_id', $yearId);
    }
}
