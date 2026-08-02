<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksTreatmentNote — Perkembangan / catatan observasi pasien selama rawat.
 *
 * Setiap catatan waktu tertentu selama pasien berstatus rawat_uks / istirahat_di_uks.
 */
class UksTreatmentNote extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $table = 'uks_treatment_notes';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'patient_id',
        'recorded_by',
        'recorded_at',
        'note',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'recorded_by' => 'string',
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId)->orderBy('recorded_at');
    }
}
