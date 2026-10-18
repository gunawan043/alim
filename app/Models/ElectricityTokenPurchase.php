<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ElectricityTokenPurchase extends Model
{
    protected $table = 'electricity_token_purchases';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'meter_id', 'period', 'amount', 'purchased_at',
        'receipt_path', 'receipt_url', 'notes', 'recorded_by',
    ];

    protected $casts = ['period' => 'date', 'purchased_at' => 'date', 'amount' => 'decimal:2'];

    public function meter(): BelongsTo { return $this->belongsTo(ElectricityMeter::class, 'meter_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
