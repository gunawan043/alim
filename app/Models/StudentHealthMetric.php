<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentHealthMetric extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
        static::saving(function ($m) {
            if ($m->height_cm && $m->weight_kg) {
                $heightM = $m->height_cm / 100;
                $m->bmi = round($m->weight_kg / ($heightM * $heightM), 2);
                $m->bmi_category = self::categorizeBmi($m->bmi);
            }
        });
    }

    protected $fillable = [
        'student_id',
        'school_id',
        'academic_year_id',
        'record_date',
        'height_cm',
        'weight_kg',
        'bmi',
        'bmi_category',
        'measurement_session',
        'measured_by',
        'notes',
    ];

    protected $casts = [
        'record_date' => 'date',
        'height_cm' => 'integer',
        'weight_kg' => 'integer',
        'bmi' => 'decimal:2',
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

    public function measuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    // ── BMI Categorization ───────────────────────────────────────

    public static function categorizeBmi(float $bmi): string
    {
        return match (true) {
            $bmi < 17.0 => 'sangat_kurang',
            $bmi < 18.5 => 'kurang',
            $bmi < 25.0 => 'normal',
            $bmi < 27.0 => 'lebih',
            default => 'gemuk',
        };
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getBmiCategoryTextAttribute(): string
    {
        return match ($this->bmi_category) {
            'sangat_kurang' => 'Sangat Kurang',
            'kurang' => 'Kurang',
            'normal' => 'Normal',
            'lebih' => 'Lebih',
            'gemuk' => 'Gemuk',
            default => '-',
        };
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

    public function scopeLatest($q)
    {
        return $q->orderByDesc('record_date');
    }

    // ── Sync height/weight ke students ───────────────────────────

    public function syncHeightWeightToStudent(): void
    {
        $studentId = $this->student_id;

        // Latest metric for this student
        $latestMetric = self::where('student_id', $studentId)
            ->whereNotNull('height_cm')
            ->whereNotNull('weight_kg')
            ->latest('record_date')
            ->first();

        // Latest checkup for this student
        $latestCheckup = StudentHealthCheckup::where('student_id', $studentId)
            ->whereNotNull('height_cm')
            ->whereNotNull('weight_kg')
            ->latest('checkup_date')
            ->first();

        // Pick the one with the most recent date
        $height = null;
        $weight = null;

        if ($latestMetric && $latestCheckup) {
            if ($latestCheckup->checkup_date->gte($latestMetric->record_date)) {
                $height = $latestCheckup->height_cm;
                $weight = $latestCheckup->weight_kg;
            } else {
                $height = $latestMetric->height_cm;
                $weight = $latestMetric->weight_kg;
            }
        } elseif ($latestMetric) {
            $height = $latestMetric->height_cm;
            $weight = $latestMetric->weight_kg;
        } elseif ($latestCheckup) {
            $height = $latestCheckup->height_cm;
            $weight = $latestCheckup->weight_kg;
        }

        Student::where('id', $studentId)->update([
            'height' => $height,
            'weight' => $weight,
        ]);
    }
}
