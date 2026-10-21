<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabConditionHistory extends LabModel
{
    protected $casts = ['occurred_at' => 'datetime'];

    protected $table = 'lab_condition_histories';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
    public function actor(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'actor_id'); }
}
