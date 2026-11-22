<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pemetaan terverifikasi identitas WhatsApp → user Core.
 *
 * Hanya baris dengan `verified_at` terisi dan `is_active` = true yang boleh
 * dipakai untuk mengotorisasi command persetujuan.
 */
class ConnectActorIdentity extends Model
{
    protected $table = 'connect_actor_identities';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'channel',
        'external_identity',
        'user_id',
        'display_name',
        'is_active',
        'verified_at',
        'verified_by',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Normalisasi nomor WhatsApp / JID menjadi digit E.164 tanpa tanda baca.
     * `0812-3456-7890`, `+62 812 3456 7890`, dan
     * `6281234567890@s.whatsapp.net` semuanya menjadi `6281234567890`.
     */
    public static function normalizeIdentity(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/@.*$/', '', $value);
        $value = (string) preg_replace('/[^0-9]/', '', $value);

        if (str_starts_with($value, '0')) {
            $value = '62'.substr($value, 1);
        }

        return $value;
    }
}
