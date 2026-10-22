<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageCoaching extends Model
{
    protected $table = 'language_coachings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'violation_id', 'category', 'action', 'coaching_date', 'coach_id',
        'notes', 'follow_up', 'pledge_signed',
    ];

    protected $casts = [
        'coaching_date' => 'date',
        'pledge_signed' => 'boolean',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(LanguageViolation::class, 'violation_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
