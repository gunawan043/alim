<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaliRegistrationToken extends Pivot
{
    use LogsDeletion;
    use SoftDeletes;

    protected $table = 'wali_registration_tokens';

    protected $fillable = [
        'token',
        'user_id',
        'school_id',
        'nik_santri',
        'no_kk',
        'intent',
        'student_id',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public const INTENT_LINK_SANTRI = 'link_santri';

    public const INTENT_REGISTER_NEW = 'register_new';

    public const INTENT_ADD_SECOND_WALI = 'add_second_wali';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
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
        return ! $this->isExpired() && ! $this->isUsed();
    }
}
