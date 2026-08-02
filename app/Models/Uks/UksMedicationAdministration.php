<?php

declare(strict_types=1);

namespace App\Models\Uks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksMedicationAdministration — Histori pemberian obat harian pasien UKS.
 *
 * Mencatat nama obat, dosis, jumlah yang diberikan, jam pemberian,
 * dan petugas yang memberikan. Tiap entri adalah satu kali
 * pemberian/pencatatan.
 */
class UksMedicationAdministration extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_medication_administrations';

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->id ??= (string) Str::uuid();
            if (! isset($m->quantity) || $m->quantity === null) {
                $m->quantity = 1;
            }
        });
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
        'given_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'administered_by');
    }
}
