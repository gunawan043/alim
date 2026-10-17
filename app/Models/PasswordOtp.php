<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordOtp extends Model
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
        'otp_hash',
        'expires_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'expires_at' => 'datetime',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // SCOPES
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // OTP MANAGEMENT
    public function isValid()
    {
        return $this->expires_at > now();
    }

    public function isExpired()
    {
        return $this->expires_at <= now();
    }

    public static function generateOtp($userId, $expiryMinutes = 10)
    {
        // Hapus OTP lama untuk user ini
        self::where('user_id', $userId)->delete();

        // Generate OTP 6 digit
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedOtp = Hash::make($otp);

        $passwordOtp = self::create([
            'user_id' => $userId,
            'otp_hash' => $hashedOtp,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        // Simpan OTP plaintext sementara
        $passwordOtp->plain_otp = $otp;

        return $passwordOtp;
    }

    public static function verifyOtp($userId, $otp)
    {
        $otpRecord = self::where('user_id', $userId)
            ->valid()
            ->first();

        if (! $otpRecord) {
            return false;
        }

        if (Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->delete();

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'PASSWORD_OTP_VERIFIED',
                'table_name' => 'password_otps',
                'record_id' => $otpRecord->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return true;
        }

        return false;
    }

    public static function cleanupExpired()
    {
        self::expired()->delete();
    }

    // ACCESSORS
    public function getMaskedOtpAttribute()
    {
        return '***'.substr($this->otp_hash, -3);
    }
}
