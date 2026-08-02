<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'email',
        'attempts',
        'last_attempt_at',
        'locked_until',
        'locked_reason',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isLockedByIp(): bool
    {
        return $this->isLocked() && $this->locked_reason === 'ip';
    }

    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', strtolower($email));
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('locked_until')
                ->orWhere('locked_until', '>', now());
        });
    }

    public function recordAttempt(bool $emailFound): void
    {
        $this->attempts = $this->attempts + 1; // controller already set attempts=N, this makes it N+1
        $this->last_attempt_at = now();

        if ($this->attempts >= 9) {
            $this->locked_until = now()->addHours(24);
            $this->locked_reason = $emailFound ? 'account' : 'ip';
        }

        $this->save();
    }
}
