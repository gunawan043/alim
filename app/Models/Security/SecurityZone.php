<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityZone extends SecurityModel
{
    protected $table = 'security_zones';

    public const RISK_OPTIONS = [
        'rendah' => 'Rendah',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
    ];

    protected $fillable = ['code', 'name', 'risk_level', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function posts(): HasMany
    {
        return $this->hasMany(SecurityPost::class, 'zone_id');
    }

    public function riskLabel(): string
    {
        return self::RISK_OPTIONS[$this->risk_level] ?? $this->risk_level;
    }
}
