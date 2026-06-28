<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ClassScheduleSlot extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) \Illuminate\Support\Str::uuid());
    }

    protected $fillable = [
        'schedule_id',
        'study_group_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_index',
        'room',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function isDay(string $day): bool
    {
        return $this->day_of_week === Carbon::parse($day.' 00:00:00')->dayOfWeek;
    }
}
