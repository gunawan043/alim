<?php

namespace App\Models\Uks;

use App\Models\UksCareEvent;
use App\Models\UksMedicationLog;
use App\Models\UksTreatment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * UksPatient — UKS Patient Registration / Treatment Tracking.
 *
 * Status lifecycle:
 *   menunggu_pemeriksaan → sedang_ditangani → observasi → rawat_uks → selesai
 *                                                ↓ dirujuk_ke_klinik / dirujuk_ke_rumah_sakit
 *   After active care:  istirahat_di_uks → kembali_ke_asrama / kembali_ke_sekolah
 *                                       → dijemput_wali / pulang
 */
class UksPatient extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_patients';

    protected $fillable = [
        'student_id',
        'school_id',
        'academic_year_id',
        'dormitory_id',
        'patient_type',
        'chief_complaint',
        'symptoms',
        'vitals',
        'diagnosis',
        'treatment',
        'medicine_given',
        'medication_details',
        'referred_to_faskes',
        'referral_reason',
        'bed_number',
        'in_bed',
        'taken_bed_at',
        'left_bed_at',
        'status',
        'admitted_at',
        'discharged_at',
        'admitted_by',
        'discharged_by',
        'notes',
    ];

    protected $casts = [
        'vitals' => 'array',
        'symptoms' => 'array',
        'referred_to_faskes' => 'boolean',
        'in_bed' => 'boolean',
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'taken_bed_at' => 'datetime',
        'left_bed_at' => 'datetime',
        'school_id' => 'string',
        'student_id' => 'string',
        'admitted_by' => 'string',
        'discharged_by' => 'string',
    ];

    // ── Status Constants ────────────────────────────────────────

    const STATUS_WAITING = 'menunggu_pemeriksaan';

    const STATUS_TREATED = 'sedang_ditangani';

    const STATUS_OBSERVATION = 'observasi';

    const STATUS_INPATIENT = 'rawat_uks';

    const STATUS_REFERRAL_CLINIC = 'dirujuk_ke_klinik';

    const STATUS_REFERRAL_HOSPITAL = 'dirujuk_ke_rumah_sakit';

    const STATUS_COMPLETED = 'selesai';

    /** Statuses considered "active" (receiving care). */
    public static array $activeStatuses = [
        self::STATUS_WAITING,
        self::STATUS_TREATED,
        self::STATUS_OBSERVATION,
        self::STATUS_INPATIENT,
    ];

    /** Post-treatment statuses that still allow action buttons. */
    public static array $postTreatmentStatuses = [
        self::STATUS_RESTING_UKS,
    ];

    // ── Discharge / Return Statuses ────────────────────────────

    const STATUS_RESTING_UKS = 'istirahat_di_uks';

    const STATUS_RETURN_DORM = 'kembali_ke_asrama';

    const STATUS_RETURN_SCHOOL = 'kembali_ke_sekolah';

    const STATUS_PICKED_UP = 'dijemput_wali';

    const STATUS_LEAVING = 'pulang';

    // ── Relationships ───────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class, 'student_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AcademicYear::class);
    }

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dormitory::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'admitted_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'discharged_by');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(UksTreatment::class, 'patient_id');
    }

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(UksMedicationLog::class, 'patient_id');
    }

    public function careEvents(): HasMany
    {
        return $this->hasMany(UksCareEvent::class, 'patient_id');
    }

    public function bedAssignments(): HasMany
    {
        return $this->hasMany(\App\Models\UksBedAssignment::class, 'patient_id');
    }

    public function treatmentNotes(): HasMany
    {
        return $this->hasMany(\App\Models\Uks\UksTreatmentNote::class, 'patient_id');
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(\App\Models\Uks\UksMedicationAdministration::class, 'patient_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(\App\Models\Uks\UksStatusHistory::class, 'patient_id');
    }

    /**
     * The most recent active bed assignment for this patient.
     */
    public function currentBedAssignment(): HasOne
    {
        return $this->hasOne(\App\Models\UksBedAssignment::class, 'patient_id')
            ->where('status', 'assigned')
            ->whereNull('released_at')
            ->latestOfMany();
    }

    /**
     * Calculate the duration of stay in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->admitted_at) {
            return null;
        }
        $end = $this->discharged_at ?? now();

        return $this->admitted_at->diffInMinutes($end, false);
    }

    /**
     * Human-readable duration string.
     */
    public function getDurationFormattedAttribute(): ?string
    {
        $minutes = $this->duration;
        if ($minutes === null) {
            return null;
        }
        if ($minutes < 60) {
            return "{$minutes} menit";
        }
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining > 0
            ? "{$hours} jam {$remaining} menit"
            : "{$hours} jam";
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_WAITING,
            self::STATUS_TREATED,
            self::STATUS_OBSERVATION,
            self::STATUS_INPATIENT,
        ]);
    }

    public function scopeInProgress($query)
    {
        // Same as active but excludes "menunggu"
        return $query->whereIn('status', [
            self::STATUS_TREATED,
            self::STATUS_OBSERVATION,
            self::STATUS_INPATIENT,
        ]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->whereHas('student', fn ($q) => $q->where('gender', $gender));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('admitted_at', now()->toDateString());
    }

    /**
     * Scope for patients resting in UKS (bed only, no active treatment).
     */
    public function scopeResting($query)
    {
        return $query->where('status', self::STATUS_RESTING_UKS);
    }

    /**
     * Scope for patients returning to dormitory or school.
     */
    public function scopeReturning($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RETURN_DORM,
            self::STATUS_RETURN_SCHOOL,
        ]);
    }

    /**
     * Scope for patients being picked up by guardian.
     */
    public function scopePickedUp($query)
    {
        return $query->where('status', self::STATUS_PICKED_UP);
    }

    // ── Facade Methods ──────��───────────────────────────────────

    /**
     * Check if patient is still in active treatment.
     */
    public function isActive(): bool
    {
        return $this->active()->count() > 0 || in_array($this->status, [
            self::STATUS_WAITING,
            self::STATUS_TREATED,
            self::STATUS_OBSERVATION,
            self::STATUS_INPATIENT,
        ]);
    }

    /**
     * Check if patient requires bed placement.
     */
    public function needsBed(): bool
    {
        return $this->status === self::STATUS_INPATIENT;
    }

    /**
     * Get all patients requiring bed (rawat_uks) with their assignments.
     */
    public static function getPatientsNeedingBeds(?string $schoolId = null): Collection
    {
        $query = static::where('status', self::STATUS_INPATIENT)
            ->with(['student.dormitory', 'currentBedAssignment.bed']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get();
    }
}
