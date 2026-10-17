<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use HasFactory;

    protected $table = 'vendor_documents';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'name',
        'storage_path',
        'mime_type',
        'file_size',
        'type',
        'status',
        'expiry_date',
        'issued_date',
        'notes',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'issued_date' => 'date',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public const TYPE_BUSINESS_LICENSE = 'business_license';

    public const TYPE_NPWP = 'npwp';

    public const TYPE_COMPANY_REGISTRATION = 'company_registration';

    public const TYPE_TAX_CERTIFICATE = 'tax_certificate';

    public const TYPE_ISO_CERTIFICATE = 'iso_certificate';

    public const TYPE_INSURANCE = 'insurance';

    public const TYPE_BANK_REFERENCE = 'bank_reference';

    public const TYPE_PRODUCT_CATALOG = 'product_catalog';

    public const TYPE_PRICE_LIST = 'price_list';

    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expiry_date && $this->expiry_date->lt(now());
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($days));
    }
}
