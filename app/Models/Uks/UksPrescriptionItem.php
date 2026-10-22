<?php

declare(strict_types=1);

namespace App\Models\Uks;

use App\Models\StudentMedicineInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksPrescriptionItem — Satu baris obat pada resep UKS.
 */
class UksPrescriptionItem extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_prescription_items';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'prescription_id',
        'inventory_id',
        'medicine_name',
        'dosage',
        'frequency',
        'route',
        'duration_days',
        'quantity',
        'dispensed_quantity',
        'instructions',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'quantity' => 'decimal:2',
        'dispensed_quantity' => 'decimal:2',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(UksPrescription::class, 'prescription_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(StudentMedicineInventory::class, 'inventory_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->dispensed_quantity);
    }
}
