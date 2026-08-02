<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'goods_receipt_items';

    public $timestamps = false;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'item_name',
        'expected_quantity',
        'received_quantity',
        'accepted_quantity',
        'rejected_quantity',
        'discrepancy_notes',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function isComplete(): bool
    {
        return $this->received_quantity === $this->expected_quantity;
    }

    public function isOverReceived(): bool
    {
        return $this->received_quantity > $this->expected_quantity;
    }
}
