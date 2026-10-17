<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'gr_number',
        'purchase_order_id',
        'delivery_id',
        'receipt_date',
        'status',
        'warehouse_location',
        'received_by',
        'warehouse_id',
        'supplier_delivery_note',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public const STATUS_RECEIVED = 'received';

    public const STATUS_UNDER_INSPECTION = 'under_inspection';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_CLOSED = 'closed';

    public const ALLOWED_STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_UNDER_INSPECTION,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_PARTIAL,
        self::STATUS_CLOSED,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(DeliveryTracking::class, 'delivery_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class, 'goods_receipt_id');
    }

    public function generateNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "GR-{$year}{$month}-";

        $latest = static::where('gr_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('gr_number');

        $sequence = 1;
        if ($latest) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function isFullyAccepted(): bool
    {
        $totalExpected = $this->items->sum('expected_quantity');
        $totalAccepted = $this->items->sum('accepted_quantity');

        return $totalAccepted >= $totalExpected && $totalAccepted > 0;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function hasDiscrepancies(): bool
    {
        return $this->items->contains(fn ($item) => $item->received_quantity !== $item->expected_quantity);
    }
}
