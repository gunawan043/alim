<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalTaskTypeWorkUnitHour extends Model
{
    use HasUuids;

    protected $table = 'additional_task_type_work_unit_hours';

    protected $fillable = [
        'work_unit_id',
        'additional_task_type_id',
        'weekly_hours',
    ];

    protected $casts = [
        'weekly_hours' => 'decimal:2',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'work_unit_id');
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(AdditionalTaskType::class, 'additional_task_type_id');
    }
}
