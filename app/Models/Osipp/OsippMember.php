<?php

namespace App\Models\Osipp;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippMember extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_members';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_OPTIONS = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_INACTIVE => 'Nonaktif',
    ];

    public const ROLE_PENGURUS = 'pengurus';

    public const ROLE_ANGGOTA = 'anggota';

    public const ROLE_OPTIONS = [
        self::ROLE_PENGURUS => 'Pengurus',
        self::ROLE_ANGGOTA => 'Anggota',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sort_order' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsiblePrograms(): HasMany
    {
        return $this->hasMany(OsippProgram::class, 'responsible_member_id');
    }

    public function responsibleActivities(): HasMany
    {
        return $this->hasMany(OsippActivity::class, 'responsible_member_id');
    }

    public function coachingAttendances(): HasMany
    {
        return $this->hasMany(OsippCoachingAttendance::class, 'member_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_OPTIONS[$this->member_role] ?? ucfirst((string) $this->member_role);
    }
}
