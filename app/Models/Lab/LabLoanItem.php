<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabLoanItem extends LabModel
{
    protected $casts = ['quantity' => 'integer', 'quantity_returned' => 'integer'];

    protected $table = 'lab_loan_items';

    public function loan(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabLoan::class, 'loan_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
}
