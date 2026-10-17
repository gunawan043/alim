<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentHealthRecord extends Model
{
    protected $table = 'student_health_records';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id', 'school_id',
        'blood_type', 'height_cm', 'weight_kg', 'bmi',
        'allergies', 'chronic_diseases', 'current_medications',
        'emergency_notes',
        'bpjs_number', 'insurance_provider', 'insurance_number',
        'last_physical_exam_date',
    ];

    protected $casts = [
        'height_cm' => 'integer',
        'weight_kg' => 'integer',
        'bmi' => 'decimal:2',
        'last_physical_exam_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getBloodTypeLabelAttribute(): string
    {
        return match ($this->blood_type) {
            'A' => 'A',
            'B' => 'B',
            'AB' => 'AB',
            'O' => 'O',
            'tidak_diketahui' => 'Tidak Diketahui',
            default => $this->blood_type ?? '-',
        };
    }
}
