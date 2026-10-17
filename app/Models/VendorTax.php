<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTax extends Model
{
    use HasFactory;

    protected $table = 'vendor_taxes';

    protected $fillable = [
        'vendor_id', 'npwp', 'pkp_status', 'pkp_number',
        'tax_office', 'tax_attachment_path', 'tax_registered_at',
    ];

    protected $casts = [
        'tax_registered_at' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
