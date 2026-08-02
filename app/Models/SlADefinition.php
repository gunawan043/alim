<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlADefinition extends Model
{
    protected $table = 'sarpras_sla_definitions';

    protected $fillable = [
        'workflow_type',
        'priority',
        'response_minutes',
        'resolution_minutes',
        'escalation_minutes',
        'escalation_chain',
        'is_active',
    ];

    protected $casts = [
        'escalation_chain' => 'array',
        'is_active' => 'boolean',
    ];

    public static function forWorkflow(string $workflowType, string $priority = 'medium'): ?self
    {
        return static::where('workflow_type', $workflowType)
            ->where('priority', $priority)
            ->where('is_active', true)
            ->first();
    }
}
