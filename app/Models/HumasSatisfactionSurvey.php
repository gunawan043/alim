<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HumasSatisfactionSurvey extends Model
{
    use HasUuids;

    protected $table = 'humas_satisfaction_surveys';

    protected $fillable = ['school_id', 'title', 'description', 'audience', 'status', 'starts_at', 'ends_at', 'created_by'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function responses(): HasMany { return $this->hasMany(HumasSatisfactionResponse::class, 'humas_satisfaction_survey_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
