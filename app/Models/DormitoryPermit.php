<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryPermit extends Model
{
    protected $table = 'dormitory_permits';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'dormitory_id',
        'room_id',
        'academic_year_id',
        'permit_type',
        'destination',
        'purpose',
        'departure_datetime',
        'expected_return_datetime',
        'actual_return_datetime',
        'companion_name',
        'companion_relation',
        'companion_phone',
        'companion_is_mahrom',
        'mahrom_id',
        'status',
        'secondary_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'document_path',
        'overdue_notified_count',
        'overdue_notified_at',
        'escalation_triggered_at',
        'created_by',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'expected_return_datetime' => 'datetime',
        'actual_return_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'escalation_triggered_at' => 'datetime',
        'overdue_notified_count' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mahrom(): BelongsTo
    {
        return $this->belongsTo(StudentMahrom::class);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getPermitTypeTextAttribute(): string
    {
        return match ($this->permit_type) {
            'pulang'           => 'Pulang',
            'keluar_kota'      => 'Keluar Kota',
            'berobat'          => 'Berobat',
            'sakit'            => 'Sakit',
            'keperluan_keluarga' => 'Keperluan Keluarga',
            'lainnya'          => ' Lainnya',
            default            => ucfirst($this->permit_type ?? ''),
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            'returned'  => 'Sudah Pulang',
            'overdue'   => 'Terlambat',
            default     => ucfirst($this->status ?? ''),
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'approved'
            && $this->actual_return_datetime === null
            && $this->expected_return_datetime->isPast();
    }
}