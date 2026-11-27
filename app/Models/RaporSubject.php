<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Mapel yang dicetak di rapor untuk satu TINGKAT pada TA + semester tertentu.
 *
 * Rapor satu tingkat memakai template yang sama: daftar mapel, urutan, dan
 * KKTP tidak boleh berbeda antar rombel. Baris di sini hanya menandai mapel
 * mana yang ikut dicetak; urutan & pondok/dinas dibaca dari
 * grade_level_subjects agar cukup diatur sekali di Peta Kurikulum.
 */
class RaporSubject extends Model
{
    protected $table = 'rapor_subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'grade_level_id',
        'semester',
        'subject_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Daftar mapel rapor yang dipilih untuk satu tingkat.
     */
    public function scopeForScope(Builder $query, ?string $schoolId, ?string $academicYearId, ?string $semester, ?string $gradeLevelId): Builder
    {
        if (! $academicYearId || ! $semester || ! $gradeLevelId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->when($schoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('grade_level_id', $gradeLevelId);
    }
}
