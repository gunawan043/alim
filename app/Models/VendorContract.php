<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorContract extends Model
{
    use HasFactory;

    protected $table = 'vendor_contracts';

    protected $fillable = [
        'vendor_id', 'contract_number', 'title', 'start_date', 'end_date',
        'signed_at', 'contract_value', 'contract_type', 'status',
        'scope_of_work', 'terms', 'document_path', 'auto_renew',
        'auto_renew_days_before', 'signed_by_user',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'date',
        'contract_value' => 'decimal:2',
        'auto_renew' => 'boolean',
        'terms' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->end_date === null || $this->end_date->gte(now()));
    }

    public function daysToExpiry(): ?int
    {
        return $this->end_date ? now()->diffInDays($this->end_date, false) : null;
    }
}