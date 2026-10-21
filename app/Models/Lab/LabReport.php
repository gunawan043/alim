<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabReport extends LabModel
{
    use SoftDeletes;

    protected $casts = ['report_date' => 'date', 'participants_total' => 'integer', 'participants_present' => 'integer', 'participants_absent' => 'integer', 'absent_names' => 'array', 'verified_at' => 'datetime'];

    protected $table = 'lab_reports';

    public function activity(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabActivity::class, 'activity_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'teacher_id'); }
    public function verifier(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'verified_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
