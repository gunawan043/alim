<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabStockMovement extends LabModel
{
    protected $casts = ['quantity' => 'integer', 'occurred_at' => 'datetime'];

    protected $table = 'lab_stock_movements';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
    public function batch(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabItemBatch::class, 'batch_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'actor_id'); }
}
