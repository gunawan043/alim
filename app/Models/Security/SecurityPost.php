<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityPost extends SecurityModel
{
    protected $table = 'security_posts';

    protected $fillable = ['zone_id', 'code', 'name', 'location', 'phone', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(SecurityZone::class, 'zone_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(SecurityTeam::class, 'post_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(SecurityCheckpoint::class, 'post_id');
    }
}
