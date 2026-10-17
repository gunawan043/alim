<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaTracker extends Model
{
    protected $table = 'sarpras_sla_trackers';

    protected $fillable = [
        'workflow_type',
        'entity_id',
        'entity_table',
        'priority',
        'started_at',
        'deadline_at',
        'paused_at',
        'completed_at',
        'accumulated_seconds',
        'status',
        'escalation_level',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getRemainingMinutesAttribute(): int
    {
        if ($this->completed_at) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->deadline_at, false);
    }

    public function getOverdueMinutesAttribute(): int
    {
        if ($this->completed_at || ! $this->deadline_at->isPast()) {
            return 0;
        }

        return (int) $this->deadline_at->diffInMinutes(now());
    }

    public function markCompleted(): void
    {
        $this->completed_at = now();
        $this->status = 'completed';
        $this->save();
    }

    public function escalate(int $level): void
    {
        $this->escalation_level = $level;
        $this->status = 'escalated';
        $this->save();
    }
}
