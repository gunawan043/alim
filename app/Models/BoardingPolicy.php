<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BoardingPolicy extends Model
{
    protected $table = 'boarding_policies';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'leave_strategy',
        'leave_quota',
        'leave_quota_period',
        'visit_strategy',
        'visit_quota',
        'visit_quota_period',
        'max_visitors_per_visit',
        'curfew_hour',
        'special_permission_allowed',
        'special_permission_types',
        'auto_sync_academic_attendance',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'leave_quota' => 'integer',
        'leave_quota_period' => 'string',
        'visit_quota' => 'integer',
        'visit_quota_period' => 'string',
        'max_visitors_per_visit' => 'integer',
        'curfew_hour' => 'integer',
        'special_permission_types' => 'array',
        'special_permission_allowed' => 'boolean',
        'auto_sync_academic_attendance' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(DormitoryPolicyAssignment::class, 'boarding_policy_id');
    }

    public function isUnrestricted(): bool
    {
        return $this->leave_strategy === 'unrestricted';
    }

    public function isBanned(): bool
    {
        return $this->leave_strategy === 'banned';
    }

    public function isQuotaBased(): bool
    {
        return $this->leave_strategy === 'quota';
    }

    public function allowsSpecialPermission(): bool
    {
        return $this->special_permission_allowed;
    }
}
