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
        'name_ar',
        'subject_group_id',
        'category',
        'source',
        'credit_hours',
        'description',
        'is_active',
    ];

    /**
     * Boleh diajar lebih dari satu guru pada rombel yang sama?
     * Kebijakan: hanya Tahfidz (hafalan) & Hadits — karena berkaitan dengan hafalan.
     */
    public function allowsMultipleTeachers(): bool
    {
        $haystack = mb_strtolower(($this->name ?? '').' '.($this->code ?? ''));

        return (bool) preg_match('/(tahfidz|tahfiz|hafalan|hadits|hadist)/', $haystack);
    }

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
