<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageDailyLog extends Model
{
    protected $table = 'language_daily_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'grade_level_id', 'study_group_id', 'log_date', 'log_type', 'theme', 'location', 'class_name',
        'mustami_id', 'participants_count', 'findings', 'follow_up', 'status', 'created_by',
    ];

    protected $casts = [
        'log_date' => 'date',
        'participants_count' => 'integer',
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

    public function mustami(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mustami_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->log_type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian';
    }
}
