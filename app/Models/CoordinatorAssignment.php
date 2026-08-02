<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CoordinatorAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'coordinator_assignments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'decree_id',
        'teacher_id',
        'coordination_area',
        'coordination_type',
        'school_id',
        'academic_year_id',
        'start_date',
        'end_date',
        'status',
        'description',
        'responsibilities',
    ];

    public function decree(): BelongsTo
    {
        return $this->belongsTo(InstitutionDecree::class, 'decree_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where('start_date', '<=', now()->toDateString());
    }

    public function scopeForArea($query, string $area)
    {
        return $query->where('coordination_area', $area);
    }
}
