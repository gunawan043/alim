<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtkHealthRecord extends Model
{
    protected $table = 'gtk_health_records';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'examined_at',
        'check_date',
        'blood_pressure',
        'pulse',
        'temperature',
        'respiration_rate',
        'height',
        'weight',
        'blood_sugar_postprandial',
        'sgot_ast',
        'sgpt_alt',
        'bun',
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
        'recorded_by',
        'source',
        'medical_history_id',
    ];

    protected $casts = [
        'user_id' => 'string',
        'examined_at' => 'datetime',
        'check_date' => 'date',
        'weight' => 'decimal:1',
        'height' => 'decimal:1',
        'pulse' => 'integer',
        'temperature' => 'decimal:1',
        'respiration_rate' => 'integer',
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
    public static function forUser(string|int $userId): Collection
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
            $aliases = [
                'examined_at' => 'check_date',
                'weight_kg' => 'weight',
                'height_cm' => 'height',
                'pulse_bpm' => 'pulse',
                'temperature_c' => 'temperature',
                'resp_rate' => 'respiration_rate',
            ];

            foreach ($aliases as $modern => $legacy) {
                if (empty($record->{$modern}) && ! empty($record->{$legacy})) {
                    $record->{$modern} = $record->{$legacy};
                }

                if (empty($record->{$legacy}) && ! empty($record->{$modern})) {
                    $record->{$legacy} = $record->{$modern};
                }
            }

            $height = $record->height_cm ?: $record->height;
            $weight = $record->weight_kg ?: $record->weight;

            if ($height && $weight && $height > 0) {
                $heightM = $height / 100;
                $record->bmi = round($weight / ($heightM * $heightM), 1);
            }
        });
    }

    // ── Compatibility Accessors ─────────────────────────────────
    // These let Blade templates use the simpler names while the DB
    // columns have _suffix_ naming.  They also let old code that
    // references e.g. $r->weight_kg keep working.

}
