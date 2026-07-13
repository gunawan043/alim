<?php

declare(strict_types=1);

namespace App\Authorization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property string $scope_key
 * @property string $event
 * @property string $fingerprint
 * @property string $status
 * @property string|null $error
 * @property \Illuminate\Support\Carbon $created_at
 */
final class SnapshotAuditLog extends Model
{
    protected $table = 'snapshot_audit_log';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}