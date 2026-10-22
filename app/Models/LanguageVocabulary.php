<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageVocabulary extends Model
{
    protected $table = 'language_vocabularies';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = ['period_id', 'position', 'arabic', 'meaning', 'example_sentence'];

    protected $casts = ['position' => 'integer'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(LanguageVocabularyPeriod::class, 'period_id');
    }
}
