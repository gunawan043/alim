<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_devices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'provider',
        'push_token',
        'platform',
        'device_name',
        'app_version',
        'device_id',
        'metadata',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
