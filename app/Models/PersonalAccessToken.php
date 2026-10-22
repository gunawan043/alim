<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Tabel personal_access_tokens pada aplikasi ini memakai UUID, bukan bigint.
 * Model bawaan Sanctum mengasumsikan auto-increment integer; override di sini
 * agar pembuatan token mobile tidak gagal pada primary key.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $token) {
            $token->id = $token->id ?: (string) Str::uuid();
        });
    }
}
