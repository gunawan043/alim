<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoanItem extends Model
{
    use HasUuids;

    protected $table = 'library_loan_items';

    protected $fillable = [
        'library_loan_id', 'library_item_id', 'returned_at', 'return_condition',
        'return_notes', 'status',
    ];

    protected $casts = ['returned_at' => 'date'];

    public function loan(): BelongsTo { return $this->belongsTo(LibraryLoan::class, 'library_loan_id'); }
    public function item(): BelongsTo { return $this->belongsTo(LibraryItem::class, 'library_item_id'); }
}
