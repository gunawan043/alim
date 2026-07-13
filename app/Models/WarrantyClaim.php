<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $table = 'warranty_claims';

    protected $fillable = [
        'claim_number', 'asset_id', 'vendor_id', 'vendor_warranty_id',
        'defect_description', 'claim_date', 'resolution_date',
        'status', 'outcome', 'claimed_amount', 'approved_amount',
        'notes', 'submitted_by', 'approved_by',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'resolution_date' => 'date',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorWarranty(): BelongsTo
    {
        return $this->belongsTo(VendorWarranty::class, 'vendor_warranty_id');
    }
}