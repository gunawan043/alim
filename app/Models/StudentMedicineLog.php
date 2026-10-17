<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentMedicineLog extends Model
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
        'inventory_id',
        'academic_year_id',
        'log_date',
        'time_given',
        'quantity_given',
        'dosage',
        'purpose',
        'administered_by',
        'follow_up_date',
        'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'follow_up_date' => 'date',
        'quantity_given' => 'decimal:2',
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

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(StudentMedicineInventory::class, 'inventory_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeByStudent($q, $studentId)
    {
        return $q->where('student_id', $studentId);
    }

    public function scopeByAcademicYear($q, $yearId)
    {
        return $q->where('academic_year_id', $yearId);
    }
}
