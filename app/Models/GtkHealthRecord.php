<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GtkHealthRecord extends Model
{
    protected $table = 'gtk_health_records';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id',
        'examined_at',
        'weight_kg',
        'height_cm',
        'bmi',
        'waist_circumference',
        'bp_systolic',
        'bp_diastolic',
        'pulse_bpm',
        'temperature_c',
        'resp_rate',
        'oxygen_saturation',
        'fasting_glucose',
        'random_glucose',
        'hba1c',
        'total_cholesterol',
        'hdl',
        'ldl',
        'triglycerides',
        'uric_acid',
        'hemoglobin',
        'leukocytes',
        'thrombocytes',
        'creatinine',
        'sgot',
        'sgpt',
        'lifestyle',
        'notes',
        'examined_by',
        'medical_history_id',
    ];

    protected $casts = [
        'user_id' => 'string',
        'examined_at' => 'datetime',
        'weight_kg' => 'decimal:1',
        'height_cm' => 'decimal:1',
        'bmi' => 'decimal:1',
        'bp_systolic' => 'integer',
        'bp_diastolic' => 'integer',
        'pulse_bpm' => 'integer',
        'temperature_c' => 'decimal:1',
        'resp_rate' => 'integer',
        'oxygen_saturation' => 'integer',
        'fasting_glucose' => 'decimal:2',
        'random_glucose' => 'decimal:2',
        'hba1c' => 'decimal:1',
        'total_cholesterol' => 'decimal:2',
        'hdl' => 'decimal:2',
        'ldl' => 'decimal:2',
        'triglycerides' => 'decimal:2',
        'uric_acid' => 'decimal:2',
        'hemoglobin' => 'decimal:1',
        'leukocytes' => 'decimal:2',
        'thrombocytes' => 'decimal:2',
        'creatinine' => 'decimal:2',
        'sgot' => 'decimal:2',
        'sgpt' => 'decimal:2',
        'lifestyle' => 'array',
        'examined_by' => 'string',
        'medical_history_id' => 'string',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examinedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examined_by');
    }

    public function medicalHistory(): BelongsTo
    {
        return $this->belongsTo(GtkMedicalHistory::class, 'medical_history_id');
    }

    /**
     * Latest health record for a given user.
     */
    public static function latestForUser(string|int $userId): ?self
    {
        if (is_int($userId)) {
            $userId = (string) $userId;
        }

        return self::where('user_id', $userId)
            ->orderByDesc('examined_at')
            ->first();
    }

    /**
     * All records for a user, ordered by exam date.
     */
    public static function forUser(string|int $userId): \Illuminate\Database\Eloquent\Collection
    {
        if (is_int($userId)) {
            $userId = (string) $userId;
        }

        return self::where('user_id', $userId)
            ->orderByDesc('examined_at')
            ->get();
    }

    /**
     * Auto-compute BMI when weight and height are set.
     */
    protected static function booted()
    {
        static::saving(function ($record) {
            if (($record->height_cm ?? null) && ($record->weight_kg ?? null) && $record->height_cm > 0) {
                $heightM = $record->height_cm / 100;
                $record->bmi = round($record->weight_kg / ($heightM * $heightM), 1);
            }
        });
    }

    // ── Compatibility Accessors ─────────────────────────────────
    // These let Blade templates use the simpler names while the DB
    // columns have _suffix_ naming.  They also let old code that
    // references e.g. $r->weight_kg keep working.

    public function getWeightKgAttribute(): ?float
    {
        return $this->getAttribute('weight_kg');
    }

    public function getHeightCmAttribute(): ?float
    {
        return $this->getAttribute('height_cm');
    }

    public function getPulseBpmAttribute(): ?int
    {
        return $this->getAttribute('pulse_bpm');
    }

    public function getTemperatureCAttribute(): ?float
    {
        return $this->getAttribute('temperature_c');
    }

    public function getRespRateAttribute(): ?int
    {
        return $this->getAttribute('resp_rate');
    }

    public function getExaminedAtAttribute()
    {
        return $this->getAttribute('examined_at');
    }
}
