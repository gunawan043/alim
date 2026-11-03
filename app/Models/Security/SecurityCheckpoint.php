<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SecurityCheckpoint extends SecurityModel
{
    protected $table = 'security_checkpoints';

    protected $fillable = ['post_id', 'code', 'name', 'qr_token', 'location', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->qr_token)) {
                $model->qr_token = (string) Str::uuid();
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SecurityPatrolLog::class, 'checkpoint_id');
    }

    public function scanUrl(): string
    {
        return route('user.keamanan.patrol.scan', ['token' => $this->qr_token]);
    }
}
