<?php

declare(strict_types=1);

namespace App\Models\Uks;

use App\Models\School;
use App\Models\UksTreatment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * UksPrescription — Resep pasien UKS yang terhubung ke lembar pemeriksaan
 * dan ke stok obat (student_medicine_inventory).
 */
class UksPrescription extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'uks_prescriptions';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->id ??= (string) Str::uuid();
            $model->prescribed_at ??= now();
        });
    }

    protected $fillable = [
        'patient_id',
        'treatment_id',
        'school_id',
        'prescribed_by',
        'prescription_no',
        'prescribed_at',
        'status',
        'notes',
        'dispensed_by',
        'dispensed_at',
    ];

    protected $casts = [
        'prescribed_at' => 'datetime',
        'dispensed_at' => 'datetime',
    ];

    public const STATUS_NEW = 'baru';

    public const STATUS_PREPARED = 'disiapkan';

    public const STATUS_DISPENSED = 'diserahkan';

    public const STATUS_CANCELLED = 'dibatalkan';

    public const STATUSES = [
        self::STATUS_NEW => 'Baru',
        self::STATUS_PREPARED => 'Disiapkan',
        self::STATUS_DISPENSED => 'Diserahkan',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(UksPatient::class, 'patient_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(UksTreatment::class, 'treatment_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UksPrescriptionItem::class, 'prescription_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_PREPARED], true);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_PREPARED]);
    }
}
