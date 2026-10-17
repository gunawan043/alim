<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DormitoryActivitySchedule extends Model
{
    protected $table = 'dormitory_activity_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'dormitory_id', 'name', 'recurrence', 'days_of_week', 'start_date', 'end_date',
        'start_time', 'end_time', 'location', 'description',
        'target_type', 'target_study_group_ids', 'is_active', 'created_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'target_study_group_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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

    public function activities(): HasMany
    {
        return $this->hasMany(DormitoryActivity::class, 'schedule_id');
    }

    public function runsOn(\Illuminate\Support\Carbon $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($date->lt($this->start_date) || ($this->end_date && $date->gt($this->end_date))) {
            return false;
        }

        if ($this->recurrence === 'daily') {
            return true;
        }

        return in_array($date->dayOfWeekIso, array_map('intval', (array) $this->days_of_week), true);
    }
}
