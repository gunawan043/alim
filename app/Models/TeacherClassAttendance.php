<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeacherClassAttendance extends Model
{
    protected $table = 'teacher_class_attendances';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'study_group_id',
        'jadwal_kbm_id',
        'teacher_id',
        'qr_token_id',
        'attendance_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'scheduled_break_time',
        'actual_time_in',
        'actual_time_out',
        'late_minutes',
        'early_leave_minutes',
        'duration_minutes',
        'status_masuk',
        'status_keluar',
        'checkout_qr_token_id',
        'is_substituted',
        'recorded_by',
        'notes',
        'verified_by_waka_at',
        'verified_by_waka',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'actual_time_in' => 'datetime',
        'actual_time_out' => 'datetime',
        'is_substituted' => 'boolean',
        'scheduled_break_time' => 'datetime',
        'verified_by_waka_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function jadwalKbm(): BelongsTo
    {
        return $this->belongsTo(JadwalKbm::class, 'jadwal_kbm_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedByWaka(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_waka');
    }

    public function qrToken(): BelongsTo
    {
        return $this->belongsTo(QrClassToken::class, 'qr_token_id');
    }

    public function checkoutQrToken(): BelongsTo
    {
        return $this->belongsTo(QrClassToken::class, 'checkout_qr_token_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', today());
    }

    public function scopeByTeacher($query, string $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByStudyGroup($query, string $studyGroupId)
    {
        return $query->where('study_group_id', $studyGroupId);
    }

    public function scopeByJadwalKbm($query, string $jadwalKbmId)
    {
        return $query->where('jadwal_kbm_id', $jadwalKbmId);
    }

    public function scopeNotCheckedOut($query)
    {
        return $query->where('status_keluar', 'belum_keluar')
                     ->whereNull('actual_time_out');
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getStatusMasukLabelAttribute(): string
    {
        return match ($this->status_masuk) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpa' => 'Alpa',
            'cuti' => 'Cuti',
            'dinas_luar' => 'Dinas Luar',
            default => $this->status_masuk,
        };
    }

    public function getStatusKeluarLabelAttribute(): string
    {
        return match ($this->status_keluar) {
            'selesai' => 'Selesai',
            'keluar_cepat' => 'Pulang Awal',
            'belum_keluar' => 'Belum Keluar',
            'tidak_keluar' => 'Tidak Keluar',
            default => $this->status_keluar,
        };
    }

    public function getLateMinutesAttribute($value)
    {
        if ($this->actual_time_in && $this->scheduled_start_time) {
            $in = strtotime($this->actual_time_in);
            $start = strtotime($this->scheduled_start_time);
            $diff = max(0, $in - $start);
            return (int) round($diff / 60);
        }
        return $value ?? 0;
    }

    public function getEarlyLeaveMinutesAttribute($value)
    {
        if ($this->actual_time_out && $this->scheduled_end_time) {
            $out = strtotime($this->actual_time_out);
            $end = strtotime($this->scheduled_end_time);
            $diff = max(0, $end - $out);
            return (int) round($diff / 60);
        }
        return $value ?? 0;
    }

    public function getDurationMinutesAttribute($value)
    {
        if ($this->actual_time_in && $this->actual_time_out) {
            $in = strtotime($this->actual_time_in);
            $out = strtotime($this->actual_time_out);
            return max(0, (int) round(($out - $in) / 60));
        }
        return $value ?? 0;
    }
}
