<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ElectricityMeter extends Model
{
    protected $table = 'electricity_meters';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'name', 'meter_type', 'meter_number', 'location', 'default_amount',
        'payment_due_day', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = ['default_amount' => 'decimal:2', 'is_active' => 'boolean', 'payment_due_day' => 'integer'];

    public function purchases(): HasMany { return $this->hasMany(ElectricityTokenPurchase::class, 'meter_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
