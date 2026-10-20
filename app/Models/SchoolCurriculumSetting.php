<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SchoolCurriculumSetting extends Model
{
    protected $table = 'school_curriculum_settings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'curriculum_profile_id',
        'kokurikuler_type',
        'is_locked',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'curriculum_profile_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function forSchoolYear(?string $schoolId, ?string $academicYearId): ?self
    {
        if (! $schoolId || ! $academicYearId) {
            return null;
        }

        return static::with('profile')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->first();
    }
}
