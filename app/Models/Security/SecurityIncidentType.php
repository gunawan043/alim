<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityIncidentType extends SecurityModel
{
    protected $table = 'security_incident_types';

    public const SEVERITY_OPTIONS = [
        'ringan' => 'Ringan',
        'sedang' => 'Sedang',
        'berat' => 'Berat',
        'kritis' => 'Kritis',
    ];

    protected $fillable = ['code', 'name', 'default_severity', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function incidents(): HasMany
    {
        return $this->hasMany(SecurityIncident::class, 'incident_type_id');
    }
}
