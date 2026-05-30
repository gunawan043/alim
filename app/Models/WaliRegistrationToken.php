<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaliRegistrationToken extends Pivot
{
    use SoftDeletes;

    protected $table = 'wali_registration_tokens';

    protected $fillable = [
        'token',
        'user_id',
        'nik_santri',
        'no_kk',
        'intent',
        'student_id',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public const INTENT_LINK_SANTRI    = 'link_santri';
    public const INTENT_REGISTER_NEW   = 'register_new';
    public const INTENT_ADD_SECOND_WALI = 'add_second_wali';

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }
}