<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GradeLevelSubject extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'academic_year_id',
        'grade_level_id',
        'subject_id',
        'allocation_hours',
        'track',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allocation_hours' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * ARSIP PERIODE: baris versi tahun ajaran terpilih + baris lama (NULL)
     * sebagai fallback. Baris versi periode selalu diprioritaskan lewat
     * orderByAcademicYearFirst() agar tidak terjadi duplikasi tampilan.
     */
    public function scopeForAcademicYear(Builder $query, ?string $academicYearId): Builder
    {
        if (! $academicYearId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId)
                ->orWhereNull('academic_year_id');
        });
    }

    /**
     * Urutkan baris versi periode lebih dulu; baris lama (NULL) menjadi fallback.
     */
    public function scopeOrderByAcademicYearFirst(Builder $query): Builder
    {
        return $query->orderByRaw('academic_year_id IS NULL ASC');
    }

    /**
     * Kunci unik logis struktur: jenjang + mapel + jalur (track).
     */
    public function structureKey(): string
    {
        return $this->grade_level_id.'|'.$this->subject_id.'|'.($this->track ?? '');
    }
}
