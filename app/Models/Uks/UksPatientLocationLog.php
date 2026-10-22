<?php

declare(strict_types=1);

namespace App\Models\Uks;

use App\Models\School;
use App\Models\UksBed;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksPatientLocationLog — histori perpindahan lokasi perawatan santri.
 *
 * Mencatat: dari asrama ke UKS, dari UKS kembali ke asrama/sekolah,
 * pulang ke rumah, dirujuk ke faskes, dan lokasi lainnya.
 */
class UksPatientLocationLog extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_patient_location_logs';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
            $model->recorded_at ??= now();
        });
    }

    protected $fillable = [
        'patient_id',
        'school_id',
        'bed_id',
        'from_location',
        'to_location',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(UksBed::class, 'bed_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getFromLabelAttribute(): string
    {
        return UksPatient::$careLocationLabels[$this->from_location] ?? '-';
    }

    public function getToLabelAttribute(): string
    {
        return UksPatient::$careLocationLabels[$this->to_location] ?? ucfirst((string) $this->to_location);
    }
}
