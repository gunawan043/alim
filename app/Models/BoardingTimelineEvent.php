<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BoardingTimelineEvent extends Model
{
    protected $table = 'boarding_timeline_events';

    protected $keyType = 'string';

    public $incrementing = false;

    public const TYPE_CHECK_IN = 'check_in';

    public const TYPE_CHECK_OUT = 'check_out';

    public const TYPE_ROOM_TRANSFER = 'room_transfer';

    public const TYPE_LEAVE_APPROVED = 'leave_approved';

    public const TYPE_LEAVE_STARTED = 'leave_started';

    public const TYPE_LEAVE_OVERDUE = 'leave_overdue';

    public const TYPE_LEAVE_RETURNED = 'leave_returned';

    public const TYPE_RETURNED = 'returned';

    public const TYPE_HOSPITALIZED = 'hospitalized';

    public const TYPE_RECOVERED = 'recovered';

    public const TYPE_VISIT_APPROVED = 'visit_approved';

    public const TYPE_VISIT_REJECTED = 'visit_rejected';

    public const TYPE_VISIT_CHECK_IN = 'visit_check_in';

    public const TYPE_VISIT_CHECK_OUT = 'visit_check_out';

    public const TYPE_VIOLATION = 'violation';

    public const TYPE_REWARD = 'reward';

    public const TYPE_EXPELLED = 'expelled';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_PERMIT_SUBMITTED = 'permit_submitted';

    public const TYPE_PERMIT_REJECTED = 'permit_rejected';

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'event_type',
        'student_id',
        'dormitory_id',
        'room_id',
        'boarding_policy_id',
        'event_at',
        'subject_refs',
        'payload',
        'is_special_permission',
        'recorded_by',
        'source_actor_id',
        'source_system',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'subject_refs' => 'array',
        'payload' => 'array',
        'is_special_permission' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(BoardingPolicy::class, 'boarding_policy_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
