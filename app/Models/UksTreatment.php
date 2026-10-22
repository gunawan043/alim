<?php

namespace App\Models;

use App\Models\Uks\UksPatient;
use App\Models\Uks\UksPrescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UksTreatment extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_treatments';

    protected $fillable = [
        'patient_id',
        'performed_by',
        'chief_complaint',
        'subjective',
        'symptoms',
        'vitals',
        'objective',
        'physical_exam',
        'diagnosis',
        'assessment',
        'icd10_code',
        'icd10_name',
        'treatment',
        'plan',
        'disposition',
        'follow_up_date',
        'is_locked',
        'notes',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'performed_by' => 'string',
        'symptoms' => 'array',
        'vitals' => 'array',
        'follow_up_date' => 'date',
        'is_locked' => 'boolean',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function prescriptions()
    {
        return $this->hasMany(UksPrescription::class, 'treatment_id');
    }
}
