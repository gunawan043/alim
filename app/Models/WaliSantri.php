<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaliSantri extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public const ROLE_AYAH = 'ayah';
    public const ROLE_IBU = 'ibu';
    public const ROLE_KAKEK = 'kakek';
    public const ROLE_NENEK = 'nenek';
    public const ROLE_WALI = 'wali';
    public const ROLE_LAINNYA = 'lainnya';

    public const VALID_ROLES = [
        self::ROLE_AYAH,
        self::ROLE_IBU,
        self::ROLE_KAKEK,
        self::ROLE_NENEK,
        self::ROLE_WALI,
        self::ROLE_LAINNYA,
    ];

    public const MAX_WALI_PER_STUDENT = 5;

    protected $fillable = [
        'user_id',
        'student_id',
        'role',
        'is_primary',
        'access_token',
        'access_expires_at',
        'verified_at',
        'verified_by',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'access_expires_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccessTokenValid(): bool
    {
        return $this->access_token
            && $this->access_expires_at
            && $this->access_expires_at->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function generateAccessToken(): string
    {
        $this->access_token = bin2hex(random_bytes(32));
        $this->access_expires_at = now()->addHours(24);
        return $this->access_token;
    }

    public function consumeAccessToken(string $token): bool
    {
        if (!$this->isAccessTokenValid() || $this->access_token !== $token) {
            return false;
        }
        $this->access_token = null;
        $this->access_expires_at = null;
        return true;
    }

    public static function maskNoKk(string $noKk): string
    {
        return substr($noKk, 0, 4) . '••••••••' . substr($noKk, -4);
    }
}