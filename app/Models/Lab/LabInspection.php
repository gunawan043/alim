<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabInspection extends LabModel
{
    use SoftDeletes;

    protected $casts = ['scheduled_date' => 'date', 'performed_date' => 'date', 'semester' => 'integer'];

    protected $table = 'lab_inspections';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function inspector(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'inspector_id'); }
    public function items(): HasMany { return $this->hasMany(\App\Models\Lab\LabInspectionItem::class, 'inspection_id'); }
}
