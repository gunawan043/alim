<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabScheduleSlot extends LabModel
{
    protected $casts = ['day_of_week' => 'integer', 'slot_index' => 'integer', 'semester' => 'integer'];

    protected $table = 'lab_schedule_slots';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function studyGroup(): BelongsTo { return $this->belongsTo(\App\Models\StudyGroup::class); }
    public function subject(): BelongsTo { return $this->belongsTo(\App\Models\Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'teacher_id'); }
    public function usageRequest(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabUsageRequest::class, 'usage_request_id'); }
    public function sourceJadwalKbm(): BelongsTo { return $this->belongsTo(\App\Models\JadwalKbm::class, 'source_jadwal_kbm_id'); }
}
