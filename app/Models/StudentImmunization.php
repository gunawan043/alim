<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentImmunization extends Model
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
        'immunization_type',
        'vaccine_name',
        'date_given',
        'age_at_vaccination_days',
        'place',
        'batch_number',
        'side_effects',
        'medical_staff',
        'notes',
    ];

    protected $casts = [
        'date_given' => 'date',
        'age_at_vaccination_days' => 'integer',
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

    // ── Accessors ───────────────────────────────────────────────

    public function getImmunizationTypeTextAttribute(): string
    {
        $labels = [
            'BCG' => 'BCG',
            'Polio_1' => 'Polio 1',
            'Polio_2' => 'Polio 2',
            'Polio_3' => 'Polio 3',
            'Polio_4' => 'Polio 4',
            'DPT_HB_Hib_1' => 'DPT-HB-Hib 1',
            'DPT_HB_Hib_2' => 'DPT-HB-Hib 2',
            'DPT_HB_Hib_3' => 'DPT-HB-Hib 3',
            'Campak_MR' => 'Campak/MR',
            'MR_2' => 'MR 2',
            'Hepatitis_B' => 'Hepatitis B',
            'TT_1' => 'TT 1',
            'TT_2' => 'TT 2',
            'TT_3' => 'TT 3',
            'TT_4' => 'TT 4',
            'TT_5' => 'TT 5',
            'Covid19' => 'Covid-19',
            'Influenza' => 'Influenza',
            'Japanese_Encephalitis' => 'Japanese Encephalitis',
            'lainnya' => 'Lainnya',
        ];

        return $labels[$this->immunization_type] ?? $this->immunization_type;
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeByStudent($q, $studentId)
    {
        return $q->where('student_id', $studentId);
    }
}
