<?php

declare(strict_types=1);

namespace App\Authorization\Models;

use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $scope_key
 * @property string $scope_school_id
 * @property string $fingerprint
 * @property array<int|string, mixed>|null $permissions
 * @property array<int|string, mixed>|null $revoked
 * @property Carbon|null $expires_at
 * @property bool $is_current
 * @property Carbon $created_at
 * @property Carbon|null $archived_at
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeSchool(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'scope_school_id');
    }
}
