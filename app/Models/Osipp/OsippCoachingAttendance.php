<?php

namespace App\Models\Osipp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsippCoachingAttendance extends OsippModel
{
    protected $table = 'osipp_coaching_attendances';

    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_EXCUSED = 'excused';

    public const STATUS_OPTIONS = [
        self::STATUS_PRESENT => 'Hadir',
        self::STATUS_ABSENT => 'Tidak Hadir',
        self::STATUS_EXCUSED => 'Izin',
    ];

    public function coaching(): BelongsTo
    {
        return $this->belongsTo(OsippCoaching::class, 'coaching_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(OsippMember::class, 'member_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }
}
