<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CutiPeriod extends Model
{
    use HasUuids;

    protected $table = 'cuti_periods';

    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(CutiBalance::class, 'cuti_period_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CutiRequest::class, 'cuti_period_id');
    }
}
