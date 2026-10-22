<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LanguageVocabularyPeriod extends Model
{
    protected $table = 'language_vocabulary_periods';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'academic_year_id', 'grade_level_id', 'study_group_id', 'title', 'period_start', 'period_end',
        'location_school', 'location_dorm', 'min_words', 'notes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'min_words' => 'integer',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vocabularies(): HasMany
    {
        return $this->hasMany(LanguageVocabulary::class, 'period_id')->orderBy('position');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LanguageMufradatSchedule::class, 'period_id')->orderBy('day_of_week')->orderBy('time_start');
    }

    public function mustamis(): HasMany
    {
        return $this->hasMany(LanguageMustamiAssignment::class, 'period_id');
    }

    public function setorans(): HasMany
    {
        return $this->hasMany(LanguageMufradatSetoran::class, 'period_id');
    }
}
