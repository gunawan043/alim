<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rma extends Model
{
    use HasFactory;

    protected $table = 'rmas';

    protected $fillable = [
        'rma_number',
        'purchase_order_id',
        'quality_check_id',
        'goods_receipt_id',
        'vendor_id',
        'vendor_reference',
        'status',
        'type',
        'quantity',
        'request_date',
        'estimated_return_date',
        'actual_return_date',
        'description',
        'resolution',
        'refund_amount',
        'cost_deduction',
        'evidence',
        'vendor_response',
        'vendor_responded_at',
        'created_by',
        'resolved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'estimated_return_date' => 'date',
        'actual_return_date' => 'date',
        'refund_amount' => 'decimal:2',
        'cost_deduction' => 'decimal:2',
        'evidence' => 'array',
        'vendor_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_RETURN = 'in_return';

    public const STATUS_RECEIVED_BY_VENDOR = 'received_by_vendor';

    public const STATUS_REPLACEMENT_RECEIVED = 'replacement_received';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CREDITED = 'credited';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_DEFECTIVE = 'defective';

    public const TYPE_WRONG_ITEM = 'wrong_item';

    public const TYPE_MISSING = 'missing';

    public const TYPE_DAMAGED = 'damaged';

    public const TYPE_NON_CONFORMING = 'non_conforming';

    public const ALLOWED_TYPES = [
        self::TYPE_DEFECTIVE,
        self::TYPE_WRONG_ITEM,
        self::TYPE_MISSING,
        self::TYPE_DAMAGED,
        self::TYPE_NON_CONFORMING,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function qualityCheck(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class, 'quality_check_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "RMA-{$year}{$month}-";

        $latest = static::where('rma_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('rma_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, [
            self::STATUS_REPLACEMENT_RECEIVED,
            self::STATUS_REFUNDED,
            self::STATUS_CREDITED,
            self::STATUS_CLOSED,
        ], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
