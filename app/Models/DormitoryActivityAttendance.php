<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryActivityAttendance extends Model
{
    protected $table = 'dormitory_activity_attendances';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'activity_id', 'resident_id', 'student_id', 'room_id',
        'status', 'notes', 'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(DormitoryActivity::class, 'activity_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(DormitoryResident::class, 'resident_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
