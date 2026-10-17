<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAddress extends Model
{
    use HasFactory;

    protected $table = 'vendor_addresses';

    protected $fillable = [
        'vendor_id', 'address_type', 'label', 'street_address',
        'rt', 'rw', 'village', 'district', 'city', 'province',
        'postal_code', 'country', 'latitude', 'longitude', 'is_default',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_default' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
