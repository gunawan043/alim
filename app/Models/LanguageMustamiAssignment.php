<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LanguageMustamiAssignment extends Model
{
    protected $table = 'language_mustami_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = ['period_id', 'mustami_id', 'scope', 'dormitory_id', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(LanguageVocabularyPeriod::class, 'period_id');
    }

    public function mustami(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mustami_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }
}
