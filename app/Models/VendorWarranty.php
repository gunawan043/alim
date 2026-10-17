<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorWarranty extends Model
{
    use HasFactory;

    protected $table = 'vendor_warranties';

    protected $fillable = [
        'vendor_id', 'asset_id', 'warranty_number', 'scope',
        'start_date', 'end_date', 'coverage_type', 'terms',
        'document_path', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date?->lte(now())
            && $this->end_date?->gte(now());
    }
}
