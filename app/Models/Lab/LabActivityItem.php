<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabActivityItem extends LabModel
{
    protected $casts = ['quantity' => 'integer'];

    protected $table = 'lab_activity_items';

    public function activity(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabActivity::class, 'activity_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
}
