<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TahfidzSchedule extends Model
{
    protected $table = 'tahfidz_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'tahfidz_group_id', 'academic_year_id', 'day_of_week', 'time_start', 'time_end',
        'room', 'schedule_type', 'is_active', 'effective_from', 'effective_until', 'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(TahfidzGroup::class, 'tahfidz_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getDayNameAttribute(): string
    {
        return match ($this->day_of_week) {
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu', default => '—',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->schedule_type) {
            'setoran' => 'Setoran',
            'murajaah' => 'Murajaah',
            'tasmi' => 'Tasmi’',
            'evaluasi' => 'Evaluasi',
            'tikror' => 'Tikror',
            default => ucfirst((string) $this->schedule_type),
        };
    }
}
