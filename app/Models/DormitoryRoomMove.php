<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryRoomMove extends Model
{
    protected $table = 'dormitory_room_moves';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'from_room_id',
        'to_room_id',
        'dormitory_id',
        'academic_year_id',
        'move_date',
        'reason',
        'move_type',
        'approved_by',
        'approval_status',
        'notes',
    ];

    protected $casts = [
        'move_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class, 'from_room_id');
    }

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class, 'to_room_id');
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
