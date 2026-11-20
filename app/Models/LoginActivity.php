<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginActivity extends Model
{
    use HasFactory;

    public const EVENT_LABELS = [
        'login_success' => 'Login Berhasil',
        'login_failed' => 'Login Gagal',
        'login_blocked' => 'Login Diblokir',
        'account_locked' => 'Akun Terkunci',
        'account_unlocked' => 'Akun Dibuka',
        'ip_blocked' => 'IP Diblokir',
        'ip_unblocked' => 'IP Dibuka',
        'otp_failed' => 'OTP Gagal',
        'otp_verified' => 'OTP Terverifikasi',
        '2fa_failed' => 'Verifikasi 2FA Gagal',
        'logout' => 'Logout',
        'password_reset' => 'Reset Password',
        'password_otp_requested' => 'OTP Reset Diminta',
        'password_otp_resent' => 'OTP Reset Dikirim Ulang',
        'password_otp_failed' => 'OTP Reset Gagal',
        'password_otp_verified' => 'OTP Reset Terverifikasi',
        'password_reset_completed' => 'Password Direset',
    ];

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'identity',
        'email',
        'event',
        'reason',
        'ip_address',
        'user_agent',
        'is_suspicious',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'is_suspicious' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('event', ['login_failed', 'login_blocked', 'account_locked', 'ip_blocked', 'otp_failed', '2fa_failed']);
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event] ?? Str::headline((string) $this->event);
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'login_success', 'account_unlocked', 'ip_unblocked', 'otp_verified',
            'password_otp_verified', 'password_reset_completed' => 'success',
            'login_failed', '2fa_failed', 'otp_failed', 'password_otp_failed' => 'danger',
            'account_locked', 'ip_blocked', 'login_blocked' => 'warning',
            'password_otp_requested', 'password_otp_resent' => 'info',
            default => 'secondary',
        };
    }

    public function getDeviceAttribute(): string
    {
        $ua = strtolower((string) $this->user_agent);

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/') => 'Safari',
            str_contains($ua, 'curl/') => 'cURL',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'mac os') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Perangkat',
        };

        return "{$browser} · {$platform}";
    }
}
