<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DivisionBudget extends Model
{
    use HasFactory;

    protected $table = 'division_budgets';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?? (string) Str::uuid());
    }

    protected $fillable = [
        'id', 'division_id', 'fiscal_year', 'allocated_amount',
        'used_amount', 'reserved_amount', 'last_purpose',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'allocated_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'reserved_amount' => 'decimal:2',
    ];

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->allocated_amount - $this->used_amount - $this->reserved_amount);
    }

    public function utilizationPercentage(): float
    {
        if ((float) $this->allocated_amount <= 0) {
            return 0;
        }

        return round(((float) $this->used_amount / (float) $this->allocated_amount) * 100, 2);
    }
}
