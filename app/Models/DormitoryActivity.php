<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DormitoryActivity extends Model
{
    protected $table = 'dormitory_activities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'dormitory_id', 'schedule_id', 'academic_year_id', 'name', 'activity_date',
        'start_time', 'end_time', 'location', 'description', 'target_type',
        'target_study_group_ids', 'status', 'created_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'target_study_group_ids' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DormitoryActivitySchedule::class, 'schedule_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(DormitoryActivityAttendance::class, 'activity_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
