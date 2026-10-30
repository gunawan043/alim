<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Token verifikasi dua langkah untuk penghubungan wali–santri.
 * Token asli hanya dikirim sekali ke client; database menyimpan hash SHA-256.
 */
class WaliLinkVerification extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'student_id',
        'school_id',
        'token_hash',
        'relationship',
        'method',
        'ip_address',
        'user_agent',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'student_id' => 'string',
        'school_id' => 'string',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
