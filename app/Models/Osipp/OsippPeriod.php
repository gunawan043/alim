<?php

namespace App\Models\Osipp;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippPeriod extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_periods';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_ENDED => 'Selesai',
    ];

    protected $casts = [
        'decree_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OsippMember::class, 'period_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', OsippMember::STATUS_ACTIVE);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(OsippProgram::class, 'period_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OsippActivity::class, 'period_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OsippDocument::class, 'period_id');
    }

    public function coachings(): HasMany
    {
        return $this->hasMany(OsippCoaching::class, 'period_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(OsippEvaluation::class, 'period_id');
    }

    public function scopeForSchool($query, ?string $schoolId)
    {
        return $schoolId ? $query->where('school_id', $schoolId) : $query;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_ENDED => 'secondary',
            default => 'warning',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
