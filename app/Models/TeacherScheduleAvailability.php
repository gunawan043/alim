<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherScheduleAvailability extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'teacher_schedule_availabilities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'available_slots',
    ];

    protected $casts = [
        'available_slots' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
