<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DormitoryPolicyAssignment extends Model
{
    use HasFactory;

    protected $table = 'dormitory_policy_assignments';

    protected $fillable = [
        'boarding_policy_id',
        'policy_assignment_type', // 'dormitory' | 'sekolah' | 'kelas'
        'target_id',
        'effective_from',
        'effective_until',
        'notes',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function boardingPolicy(): BelongsTo
    {
        return $this->belongsTo(BoardingPolicy::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'target_id', 'id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'target_id', 'id');
    }

    public function getTargetAttribute()
    {
        return match ($this->policy_assignment_type) {
            'dormitory' => $this->dormitory,
            'sekolah' => $this->sekolah,
            default => null,
        };
    }
}
