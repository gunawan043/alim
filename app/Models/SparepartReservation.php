<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparepartReservation extends Model
{
    use HasFactory;

    protected $table = 'sparepart_reservations';

    protected $fillable = [
        'sparepart_id', 'reference_type', 'reference_id',
        'quantity', 'consumed_quantity', 'reserved_by',
        'reserved_at', 'expires_at', 'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function sparepart(): BelongsTo
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function reserve(): int
    {
        return max(0, (int) ceil((float) $this->quantity - (float) $this->consumed_quantity));
    }
}