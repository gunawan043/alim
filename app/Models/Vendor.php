<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $fillable = [
        'vendor_code', 'name', 'legal_name', 'npwp', 'category_id', 'vendor_type',
        'status', 'phone', 'phone_alt', 'email', 'website', 'logo_path',
        'established_year', 'total_employees', 'rating_avg', 'rating_count',
        'risk_classification', 'credit_limit', 'payment_term_days',
        'preferred_currency', 'notes', 'created_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'rating_avg' => 'decimal:2',
        'rating_count' => 'integer',
        'total_employees' => 'decimal:0',
        'established_year' => 'integer',
        'payment_term_days' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(VendorContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(VendorAddress::class);
    }

    public function banks(): HasMany
    {
        return $this->hasMany(VendorBank::class);
    }

    public function tax(): HasMany
    {
        return $this->hasMany(VendorTax::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(VendorContract::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(VendorWarranty::class);
    }

    public function slas(): HasMany
    {
        return $this->hasMany(VendorSla::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(VendorRating::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(VendorEvaluation::class);
    }

    public function performanceHistory(): HasMany
    {
        return $this->hasMany(VendorPerformanceHistory::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklist';
    }

    public function recomputeRating(): void
    {
        $stats = $this->ratings()
            ->selectRaw('AVG(overall_score) as avg_score, COUNT(*) as cnt')
            ->first();

        $this->rating_avg = round((float) ($stats->avg_score ?? 0), 2);
        $this->rating_count = (int) ($stats->cnt ?? 0);
        $this->save();
    }
}
