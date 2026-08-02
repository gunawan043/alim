<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBank extends Model
{
    use HasFactory;

    protected $table = 'vendor_banks';

    protected $fillable = [
        'vendor_id', 'bank_name', 'bank_branch', 'account_number',
        'account_holder', 'swift_code', 'currency', 'is_primary',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
