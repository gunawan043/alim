<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CurriculumProjectAssessment extends Model
{
    protected $table = 'curriculum_project_assessments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->id = $model->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'project_id',
        'student_id',
        'scores',
        'average',
        'predicate',
        'notes',
        'evidence_note',
        'evidence_url',
        'assessed_by',
    ];

    protected $casts = [
        'scores' => 'array',
        'average' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CurriculumProject::class, 'project_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public static function predicateFor(float $average): string
    {
        return match (true) {
            $average >= 3.5 => 'Sangat Baik',
            $average >= 2.5 => 'Baik',
            $average >= 1.5 => 'Cukup',
            default => 'Perlu Bimbingan',
        };
    }
}
