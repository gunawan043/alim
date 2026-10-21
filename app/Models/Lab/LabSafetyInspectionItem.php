<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabSafetyInspectionItem extends LabModel
{
    protected $casts = ['source_no' => 'integer', 'sort' => 'integer'];

    protected $table = 'lab_safety_inspection_items';

    public function inspection(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabInspection::class, 'inspection_id'); }
}
