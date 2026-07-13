<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSla extends Model
{
    use HasFactory;

    protected $table = 'vendor_slas';

    protected $fillable = [
        'vendor_id', 'contract_id', 'workflow_type',
        'response_minutes', 'resolution_minutes', 'penalty_per_day',
        'bonus_target_completion_pct', 'priority',
    ];

    protected $casts = [
        'response_minutes' => 'integer',
        'resolution_minutes' => 'integer',
        'penalty_per_day' => 'decimal:2',
        'bonus_target_completion_pct' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(VendorContract::class, 'contract_id');
    }
}