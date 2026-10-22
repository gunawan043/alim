<?php

declare(strict_types=1);

namespace App\Models\Uks;

use App\Models\FacilityReferral;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * UksReferral — Rujukan pasien UKS ke klinik / puskesmas / rumah sakit.
 */
class UksReferral extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_referrals';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
            $model->referred_at ??= now();
        });
    }

    protected $fillable = [
        'patient_id',
        'school_id',
        'facility_id',
        'referral_no',
        'facility_name',
        'facility_type',
        'referred_at',
        'urgency',
        'diagnosis',
        'reason',
        'transport',
        'accompanied_by',
        'status',
        'outcome',
        'referred_by',
        'returned_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public const STATUS_REFERRED = 'dirujuk';

    public const STATUS_ACCEPTED = 'diterima';

    public const STATUS_RETURNED = 'kembali';

    public const STATUS_CANCELLED = 'batal';

    public const STATUSES = [
        self::STATUS_REFERRED => 'Dirujuk',
        self::STATUS_ACCEPTED => 'Diterima Faskes',
        self::STATUS_RETURNED => 'Kembali',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const URGENCIES = [
        'rutin' => 'Rutin',
        'segera' => 'Segera',
        'darurat' => 'Darurat',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(FacilityReferral::class, 'facility_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getUrgencyLabelAttribute(): string
    {
        return self::URGENCIES[$this->urgency] ?? ucfirst((string) $this->urgency);
    }
}
