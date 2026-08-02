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
        'contract_number',
        'vendor_id',
        'user_id',
        'title',
        'scope',
        'terms_and_conditions',
        'start_date',
        'end_date',
        'auto_renewal_date',
        'renewal_type',
        'status',
        'annual_value',
        'monthly_value',
        'slas',
        'attachment_path',
        'signed_by_vendor',
        'signed_at',
        'signed_by_admin',
        'admin_signed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renewal_date' => 'date',
        'annual_value' => 'decimal:2',
        'monthly_value' => 'decimal:2',
        'slas' => 'array',
        'signed_at' => 'datetime',
        'admin_signed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRING_SOON = 'expiring_soon';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TERMINATED = 'terminated';

    public const STATUS_SUSPENDED = 'suspended';

    public const RENEWAL_MANUAL = 'manual';

    public const RENEWAL_AUTOMATIC = 'automatic';

    public const RENEWAL_NONE = 'none';

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function signedByVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'signed_by_vendor');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->end_date !== null
            && $this->end_date->gte(now());
    }

    public function isExpiring(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->end_date && $this->end_date->lte(now()->addDays(30));
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->lt(now());
    }

    public function daysToExpiry(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "VC-{$year}-";

        $latest = static::where('contract_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('contract_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
