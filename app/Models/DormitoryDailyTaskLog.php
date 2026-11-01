<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryDailyTaskLog extends Model
{
    protected $table = 'dormitory_daily_task_logs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'task_id',
        'dormitory_id',
        'academic_year_id',
        'task_date',
        'shift',
        'task_name',
        'is_done',
        'notes',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'task_date' => 'date',
        'is_done' => 'boolean',
        'checked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(DormitoryDailyTask::class, 'task_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
