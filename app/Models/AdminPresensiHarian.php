<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminPresensiHarian extends Model
{
    protected $table = 'admin_presensi_harian';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'study_group_id', 'academic_year_id', 'semester',
        'attendance_date', 'student_id', 'status', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    protected $appends = ['status_text'];

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'hadir'      => 'Hadir',
            'terlambat'  => 'Terlambat',
            'izin'       => 'Izin',
            'sakit'      => 'Sakit',
            'alpa'       => 'Alpa',
            default      => ucfirst($this->status ?? ''),
        };
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeByStudent($q, string $studentId)
    {
        return $q->where('student_id', $studentId);
    }

    public function scopeByStudyGroup($q, string $studyGroupId)
    {
        return $q->where('study_group_id', $studyGroupId);
    }

    public function scopeByDate($q, $date)
    {
        return $q->whereDate('attendance_date', $date);
    }

    public function scopeBySemester($q, string $semester)
    {
        return $q->where('semester', $semester);
    }

    public function scopeByAcademicYear($q, string $academicYearId)
    {
        return $q->where('academic_year_id', $academicYearId);
    }

    public function scopeBetweenDates($q, $start, $end)
    {
        return $q->whereBetween('attendance_date', [$start, $end]);
    }
}