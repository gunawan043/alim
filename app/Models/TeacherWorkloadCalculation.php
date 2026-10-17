<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeacherWorkloadCalculation extends Model
{
    protected $table = 'teacher_workload_calculations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'user_id',
        'school_id',
        'academic_year_id',
        'total_required_hours',
        'total_assigned_hours',
        'additional_task_hours',
        'gap_hours',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_required_hours' => 'decimal:2',
        'total_assigned_hours' => 'decimal:2',
        'additional_task_hours' => 'decimal:2',
        'gap_hours' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Map status labels
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'underloaded' => 'Under Loaded',
            'normal' => 'Normal',
            'overloaded' => 'Overloaded',
            'critical_overload' => 'Critical Overload',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'underloaded' => 'info',
            'normal' => 'success',
            'overloaded' => 'warning',
            'critical_overload' => 'danger',
            default => 'secondary',
        };
    }
}
