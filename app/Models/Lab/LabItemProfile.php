<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabItemProfile extends LabModel
{
    protected $casts = ['requires_calibration' => 'boolean', 'is_consumable' => 'boolean', 'calibration_interval_months' => 'integer', 'min_stock' => 'integer'];

    protected $table = 'lab_item_profiles';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
    public function batches(): HasMany { return $this->hasMany(\App\Models\Lab\LabItemBatch::class, 'asset_id'); }
    public function movements(): HasMany { return $this->hasMany(\App\Models\Lab\LabStockMovement::class, 'asset_id'); }
}
