<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Satu pasangan mapel–kelas yang didampingi seorang guru pendamping.
 */
class OtherTeacherTaskSubject extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'other_teacher_task_id',
        'subject_id',
        'study_group_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OtherTeacherTask::class, 'other_teacher_task_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }
}
