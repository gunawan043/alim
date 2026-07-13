<?php

declare(strict_types=1);

namespace App\Authorization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property string $permission
 * @property string $scope_key
 * @property string $reason
 * @property string $granted_by
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \Illuminate\Support\Carbon $created_at
 */
final class RevokedPermission extends Model
{
    protected $table = 'revoked_permissions';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'permission',
        'scope_key',
        'reason',
        'granted_by',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'granted_by');
    }
}