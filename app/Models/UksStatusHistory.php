<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksStatusHistory — Histori eksplisit perubahan status perawatan pasien UKS.
 *
 * Setiap kali status pasien berubah (via change-status / discharge / mark-return),
 * baris baru ditambahkan ke tabel ini untuk audit trail.
 */
class UksStatusHistory extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $table = 'uks_status_histories';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'patient_id',
        'changed_by',
        'from_status',
        'to_status',
        'changed_at',
        'reason',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'changed_by' => 'string',
        'changed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId)->orderBy('changed_at');
    }
}
