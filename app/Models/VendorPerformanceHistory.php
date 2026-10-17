<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPerformanceHistory extends Model
{
    use HasFactory;

    protected $table = 'vendor_performance_history';

    protected $fillable = [
        'vendor_id', 'snapshot_date', 'rating_avg', 'rating_count',
        'active_orders', 'on_time_pct', 'total_value_ytd',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
