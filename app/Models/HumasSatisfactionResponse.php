<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HumasSatisfactionResponse extends Model
{
    use HasUuids;

    protected $table = 'humas_satisfaction_responses';

    protected $fillable = ['humas_satisfaction_survey_id', 'user_id', 'rating', 'feedback', 'submitted_at'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function survey(): BelongsTo { return $this->belongsTo(HumasSatisfactionSurvey::class, 'humas_satisfaction_survey_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
