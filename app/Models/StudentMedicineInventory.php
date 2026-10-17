<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudentMedicineInventory extends Model
{
    protected $table = 'student_medicine_inventory';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'medicine_name',
        'medicine_code',
        'category',
        'generic_name',
        'unit',
        'current_stock',
        'min_stock_alert',
        'expiry_date',
        'storage_location',
        'supplier',
        'purchase_date',
        'unit_price',
        'dosage_info',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'purchase_date' => 'date',
        'current_stock' => 'decimal:2',
        'min_stock_alert' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StudentMedicineLog::class, 'inventory_id');
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getCategoryTextAttribute(): string
    {
        return match ($this->category) {
            'obat_dalam' => 'Obat Dalam',
            'obat_luar' => 'Obat Luar',
            'vitamin_suplemen' => 'Vitamin & Suplemen',
            'antiseptik' => 'Antiseptik',
            'alat_kesehatan' => 'Alat Kesehatan',
            default => $this->category,
        };
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->between(now(), now()->addMonth(3));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock_alert;
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeLowStock($q)
    {
        return $q->whereColumn('current_stock', '<=', 'min_stock_alert')
            ->where('min_stock_alert', '>', 0);
    }

    public function scopeExpiringSoon($q)
    {
        return $q->whereBetween('expiry_date', [now(), now()->addMonth(3)]);
    }

    public function scopeExpired($q)
    {
        return $q->where('expiry_date', '<', now());
    }
}
