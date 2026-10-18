<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzLessonPlanItem extends Model
{
    protected $table = 'tahfidz_lesson_plan_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'plan_id', 'week', 'section', 'learning_day', 'week_target',
        'daily_target', 'teacher_activity', 'student_activity',
        'duration_minutes', 'notes', 'sort_order',
    ];

    protected $casts = [
        'week' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TahfidzLessonPlan::class, 'plan_id');
    }
}
