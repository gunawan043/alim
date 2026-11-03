<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityIncident extends SecurityModel
{
    protected $table = 'security_incidents';

    public const STATUS_NEW = 'baru';

    public const STATUS_PROCESSING = 'diproses';

    public const STATUS_INVESTIGATING = 'investigasi';

    public const STATUS_CLOSED = 'selesai';

    public const STATUS_OPTIONS = [
        self::STATUS_NEW => 'Baru',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_INVESTIGATING => 'Investigasi',
        self::STATUS_CLOSED => 'Selesai',
    ];

    public const SEVERITY_OPTIONS = [
        'ringan' => 'Ringan',
        'sedang' => 'Sedang',
        'berat' => 'Berat',
        'kritis' => 'Kritis',
    ];

    protected $fillable = [
        'incident_type_id', 'post_id', 'zone_id', 'reported_by', 'assigned_to',
        'closed_by', 'code', 'title', 'description', 'location', 'occurred_at',
        'severity', 'status', 'resolution', 'closed_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(SecurityIncidentType::class, 'incident_type_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(SecurityZone::class, 'zone_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(SecurityIncidentAction::class, 'incident_id')->latest();
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_CLOSED => 'success',
            self::STATUS_INVESTIGATING => 'warning',
            self::STATUS_PROCESSING => 'info',
            default => 'secondary',
        };
    }

    public function severityLabel(): string
    {
        return self::SEVERITY_OPTIONS[$this->severity] ?? $this->severity;
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'kritis' => 'danger',
            'berat' => 'warning',
            'sedang' => 'info',
            default => 'secondary',
        };
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CLOSED);
    }
}
