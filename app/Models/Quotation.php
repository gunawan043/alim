<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $table = 'quotations';

    protected $fillable = [
        'quotation_number',
        'rfq_id',
        'vendor_id',
        'status',
        'quotation_date',
        'valid_until',
        'lead_time_days',
        'terms',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'currency',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_EXPIRED = 'expired';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
        self::STATUS_EXPIRED,
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'submitted_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotation_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'quotation_id');
    }

    public function recalculateTotals(): self
    {
        $this->loadMissing('items');

        $subtotal = $this->items->sum(fn ($i) => (float) $i->line_total);

        $this->subtotal = $subtotal;
        $this->total = $subtotal
            - (float) $this->discount
            + (float) $this->tax
            + (float) $this->shipping_cost;

        return $this;
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "QUO-{$year}{$month}-";

        $latest = static::where('quotation_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('quotation_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED,
        ], true);
    }

    public function isExpired(): bool
    {
        return $this->valid_until->isPast();
    }
}
