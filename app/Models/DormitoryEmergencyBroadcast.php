<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DormitoryEmergencyBroadcast extends Model
{
    use LogsDeletion;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'dormitory_id',
        'title',
        'content',
        'severity',
        'broadcast_via',
        'ack_required',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'ack_required' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getSeverityTextAttribute(): string
    {
        return match ($this->severity) {
            'info' => 'Info',
            'warning' => 'Peringatan',
            'urgent' => 'Urgent',
            'emergency' => 'Darurat',
            default => ucfirst($this->severity ?? ''),
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'info' => 'secondary',
            'warning' => 'warning',
            'urgent' => 'danger',
            'emergency' => 'dark',
            default => 'secondary',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
