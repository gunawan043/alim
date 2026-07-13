<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorEvaluation extends Model
{
    use HasFactory;

    protected $table = 'vendor_evaluations';

    protected $fillable = [
        'vendor_id', 'period_start', 'period_end',
        'total_orders', 'completed_orders', 'on_time_pct',
        'quality_avg', 'response_time_avg_minutes',
        'total_value', 'penalty_amount', 'grade',
        'blacklist_recommendation', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'on_time_pct' => 'decimal:2',
        'quality_avg' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function computeGrade(): string
    {
        $score = 0;
        $score += min(40, $this->on_time_pct * 0.4);
        $score += min(40, ((float) $this->quality_avg) * 8);
        $score += min(20, max(0, 20 - ($this->response_time_avg_minutes / 60)));

        return match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'E',
        };
    }
}