<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityTeam extends SecurityModel
{
    protected $table = 'security_teams';

    protected $fillable = ['post_id', 'shift_id', 'leader_id', 'code', 'name', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SecurityPost::class, 'post_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(SecurityShift::class, 'shift_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SecurityTeamMember::class, 'team_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('is_active', true);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SecurityPatrolSchedule::class, 'team_id');
    }
}
