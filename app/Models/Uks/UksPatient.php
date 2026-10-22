<?php

namespace App\Models\Uks;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\School;
use App\Models\Student;
use App\Models\UksBedAssignment;
use App\Models\UksCareEvent;
use App\Models\UksMedicationLog;
use App\Models\UksTreatment;
use App\Models\User;
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
        'medical_record_no',
        'queue_number',
        'queue_date',
        'queue_status',
        'triage_level',
        'care_location',
        'location_notes',
        'location_updated_at',
        'location_updated_by',
        'arrived_at',
        'called_at',
        'screened_at',
        'completed_at',
        'allergies',
        'follow_up_date',
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
        'queue_date' => 'date',
        'follow_up_date' => 'date',
        'arrived_at' => 'datetime',
        'called_at' => 'datetime',
        'screened_at' => 'datetime',
        'completed_at' => 'datetime',
        'location_updated_at' => 'datetime',
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
        self::STATUS_RESTING_UKS,
    ];

    /** Post-treatment statuses that still allow action buttons. */
    public static array $postTreatmentStatuses = [
        self::STATUS_RESTING_UKS,
    ];

    public static array $statusLabels = [
        self::STATUS_WAITING => 'Menunggu Pemeriksaan',
        self::STATUS_TREATED => 'Sedang Ditangani',
        self::STATUS_OBSERVATION => 'Observasi',
        self::STATUS_INPATIENT => 'Rawat UKS',
        self::STATUS_RESTING_UKS => 'Istirahat di UKS',
        self::STATUS_RETURN_DORM => 'Kembali ke Asrama',
        self::STATUS_RETURN_SCHOOL => 'Kembali ke Sekolah',
        self::STATUS_PICKED_UP => 'Dijemput Wali',
        self::STATUS_LEAVING => 'Pulang',
        self::STATUS_REFERRAL_CLINIC => 'Dirujuk ke Klinik',
        self::STATUS_REFERRAL_HOSPITAL => 'Dirujuk ke RS',
        self::STATUS_COMPLETED => 'Selesai',
    ];

    // ── Discharge / Return Statuses ────────────────────────────

    const STATUS_RESTING_UKS = 'istirahat_di_uks';

    const STATUS_RETURN_DORM = 'kembali_ke_asrama';

    const STATUS_RETURN_SCHOOL = 'kembali_ke_sekolah';

    const STATUS_PICKED_UP = 'dijemput_wali';

    const STATUS_LEAVING = 'pulang';

    // ── Queue / Triage Constants ────────────────────────────────

    const QUEUE_WAITING = 'menunggu';

    const QUEUE_CALLED = 'dipanggil';

    const QUEUE_IN_PROGRESS = 'pemeriksaan';

    const QUEUE_PHARMACY = 'farmasi';

    const QUEUE_DONE = 'selesai';

    const QUEUE_CANCELLED = 'batal';

    public static array $queueStatuses = [
        self::QUEUE_WAITING,
        self::QUEUE_CALLED,
        self::QUEUE_IN_PROGRESS,
        self::QUEUE_PHARMACY,
        self::QUEUE_DONE,
        self::QUEUE_CANCELLED,
    ];

    public static array $queueLabels = [
        self::QUEUE_WAITING => 'Menunggu',
        self::QUEUE_CALLED => 'Dipanggil',
        self::QUEUE_IN_PROGRESS => 'Dalam Pemeriksaan',
        self::QUEUE_PHARMACY => 'Menunggu Obat',
        self::QUEUE_DONE => 'Selesai',
        self::QUEUE_CANCELLED => 'Batal',
    ];

    const TRIAGE_P1 = 'P1';

    const TRIAGE_P2 = 'P2';

    const TRIAGE_P3 = 'P3';

    public static array $triageLabels = [
        self::TRIAGE_P1 => 'P1 — Darurat',
        self::TRIAGE_P2 => 'P2 — Mendesak',
        self::TRIAGE_P3 => 'P3 — Non-urgent',
    ];

    public static array $triageColors = [
        self::TRIAGE_P1 => 'danger',
        self::TRIAGE_P2 => 'warning',
        self::TRIAGE_P3 => 'success',
    ];

    // ── Lokasi Perawatan ───────────────────────────────────────

    const LOCATION_ASRAMA = 'asrama';

    const LOCATION_UKS = 'uks';

    const LOCATION_SEKOLAH = 'sekolah';

    const LOCATION_RUMAH = 'rumah';

    const LOCATION_FASKES = 'faskes';

    const LOCATION_LAINNYA = 'lainnya';

    public static array $careLocationLabels = [
        self::LOCATION_ASRAMA => 'Dirawat di Asrama',
        self::LOCATION_UKS => 'Dirawat di UKS / Ranjang',
        self::LOCATION_SEKOLAH => 'Tetap ke Sekolah',
        self::LOCATION_RUMAH => 'Pulang / Di Rumah',
        self::LOCATION_FASKES => 'Dirujuk ke Faskes',
        self::LOCATION_LAINNYA => 'Lainnya',
    ];

    public static array $careLocationShortLabels = [
        self::LOCATION_ASRAMA => 'Asrama',
        self::LOCATION_UKS => 'UKS',
        self::LOCATION_SEKOLAH => 'Sekolah',
        self::LOCATION_RUMAH => 'Rumah',
        self::LOCATION_FASKES => 'Faskes',
        self::LOCATION_LAINNYA => 'Lainnya',
    ];

    public static array $careLocationColors = [
        self::LOCATION_ASRAMA => 'primary',
        self::LOCATION_UKS => 'danger',
        self::LOCATION_SEKOLAH => 'success',
        self::LOCATION_RUMAH => 'secondary',
        self::LOCATION_FASKES => 'warning',
        self::LOCATION_LAINNYA => 'info',
    ];

    public static array $careLocationIcons = [
        self::LOCATION_ASRAMA => 'ri-home-heart-line',
        self::LOCATION_UKS => 'ri-hotel-bed-line',
        self::LOCATION_SEKOLAH => 'ri-school-line',
        self::LOCATION_RUMAH => 'ri-home-4-line',
        self::LOCATION_FASKES => 'ri-hospital-line',
        self::LOCATION_LAINNYA => 'ri-map-pin-line',
    ];

    public static array $careLocationDescriptions = [
        self::LOCATION_ASRAMA => 'Santri dirawat/diistirahatkan di asrama',
        self::LOCATION_UKS => 'Santri menempati ranjang/ruang UKS',
        self::LOCATION_SEKOLAH => 'Santri tetap mengikuti kegiatan sekolah',
        self::LOCATION_RUMAH => 'Santri pulang ke rumah / dijemput wali',
        self::LOCATION_FASKES => 'Santri dirujuk ke klinik/rumah sakit',
        self::LOCATION_LAINNYA => 'Lokasi lain (isolasi, pindah, dll.)',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
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

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function locationUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'location_updated_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
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
        return $this->hasMany(UksBedAssignment::class, 'patient_id');
    }

    public function treatmentNotes(): HasMany
    {
        return $this->hasMany(UksTreatmentNote::class, 'patient_id');
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(UksMedicationAdministration::class, 'patient_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(UksStatusHistory::class, 'patient_id');
    }

    public function locationLogs(): HasMany
    {
        return $this->hasMany(UksPatientLocationLog::class, 'patient_id')->orderByDesc('recorded_at');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(UksPrescription::class, 'patient_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(UksReferral::class, 'patient_id');
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(UksTreatment::class, 'patient_id')->orderByDesc('created_at');
    }

    public function latestEncounter(): HasOne
    {
        return $this->hasOne(UksTreatment::class, 'patient_id')->latestOfMany('created_at');
    }

    /**
     * The most recent active bed assignment for this patient.
     */
    public function currentBedAssignment(): HasOne
    {
        return $this->hasOne(UksBedAssignment::class, 'patient_id')
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

    public function getQueueLabelAttribute(): string
    {
        return self::$queueLabels[$this->queue_status] ?? ucfirst((string) $this->queue_status);
    }

    public function getCareLocationLabelAttribute(): string
    {
        return self::$careLocationLabels[$this->care_location] ?? '-';
    }

    public function getCareLocationShortLabelAttribute(): string
    {
        return self::$careLocationShortLabels[$this->care_location] ?? '-';
    }

    public function getCareLocationColorAttribute(): string
    {
        return self::$careLocationColors[$this->care_location] ?? 'secondary';
    }

    public function getCareLocationIconAttribute(): string
    {
        return self::$careLocationIcons[$this->care_location] ?? 'ri-map-pin-line';
    }

    public function getCareLocationDescriptionAttribute(): string
    {
        return self::$careLocationDescriptions[$this->care_location] ?? '-';
    }

    public function getTriageLabelAttribute(): string
    {
        return self::$triageLabels[$this->triage_level] ?? '-';
    }

    public function getTriageColorAttribute(): string
    {
        return self::$triageColors[$this->triage_level] ?? 'secondary';
    }

    public function getWaitingMinutesAttribute(): ?int
    {
        if (! $this->arrived_at) {
            return null;
        }

        $end = $this->screened_at ?? $this->called_at ?? now();

        return max(0, $this->arrived_at->diffInMinutes($end, false));
    }

    public function getWaitingLabelAttribute(): string
    {
        $minutes = $this->waiting_minutes;

        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        return intdiv($minutes, 60).' jam '.($minutes % 60).' menit';
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_WAITING,
            self::STATUS_TREATED,
            self::STATUS_OBSERVATION,
            self::STATUS_INPATIENT,
            self::STATUS_RESTING_UKS,
            'aktif',
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

    public function scopeTodayQueue($query)
    {
        return $query->whereDate('queue_date', now()->toDateString());
    }

    public function scopeQueueActive($query)
    {
        return $query->whereIn('queue_status', [
            self::QUEUE_WAITING,
            self::QUEUE_CALLED,
            self::QUEUE_IN_PROGRESS,
            self::QUEUE_PHARMACY,
        ]);
    }

    public function scopeByCareLocation($query, string $location)
    {
        return $query->where('care_location', $location);
    }

    // ── Facade Methods ──────��───────────────────────────────────

    /**
     * Check if patient is still in active treatment.
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_WAITING,
            self::STATUS_TREATED,
            self::STATUS_OBSERVATION,
            self::STATUS_INPATIENT,
            self::STATUS_RESTING_UKS,
            'aktif',
        ], true);
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
            ->with(['student.activeDormitoryResident.dormitory', 'currentBedAssignment.bed']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->get();
    }
}
