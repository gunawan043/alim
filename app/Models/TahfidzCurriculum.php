<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TahfidzCurriculum extends Model
{
    protected $table = 'tahfidz_curriculums';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'school_id', 'tahfidz_package_id', 'grade_level_id',
        'academic_year_id', 'semester', 'target_juz_start', 'target_juz_end',
        'target_halaman', 'target_hadits', 'lines_per_page', 'kkm_score',
        'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'target_juz_start' => 'integer',
        'target_juz_end' => 'integer',
        'target_halaman' => 'integer',
        'target_hadits' => 'integer',
        'lines_per_page' => 'integer',
        'kkm_score' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TahfidzPackage::class, 'tahfidz_package_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TahfidzGroup::class, 'curriculum_id');
    }
}
