<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Alumni extends Model
{
    protected $table = 'alumni';

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
        'graduation_year',
        'graduation_certificate_number',
        'graduation_date',
        // Continuer
        'continuing_study_status',
        'higher_education_institution',
        'study_program',
        'higher_education_city',
        'higher_education_year_start',
        // Working
        'working_status',
        'occupation',
        'company_name',
        'company_address',
        'company_phone',
        'company_city',
        'monthly_income',
        'working_year_start',
        // Other
        'further_study_institution',
        'further_study_program',
        // Contact
        'is_contactable',
        'last_contact_date',
        'achievements',
        'tracer_notes',
        'tracer_status',
        'tracer_filled_at',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'last_contact_date' => 'date',
        'tracer_filled_at' => 'datetime',
        'monthly_income' => 'decimal:0',
        'is_contactable' => 'boolean',
        'higher_education_year_start' => 'integer',
        'working_year_start' => 'integer',
    ];

    protected $appends = [
        'tracer_status_text',
        'continuing_study_status_text',
        'working_status_text',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getTracerStatusTextAttribute(): string
    {
        return match ($this->tracer_status) {
            'pending' => 'Belum Diisi',
            'filled' => 'Sudah Diisi',
            'verified' => 'Diverifikasi',
            default => ucfirst($this->tracer_status ?? ''),
        };
    }

    public function getContinuingStudyStatusTextAttribute(): string
    {
        return match ($this->continuing_study_status) {
            'belum' => 'Belum',
            'sedang' => 'Sedang',
            'sudah' => 'Sudah',
            default => ucfirst($this->continuing_study_status ?? ''),
        };
    }

    public function getWorkingStatusTextAttribute(): string
    {
        return match ($this->working_status) {
            'belum' => 'Belum',
            'sedang' => 'Sedang',
            'sudah' => 'Sudah',
            default => ucfirst($this->working_status ?? ''),
        };
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeBySchool($q, $schoolId)
    {
        return $q->where('school_id', $schoolId);
    }

    public function scopeByYear($q, $year)
    {
        return $q->where('graduation_year', $year);
    }

    public function scopePendingTracer($q)
    {
        return $q->where('tracer_status', 'pending');
    }

    public function scopeFilledTracer($q)
    {
        return $q->where('tracer_status', '!=', 'pending');
    }
}
