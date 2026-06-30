<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsDeletion;
use Illuminate\Support\Str;

class EvaluationEngine extends Model
{
    use HasFactory, SoftDeletes, LogsDeletion;

    protected $table = 'evaluation_engines';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'subject_id', 'grade_level_id', 'academic_year_id',
        'semester', 'engine_name', 'engine_version',
        'configuration', 'is_active', 'last_run_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Save engine metrics snapshot
     */
    public function recordSnapshot(array $metrics): void
    {
        $snap = new EngineSnapshot;
        $snap->configuration_metrics = $metrics;
        $snap->evaluation_engine_id = $this->id;
        $snap->save();
        $this->update(['last_run_at' => now()]);
    }
}
