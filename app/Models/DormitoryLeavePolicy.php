<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
                $m->id = (string) \Illuminate\Support\Str::uuid();
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
     * Resolve quota pulang dengan fallback ke quota_per_month.
     * Return null jika tidak ada kuota yang terdefinisi.
     *
     * @return array{quota:int,period:string}|null
     */
    public function resolvePulangQuota(): ?array
    {
        if ($this->pulang_quota !== null && $this->pulang_quota_period !== null) {
            return ['quota' => (int) $this->pulang_quota, 'period' => $this->pulang_quota_period];
        }
        if ($this->quota_per_month !== null) {
            return ['quota' => (int) $this->quota_per_month, 'period' => 'monthly'];
        }

        return null;
    }
}
