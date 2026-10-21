<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabItemBatch extends LabModel
{
    protected $casts = ['quantity' => 'integer', 'received_date' => 'date', 'expiry_date' => 'date'];

    protected $table = 'lab_item_batches';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
    public function movements(): HasMany { return $this->hasMany(\App\Models\Lab\LabStockMovement::class, 'batch_id'); }
}
