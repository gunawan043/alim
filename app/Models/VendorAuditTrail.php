<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorAuditTrail extends Model
{
    use HasFactory;

    protected $table = 'vendor_audit_trails';

    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'actor_type',
        'actor_id',
        'actor_name',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(
        string $entityType,
        int $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        $actor = null
    ): self {
        $actorId = null;
        $actorType = null;
        $actorName = null;

        if ($actor !== null) {
            if (is_object($actor)) {
                $actorType = $actor instanceof \App\Models\Vendor ? 'vendor' : 'user';
                $actorId = $actor->id ?? null;
                $actorName = $actor->name ?? null;
            } elseif (is_int($actor)) {
                $actorId = $actor;
                $actorType = 'user';
            }
        }

        return self::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
