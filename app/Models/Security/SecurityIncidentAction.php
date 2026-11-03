<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncidentAction extends SecurityModel
{
    protected $table = 'security_incident_actions';

    protected $fillable = ['incident_id', 'actor_id', 'action', 'notes'];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(SecurityIncident::class, 'incident_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
