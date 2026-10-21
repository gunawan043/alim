<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabActivity extends LabModel
{
    use SoftDeletes;

    protected $casts = ['activity_date' => 'date', 'participants_count' => 'integer', 'semester' => 'integer'];

    protected $table = 'lab_activities';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'teacher_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(\App\Models\Subject::class); }
    public function studyGroup(): BelongsTo { return $this->belongsTo(\App\Models\StudyGroup::class); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(\App\Models\Lab\LabActivityItem::class, 'activity_id'); }
    public function report(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(\App\Models\Lab\LabReport::class, 'activity_id'); }
    public function attendances(): HasMany { return $this->hasMany(\App\Models\Lab\LabActivityAttendance::class, 'activity_id'); }
}
