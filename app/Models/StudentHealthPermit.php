<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentHealthPermit extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'school_id',
        'academic_year_id',
        'dormitory_id',
        'permit_type',
        'description',
        'start_date',
        'end_date',
        'rest_days',
        'status',
        'approved_by',
        'approved_at',
        'approval_note',
        'parent_notified',
        'parent_notified_at',
        'parent_notified_by',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'parent_notified_at' => 'datetime',
        'rest_days' => 'integer',
        'parent_notified' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parentNotifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_notified_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getPermitTypeTextAttribute(): string
    {
        return match ($this->permit_type) {
            'sakit_ringan' => 'Sakit Ringan',
            'sakit_sedang' => 'Sakit Sedang',
            'sakit_berat' => 'Sakit Berat',
            'kontrol_dokter' => 'Kontrol Dokter',
            'isolasi' => 'Isolasi',
            default => $this->permit_type,
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'extended' => 'Diperpanjang',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeByStudent($q, $studentId)
    {
        return $q->where('student_id', $studentId);
    }
}
