<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsDeletion;
use Illuminate\Support\Str;

class DormitoryActivityLog extends Model
{
    use SoftDeletes;
    use LogsDeletion;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'resident_id',
        'dormitory_id',
        'academic_year_id',
        'activity_date',
        'session',
        'data',
        'notes',
        'notify_parent',
        'recorded_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'data'          => 'array',
        'notify_parent' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function resident(): BelongsTo
    {
        return $this->belongsTo(DormitoryResident::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getSessionTextAttribute(): string
    {
        return match ($this->session) {
            'subuh' => 'Subuh',
            'pagi'  => 'Pagi',
            'siang' => 'Siang',
            'sore'  => 'Sore',
            'isya'  => 'Isya',
            'malam' => 'Malam',
            default => ucfirst($this->session ?? ''),
        };
    }
}
