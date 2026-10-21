<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabActivityAttendance extends LabModel
{
    protected $table = 'lab_activity_attendances';

    public function activity(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabActivity::class, 'activity_id'); }
    public function student(): BelongsTo { return $this->belongsTo(\App\Models\Student::class); }
}
