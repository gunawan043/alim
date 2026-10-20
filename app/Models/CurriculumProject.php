<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CurriculumProject extends Model
{
    protected $table = 'curriculum_projects';

    protected $keyType = 'string';

    public $incrementing = false;

    const TYPE_P5 = 'p5';

    const TYPE_P5RA = 'p5ra';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ACTIVE => 'Berjalan',
        self::STATUS_COMPLETED => 'Selesai',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'semester',
        'curriculum_profile_id',
        'type',
        'source',
        'ai_generated_content_id',
        'title',
        'theme',
        'dimensions',
        'grade_level_ids',
        'description',
        'activities',
        'start_date',
        'end_date',
        'coordinator_id',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'grade_level_ids' => 'array',
        'activities' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function curriculumProfile(): BelongsTo
    {
        return $this->belongsTo(CurriculumProfile::class, 'curriculum_profile_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CurriculumProjectAssessment::class, 'project_id');
    }

    public function aiGeneratedContent(): BelongsTo
    {
        return $this->belongsTo(AiGeneratedContent::class, 'ai_generated_content_id');
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'ai' ? 'AI — perlu ditinjau' : 'Manual';
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_P5RA ? 'P5RA' : 'P5';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }
}
