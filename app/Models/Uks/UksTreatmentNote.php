<?php

declare(strict_types=1);

namespace App\Models\Uks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksTreatmentNote — Catatan detail perawatan/perembangan pasien UKS.
 *
 * Digunakan saat pasien sedang Dirawat UKS atau Istirahat UKS.
 * Berbeda dengan {@see UksCareEvent} (timeline kejadian) — note ini adalah
 * catatan kumulatif observasi oleh perawat/dokter.
 */
class UksTreatmentNote extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_treatment_notes';

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'patient_id',
        'recorded_by',
        'recorded_at',
        'note',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}
