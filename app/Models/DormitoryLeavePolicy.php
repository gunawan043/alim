<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DormitoryLeavePolicy extends Model
{
    protected $table = 'dormitory_leave_policies';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (! $m->id) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'dormitory_id',
        'permit_type',
        'is_enabled',
        'requires_approval',
        'quota_per_week',
        'quota_per_month',
        'quota_per_semester',
        'quota_per_year',
        'auto_approve_gtk',
        'auto_approve_kepala_asrama',
        'emergency_bypass_quota',
        'emergency_notify_wa_kepala',
        'emergency_approver_roles',
        'updated_by',
        'pulang_quota',
        'pulang_quota_period',
        'special_quota_mode',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'requires_approval' => 'boolean',
        'auto_approve_gtk' => 'boolean',
        'auto_approve_kepala_asrama' => 'boolean',
        'emergency_bypass_quota' => 'boolean',
        'emergency_notify_wa_kepala' => 'boolean',
        'emergency_approver_roles' => 'array',
        'updated_by' => 'string',
        'pulang_quota' => 'integer',
        'pulang_quota_period' => 'string',
        'special_quota_mode' => 'string',
    ];

    /** @return BelongsTo<Dormitory, $this> */
    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Get quota for a given DormitoryPermit record. */
    public function getQuota(?string $period): ?int
    {
        return match ($period) {
            'week' => $this->quota_per_week,
            'month' => $this->quota_per_month,
            'semester' => $this->quota_per_semester,
            'year' => $this->quota_per_year,
            default => null,
        };
    }

    /**
     * Kuota umum yang berlaku: periode paling spesifik yang diisi.
     * Nilai 0/null berarti tidak ada batas.
     *
     * @return array{quota:int,period:string}|null
     */
    public function resolveGeneralQuota(): ?array
    {
        foreach ([
            ['period' => 'week', 'column' => 'quota_per_week'],
            ['period' => 'month', 'column' => 'quota_per_month'],
            ['period' => 'semester', 'column' => 'quota_per_semester'],
            ['period' => 'year', 'column' => 'quota_per_year'],
        ] as $candidate) {
            $value = $this->{$candidate['column']};

            if ($value !== null && (int) $value > 0) {
                return ['quota' => (int) $value, 'period' => $candidate['period']];
            }
        }

        return null;
    }

    /**
     * Ringkasan kuota efektif untuk daftar konfigurasi.
     *
     * @return array{quota:int|null,period:string|null,label:string}
     */
    public function quotaSummary(): array
    {
        if ($this->permit_type === 'pulang') {
            $resolved = $this->resolvePulangQuota();
        } elseif (in_array($this->special_quota_mode, ['shared_with_pulang'], true)) {
            $resolved = null;
        } elseif ($this->special_quota_mode === 'none' && in_array($this->permit_type, ['keluar_kota', 'berobat', 'sakit', 'keperluan_keluarga', 'lainnya'], true)) {
            $resolved = null;
        } else {
            $resolved = $this->resolveGeneralQuota();
        }

        if (! $resolved) {
            return ['quota' => null, 'period' => null, 'label' => 'Tanpa batas'];
        }

        $labels = [
            'week' => 'minggu',
            'month' => 'bulan',
            'monthly' => 'bulan',
            'quarter' => 'quarter',
            'quarterly' => 'quarter',
            'semester' => 'semester',
            'year' => 'tahun',
            'yearly' => 'tahun',
        ];

        $periodLabel = $labels[$resolved['period']] ?? str_replace('_', ' ', $resolved['period']);

        return [
            'quota' => (int) $resolved['quota'],
            'period' => $resolved['period'],
            'label' => $resolved['quota'].'x / '.$periodLabel,
        ];
    }

    /**
     * Resolve quota pulang dengan fallback ke quota_per_month.
     * Return null jika tidak ada kuota yang terdefinisi.
     *
     * @return array{quota:int,period:string}|null
     */
    public function resolvePulangQuota(): ?array
    {
        if ($this->pulang_quota !== null && (int) $this->pulang_quota > 0 && $this->pulang_quota_period !== null) {
            return ['quota' => (int) $this->pulang_quota, 'period' => $this->pulang_quota_period];
        }
        if ($this->quota_per_month !== null && (int) $this->quota_per_month > 0) {
            return ['quota' => (int) $this->quota_per_month, 'period' => 'monthly'];
        }

        return null;
    }
}
