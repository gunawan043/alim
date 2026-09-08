<?php

declare(strict_types=1);

namespace App\Authorization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $permission
 * @property string $scope_key
 * @property string $reason
 * @property string $granted_by
 * @property Carbon $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon $created_at
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
