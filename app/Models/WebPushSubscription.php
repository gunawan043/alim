<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebPushSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'web_push_subscriptions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh',
        'auth_token',
        'content_encoding',
        'user_agent',
        'last_seen_at',
        'revoked_at',
    ];

    protected $hidden = [
        'p256dh',
        'auth_token',
    ];

    protected $casts = [
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
}
