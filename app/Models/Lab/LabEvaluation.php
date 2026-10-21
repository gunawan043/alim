<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabEvaluation extends LabModel
{
    use SoftDeletes;

    protected $casts = ['evaluation_date' => 'date', 'semester' => 'integer'];

    protected $table = 'lab_evaluations';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function evaluator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'evaluated_by'); }
}
