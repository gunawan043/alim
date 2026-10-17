<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GtkAnalysisRun extends Model
{
    public const STATUS_PENDING = 0;

    public const STATUS_PROCESSING = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_FAILED = 3;

    public const SCOPE_SCHOOL = 'school';

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_TEACHER = 'teacher';

    public const SCOPE_SUBJECT = 'subject';

    protected $table = 'gtk_analysis_runs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'scope',
        'trigger_source',
        'trigger_ref_id',
        'status',
        'summary',
        'context',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'context' => 'array',
        'status' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(GtkGapSummary::class, 'analysis_run_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown',
        };
    }
}
