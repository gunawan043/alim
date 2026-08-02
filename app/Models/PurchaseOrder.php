<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'vendor_id',
        'rfq_id',
        'quotation_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'delivery_address',
        'shipping_notes',
        'payment_terms',
        'special_instructions',
        'subtotal',
        'discount',
        'tax',
        'shipping_cost',
        'total',
        'currency',
        'created_by',
        'sent_at',
        'accepted_at',
        'accepted_by',
        'rejected_at',
        'rejection_reason',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IN_PRODUCTION = 'in_production';

    public const STATUS_READY_TO_SHIP = 'ready_to_ship';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_QC_IN_PROGRESS = 'qc_in_progress';

    public const STATUS_QC_PASSED = 'qc_passed';

    public const STATUS_QC_FAILED = 'qc_failed';

    public const STATUS_INVOICED = 'invoiced';

    public const STATUS_PAID = 'paid';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_IN_PRODUCTION,
        self::STATUS_READY_TO_SHIP,
        self::STATUS_SHIPPED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_QC_IN_PROGRESS,
        self::STATUS_QC_PASSED,
        self::STATUS_QC_FAILED,
        self::STATUS_INVOICED,
        self::STATUS_PAID,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedByVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'accepted_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryTracking::class, 'purchase_order_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'purchase_order_id');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'purchase_order_id');
    }

    public function rmas(): HasMany
    {
        return $this->hasMany(Rma::class, 'purchase_order_id');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PO-{$year}{$month}-";

        $latest = static::where('po_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('po_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
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

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
            self::STATUS_PAID,
        ], true);
    }

    public function isDelivered(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_QC_IN_PROGRESS,
            self::STATUS_QC_PASSED,
            self::STATUS_QC_FAILED,
            self::STATUS_INVOICED,
            self::STATUS_PAID,
            self::STATUS_CLOSED,
        ], true);
    }

    public function isQcCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_QC_PASSED,
            self::STATUS_QC_FAILED,
            self::STATUS_INVOICED,
            self::STATUS_PAID,
            self::STATUS_CLOSED,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ], true);
    }
}
