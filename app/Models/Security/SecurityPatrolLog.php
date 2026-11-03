<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityPatrolLog extends SecurityModel
{
    protected $table = 'security_patrol_logs';

    public const CONDITION_OPTIONS = [
        'aman' => 'Aman',
        'temuan' => 'Ada Temuan',
    ];

    protected $fillable = ['schedule_id', 'checkpoint_id', 'scanned_by', 'scanned_at', 'condition', 'notes'];

    protected $casts = ['scanned_at' => 'datetime'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SecurityPatrolSchedule::class, 'schedule_id');
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(SecurityCheckpoint::class, 'checkpoint_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
