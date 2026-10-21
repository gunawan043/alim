<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabLoan extends LabModel
{
    use SoftDeletes;

    protected $casts = ['loan_date' => 'date', 'planned_return_date' => 'date', 'actual_return_date' => 'date', 'approved_at' => 'datetime', 'semester' => 'integer'];

    protected $table = 'lab_loans';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(\App\Models\AcademicYear::class); }
    public function borrower(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'borrower_user_id'); }
    public function handedOverBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'handed_over_by'); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'received_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(\App\Models\Lab\LabLoanItem::class, 'loan_id'); }
}
