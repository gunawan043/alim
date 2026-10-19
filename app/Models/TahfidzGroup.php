<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TahfidzGroup extends Model
{
    protected $table = 'tahfidz_groups';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'work_unit_id', 'school_id', 'study_group_id', 'academic_year_id',
        'tahfidz_package_id', 'curriculum_id', 'name', 'code', 'gender',
        'teacher_id', 'coordinator_id', 'max_members', 'room', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_members' => 'integer',
    ];

    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TahfidzPackage::class, 'tahfidz_package_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(TahfidzCurriculum::class, 'curriculum_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TahfidzGroupMember::class, 'tahfidz_group_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TahfidzGroupTeacher::class, 'tahfidz_group_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TahfidzSchedule::class, 'tahfidz_group_id');
    }

    public function setorans(): HasMany
    {
        return $this->hasMany(TahfidzSetoran::class, 'tahfidz_group_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(TahfidzStudentTarget::class, 'tahfidz_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
