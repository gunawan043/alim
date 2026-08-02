<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StructuralPosition extends Model
{
    use HasUuids;

    protected $table = 'structural_positions';

    protected $fillable = [
        'code',
        'name',
        'level',
        'hierarchy_level',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hierarchy_level' => 'integer',
    ];

    /**
     * Active structural positions scope.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Assignments for this position.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(StructuralAssignment::class, 'position_id');
    }

    /**
     * Active assignments for this position.
     */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('start_date', '<=', now()->toDateString());
    }
}
