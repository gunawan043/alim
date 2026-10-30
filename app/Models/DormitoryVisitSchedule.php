<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DormitoryVisitSchedule extends Model
{
    protected $table = 'dormitory_visit_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'gender',
        'week_of_month',
        'day_of_week',
        'start_time',
        'end_time',
        'label',
        'is_active',
    ];

    protected $casts = [
        'week_of_month' => 'integer',
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    public function dormitory()
    {
        return $this->belongsTo(Dormitory::class);
    }
}
