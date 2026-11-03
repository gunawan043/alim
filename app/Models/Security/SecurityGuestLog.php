<?php

namespace App\Models\Security;

use App\Models\Dormitory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityGuestLog extends SecurityModel
{
    protected $table = 'security_guest_logs';

    public const STATUS_INSIDE = 'di_dalam';

    public const STATUS_OUT = 'keluar';

    public const ID_TYPES = [
        'ktp' => 'KTP',
        'sim' => 'SIM',
        'kartu_pelajar' => 'Kartu Pelajar',
        'paspor' => 'Paspor',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'post_id', 'dormitory_id', 'student_id', 'code', 'visitor_name', 'id_type',
        'id_number', 'phone', 'origin', 'institution', 'purpose', 'person_to_meet',
        'relationship', 'visitor_count', 'vehicle_plate', 'card_number',
        'badge_printed_at', 'check_in_at', 'check_out_at', 'status', 'notes',
        'recorded_by', 'checked_out_by',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'badge_printed_at' => 'datetime',
        'visitor_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class, 'dormitory_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isInside(): bool
    {
        return $this->status === self::STATUS_INSIDE;
    }

    public function durationLabel(): string
    {
        $end = $this->check_out_at ?? now();
        $minutes = (int) $this->check_in_at?->diffInMinutes($end);

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours.' jam'.($rest > 0 ? ' '.$rest.' menit' : '');
    }

    public function scopeInside(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INSIDE);
    }
}
