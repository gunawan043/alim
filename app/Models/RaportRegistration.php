<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RaportRegistration extends Model
{
    protected $table = 'raport_registrations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'student_id',
        'study_group_id',
        'academic_year_id',
        'semester',
        'status',
        'final_score',
        'class_rank',
        'predicate',
        'homeroom_note',
        'finalized_at',
        'printed_at',
        'finalized_by',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'class_rank' => 'integer',
        'finalized_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
