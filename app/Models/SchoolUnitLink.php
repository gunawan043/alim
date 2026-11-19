<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolUnitLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'primary_school_id',
        'operational_school_id',
        'relation_type',
        'is_active',
        'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function primarySchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'primary_school_id');
    }

    public function operationalSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'operational_school_id');
    }
}
