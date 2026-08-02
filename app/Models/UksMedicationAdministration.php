<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksMedicationAdministration — Histori pemberian obat yang lebih lengkap
 * untuk mendukung Status Perawatan (memiliki field quantity/jumlah, jam pemberian).
 *
 * Berbeda dari UksMedicationLog yang hanya mencatat obat + dosis + route.
 */
class UksMedicationAdministration extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $table = 'uks_medication_administrations';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'patient_id',
        'administered_by',
        'medicine_name',
        'dosage',
        'quantity',
        'given_at',
        'notes',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'administered_by' => 'string',
        'given_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId)->orderBy('given_at');
    }
}
