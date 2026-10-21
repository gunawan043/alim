<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabInspectionItem extends LabModel
{
    protected $casts = ['inspected_at' => 'date', 'quantity' => 'integer', 'sort' => 'integer'];

    protected $table = 'lab_inspection_items';

    public function inspection(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabInspection::class, 'inspection_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
}
