<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageMonthlyActivity extends Model
{
    protected $table = 'language_monthly_activities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'grade_level_id', 'study_group_id', 'period_month', 'title', 'theme', 'activity_type',
        'activity_date', 'time_start', 'time_end', 'place', 'executor_type',
        'executor_name', 'mc_name', 'committee', 'participants_count',
        'participation_notes', 'report', 'status', 'created_by',
    ];

    protected $casts = [
        'period_month' => 'date',
        'activity_date' => 'date',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivityTypeLabelAttribute(): string
    {
        return match ($this->activity_type) {
            'pidato' => 'Lomba Pidato',
            'permainan_kata' => 'Permainan Kata',
            'film' => 'Nonton Film',
            'drama' => 'Lomba Drama',
            default => ucfirst($this->activity_type ?? 'Lainnya'),
        };
    }
}
