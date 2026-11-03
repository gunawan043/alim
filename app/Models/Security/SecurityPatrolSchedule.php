<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityPatrolSchedule extends SecurityModel
{
    protected $table = 'security_patrol_schedules';

    public const STATUS_PLANNED = 'dijadwalkan';

    public const STATUS_RUNNING = 'berjalan';

    public const STATUS_DONE = 'selesai';

    public const STATUS_MISSED = 'terlewat';

    public const STATUS_OPTIONS = [
        self::STATUS_PLANNED => 'Dijadwalkan',
        self::STATUS_RUNNING => 'Berjalan',
        self::STATUS_DONE => 'Selesai',
        self::STATUS_MISSED => 'Terlewat',
    ];

    protected $fillable = ['team_id', 'shift_id', 'post_id', 'code', 'patrol_date', 'status', 'notes', 'created_by'];

    protected $casts = ['patrol_date' => 'date'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(SecurityTeam::class, 'team_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(SecurityShift::class, 'shift_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SecurityPatrolLog::class, 'schedule_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DONE => 'success',
            self::STATUS_RUNNING => 'primary',
            self::STATUS_MISSED => 'danger',
            default => 'secondary',
        };
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('patrol_date', today());
    }
}
