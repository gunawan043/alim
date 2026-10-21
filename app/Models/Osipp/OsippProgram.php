<?php

namespace App\Models\Osipp;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippProgram extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_programs';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OPTIONS = [
        self::STATUS_PLANNED => 'Direncanakan',
        self::STATUS_RUNNING => 'Berjalan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(OsippMember::class, 'responsible_member_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OsippActivity::class, 'program_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OsippDocument::class, 'program_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'warning',
        };
    }
}
