<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SecurityShift extends SecurityModel
{
    protected $table = 'security_shifts';

    protected $fillable = ['code', 'name', 'start_time', 'end_time', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function teams(): HasMany
    {
        return $this->hasMany(SecurityTeam::class, 'shift_id');
    }

    public function label(): string
    {
        return $this->name.' ('.$this->start_time.'-'.$this->end_time.')';
    }
}
