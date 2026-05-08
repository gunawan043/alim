<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SecureAccessToken extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'role_name',
        'token',
        'ip_address',
        'user_agent',
        'expires_at',
        'used_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->whereNull('used_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->orWhereNotNull('used_at');
    }

    public function scopeByToken($query, $token)
    {
        return $query->where('token', $token);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->where('role_name', $roleName);
    }

    // TOKEN MANAGEMENT
    public function markAsUsed()
    {
        $this->used_at = now();
        $this->save();

        AuditLog::create([
            'user_id' => $this->user_id,
            'action' => 'SECURE_TOKEN_USED',
            'table_name' => 'secure_access_tokens',
            'record_id' => $this->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function isValid()
    {
        return $this->expires_at > now() && !$this->used_at;
    }

    public function isExpired()
    {
        return $this->expires_at <= now() || $this->used_at;
    }

    // TOKEN GENERATION
    public static function generateToken($userId, $roleName, $ipAddress, $userAgent, $expiryHours = 24)
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = Hash::make($token);

        $secureToken = self::create([
            'user_id' => $userId,
            'role_name' => $roleName,
            'token' => $hashedToken,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addHours($expiryHours),
        ]);

        // Simpan token plaintext sementara (harus dihapus setelah dikirim ke user)
        $secureToken->plain_token = $token;

        return $secureToken;
    }

    public static function verifyToken($token, $roleName = null)
    {
        $tokens = self::valid()->get();
        
        foreach ($tokens as $secureToken) {
            if (Hash::check($token, $secureToken->token)) {
                if ($roleName && $secureToken->role_name !== $roleName) {
                    continue;
                }
                
                $secureToken->markAsUsed();
                return $secureToken;
            }
        }
        
        return null;
    }

    // ACCESSORS
    public function getMaskedTokenAttribute()
    {
        return substr($this->token, 0, 8) . '...' . substr($this->token, -8);
    }

    public function getMaskedIpAddressAttribute()
    {
        $ip = $this->ip_address;
        $parts = explode('.', $ip);
        if (count($parts) == 4) {
            return $parts[0] . '.' . $parts[1] . '.***.***';
        }
        return $ip;
    }
}