<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabDisposalItem extends LabModel
{
    protected $casts = ['quantity' => 'integer', 'sort' => 'integer'];

    protected $table = 'lab_disposal_items';

    public function disposal(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabDisposal::class, 'disposal_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
}
