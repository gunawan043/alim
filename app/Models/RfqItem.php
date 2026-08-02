<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqItem extends Model
{
    use HasFactory;

    protected $table = 'rfq_items';

    public $timestamps = false;

    protected $fillable = [
        'rfq_id',
        'item_name',
        'specification',
        'quantity',
        'unit',
        'notes',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RfqRequest::class, 'rfq_id');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(RfqQuotationItem::class, 'rfq_item_id');
    }
}
