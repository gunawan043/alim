<?php

namespace App\Models;

use App\Models\BoardingTimelineEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single source of truth for a student's CURRENT boarding status.
 *
 * Hard invariant: at most one row per student. Enforced by a UNIQUE index
 * on student_id. The boarding_workflow layer (Transitions) is the only
 * writer; UI reads from this table directly.
 *
 * The state machine lives here:
 *
 *   IN_DORM          ↔ CHECKED_OUT  (default)
 *   IN_DORM          → ON_LEAVE       (when permit approved)
 *   ON_LEAVE         → IN_DORM        (when student returns)
 *   IN_DORM          → AT_HOSPITAL    (when hospitalization starts)
 *   AT_HOSPITAL      → IN_DORM        (when recovered)
 *   IN_DORM          → OFFICIAL_ACTIVITY  (school trip, exam outside, etc.)
 *   OFFICIAL_ACTIVITY → IN_DORM       (returned)
 *   anything         → CHECKED_OUT    (admin checkout / dormant)
 *
 * Anything not in this table = CHECKED_OUT.
 */
class StudentBoardingStatus extends Model
{
    protected $table = 'student_boarding_statuses';

    protected $fillable = [
        'student_id',
        'status',
        'dormitory_id',
        'room_id',
        'effective_from',
        'expected_return_at',
        'context_subject_type',
        'context_subject_id',
        'note',
        'changed_by_user_id',
        'last_event_at',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'expected_return_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    // Status codes — exported as constants so flows can reference them.
    public const IN_DORM = 'IN_DORM';
    public const ON_LEAVE = 'ON_LEAVE';
    public const AT_HOSPITAL = 'AT_HOSPITAL';
    public const OFFICIAL_ACTIVITY = 'OFFICIAL_ACTIVITY';
    public const CHECKED_OUT = 'CHECKED_OUT';

    /**
     * @return array<string, list<string>>
     */
    public static function allowedTransitions(): array
    {
        return [
            self::IN_DORM => [
                self::ON_LEAVE,
                self::AT_HOSPITAL,
                self::OFFICIAL_ACTIVITY,
                self::CHECKED_OUT,
            ],
            self::ON_LEAVE => [
                self::IN_DORM,
                self::CHECKED_OUT,
            ],
            self::AT_HOSPITAL => [
                self::IN_DORM,
                self::CHECKED_OUT,
            ],
            self::OFFICIAL_ACTIVITY => [
                self::IN_DORM,
                self::CHECKED_OUT,
            ],
            self::CHECKED_OUT => [
                self::IN_DORM,
            ],
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class, 'room_id');
    }

    public function scopeAtStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAtDormitory(Builder $query, string $dormitoryId): Builder
    {
        return $query->where('dormitory_id', $dormitoryId);
    }

    /**
     * Display color hint for the UI (matches the badge colors CTO described).
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::IN_DORM => 'green',
            self::ON_LEAVE => 'yellow',
            self::AT_HOSPITAL => 'red',
            self::OFFICIAL_ACTIVITY => 'blue',
            self::CHECKED_OUT => 'gray',
            default => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::IN_DORM => 'Di Asrama',
            self::ON_LEAVE => 'Izin Pulang',
            self::AT_HOSPITAL => 'Sakit / Rawat',
            self::OFFICIAL_ACTIVITY => 'Kegiatan Resmi',
            self::CHECKED_OUT => 'Non-aktif',
            default => $this->status,
        };
    }
}
