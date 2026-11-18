<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDapodikMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'dapodik_school_npsn',
        'dapodik_rombel_name',
        'dapodik_rombel_code',
        'dapodik_level',
        'is_active',
        'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
