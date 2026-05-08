<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryAttendanceRecap extends Model
{
    protected $table = 'dormitory_attendance_recaps';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'room_id',
        'dormitory_id',
        'academic_year_id',
        'semester',
        'recap_month',
        'recap_year',
        'total_hadir',
        'total_izin',
        'total_sakit',
        'total_alpa',
        'total_pulang',
    ];

    protected $casts = [
        'recap_month' => 'integer',
        'recap_year' => 'integer',
        'total_hadir' => 'integer',
        'total_izin' => 'integer',
        'total_sakit' => 'integer',
        'total_alpa' => 'integer',
        'total_pulang' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(DormitoryRoom::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}