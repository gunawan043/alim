<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageMufradatSchedule extends Model
{
    protected $table = 'language_mufradat_schedules';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'period_id', 'mustami_id', 'day_of_week', 'time_start', 'time_end',
        'place', 'is_active', 'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(LanguageVocabularyPeriod::class, 'period_id');
    }

    public function mustami(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mustami_id');
    }

    public function getDayLabelAttribute(): string
    {
        return match ($this->day_of_week) {
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu', default => '-',
        };
    }
}
