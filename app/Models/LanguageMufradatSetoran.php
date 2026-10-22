<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageMufradatSetoran extends Model
{
    protected $table = 'language_mufradat_setorans';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'period_id', 'schedule_id', 'student_id', 'mustami_id', 'setoran_date',
        'total_words', 'correct_words', 'score', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'setoran_date' => 'date',
        'total_words' => 'integer',
        'correct_words' => 'integer',
        'score' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(LanguageVocabularyPeriod::class, 'period_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LanguageMufradatSchedule::class, 'schedule_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mustami(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mustami_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'ulang' ? 'Ulang' : 'Lulus';
    }

    public function getScoreLabelAttribute(): string
    {
        return number_format((float) $this->score, 1, ',', '.').'%';
    }
}
