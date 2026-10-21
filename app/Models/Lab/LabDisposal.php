<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabDisposal extends LabModel
{
    use SoftDeletes;

    protected $casts = ['disposal_date' => 'date', 'approved_at' => 'datetime', 'semester' => 'integer'];

    protected $table = 'lab_disposals';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function requester(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(\App\Models\Lab\LabDisposalItem::class, 'disposal_id'); }
}
