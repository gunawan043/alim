<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Subject extends Model
{
    protected $table = 'subjects';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
        });
    }

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'subject_group_id',
        'category',
        'credit_hours',
        'description',
        'is_active',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Rumpun mata pelajaran (entitas resmi lembaga).
     */
    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function adminBooks(): HasMany
    {
        return $this->hasMany(TeacherAdminBook::class, 'subject_id');
    }

    public function gradeLevelSubjects(): HasMany
    {
        return $this->hasMany(GradeLevelSubject::class);
    }
}
