<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    public $timestamps = false;

    protected $fillable = [
        'purchase_order_id',
        'quotation_item_id',
        'item_name',
        'specification',
        'brand',
        'ordered_quantity',
        'received_quantity',
        'unit_price',
        'line_total',
        'failed_quantity',
        'returned_quantity',
    ];

    protected $casts = [
        'ordered_quantity' => 'integer',
        'received_quantity' => 'integer',
        'failed_quantity' => 'integer',
        'returned_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->ordered_quantity - $this->received_quantity);
    }

    public function isFullyReceived(): bool
    {
        return $this->outstandingQuantity() === 0;
    }
}
