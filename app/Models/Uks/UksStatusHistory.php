<?php

declare(strict_types=1);

namespace App\Models\Uks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksStatusHistory — Histori perpindahan status pasien UKS.
 *
 * Mencatat setiap perubahan status dengan status asal, status tujuan,
 * waktu perubahan, alasan, dan petugas yang mengubah.
 */
class UksStatusHistory extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_status_histories';

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->id ??= (string) Str::uuid();
        });
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
        'changed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
