<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabUsageRequest extends LabModel
{
    use SoftDeletes;

    protected $casts = ['usage_date' => 'date', 'approved_at' => 'datetime', 'participants_count' => 'integer', 'semester' => 'integer'];

    protected $table = 'lab_usage_requests';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function applicant(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'applicant_id'); }
    public function subject(): BelongsTo { return $this->belongsTo(\App\Models\Subject::class); }
    public function approver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function activity(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabActivity::class, 'activity_id'); }
}
