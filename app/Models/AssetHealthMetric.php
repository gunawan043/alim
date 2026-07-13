<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetHealthMetric extends Model
{
    protected $table = 'asset_health_metrics';

    protected $fillable = [
        'asset_id',
        'health_score',
        'grade',
        'repair_count',
        'lifetime_repair_cost',
        'total_downtime_minutes',
        'maintenance_overdue_count',
        'audit_failures_count',
        'age_years',
        'last_computed_at',
    ];

    protected $casts = [
        'last_computed_at' => 'datetime',
    ];

    public function gradeFromScore(): string
    {
        return match (true) {
            $this->health_score >= 90 => 'A',
            $this->health_score >= 75 => 'B',
            $this->health_score >= 50 => 'C',
            $this->health_score >= 25 => 'D',
            default => 'E',
        };
    }

    public function recommendation(): string
    {
        return match ($this->grade) {
            'A' => 'Operate normally.',
            'B' => 'Monitor; preventive maintenance recommended.',
            'C' => 'Increase inspection frequency.',
            'D' => 'Repair or replace within the next quarter.',
            'E' => 'Immediate decommission candidate.',
            default => 'Recompute required.',
        };
    }
}