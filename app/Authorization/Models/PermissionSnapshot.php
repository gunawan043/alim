<?php

declare(strict_types=1);

namespace App\Authorization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property string $scope_key
 * @property string $scope_school_id
 * @property string $fingerprint
 * @property array<int|string, mixed>|null $permissions
 * @property array<int|string, mixed>|null $revoked
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_current
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
final class PermissionSnapshot extends Model
{
    protected $table = 'permission_snapshots';

    public $timestamps = false;

    protected $casts = [
        'permissions' => 'array',
        'revoked' => 'array',
        'is_current' => 'bool',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function scopeSchool(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WorkUnit::class, 'scope_school_id');
    }
}
