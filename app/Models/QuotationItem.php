<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $table = 'quotation_items';

    public $timestamps = false;

    protected $fillable = [
        'quotation_id',
        'rfq_item_id',
        'item_name',
        'specification',
        'brand',
        'model',
        'origin',
        'quantity',
        'unit_price',
        'line_total',
        'warranty_months',
        'warranty_type',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class, 'rfq_item_id');
    }
}
