<?php

namespace App\Models\Osipp;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippQism extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_qisms';

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(OsippMember::class, 'leader_member_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(OsippMember::class, 'qism_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', OsippMember::STATUS_ACTIVE);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name.' (Qism)';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
