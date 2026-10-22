<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LanguageViolation extends Model
{
    protected $table = 'language_violations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id', 'student_id', 'violation_date', 'place', 'class_name',
        'dormitory_id', 'violation_type', 'description', 'points', 'status',
        'reported_by', 'notes',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'points' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function coachings(): HasMany
    {
        return $this->hasMany(LanguageCoaching::class, 'violation_id')->orderByDesc('coaching_date');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'coached' => 'Sudah Dibina',
            'closed' => 'Selesai',
            default => 'Tercatat',
        };
    }
}
