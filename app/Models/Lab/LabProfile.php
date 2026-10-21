<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabProfile extends LabModel
{
    use SoftDeletes;

    protected $casts = ['is_active' => 'boolean'];

    protected $table = 'lab_profiles';

    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function workUnit(): BelongsTo { return $this->belongsTo(\App\Models\WorkUnit::class); }
    public function assetRoom(): BelongsTo { return $this->belongsTo(\App\Models\AssetRoom::class, 'asset_room_id'); }
    public function coordinator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'coordinator_user_id'); }
    public function head(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'head_user_id'); }
    public function items(): HasMany { return $this->hasMany(\App\Models\Lab\LabItemProfile::class, 'lab_id'); }
    public function inspections(): HasMany { return $this->hasMany(\App\Models\Lab\LabInspection::class, 'lab_id'); }
    public function calibrations(): HasMany { return $this->hasMany(\App\Models\Lab\LabCalibration::class, 'lab_id'); }
    public function loans(): HasMany { return $this->hasMany(\App\Models\Lab\LabLoan::class, 'lab_id'); }
    public function activities(): HasMany { return $this->hasMany(\App\Models\Lab\LabActivity::class, 'lab_id'); }
    public function usageRequests(): HasMany { return $this->hasMany(\App\Models\Lab\LabUsageRequest::class, 'lab_id'); }
    public function scheduleSlots(): HasMany { return $this->hasMany(\App\Models\Lab\LabScheduleSlot::class, 'lab_id'); }
    public function safetyInspections(): HasMany { return $this->hasMany(\App\Models\Lab\LabSafetyInspection::class, 'lab_id'); }
    public function incidents(): HasMany { return $this->hasMany(\App\Models\Lab\LabIncident::class, 'lab_id'); }
    public function disposals(): HasMany { return $this->hasMany(\App\Models\Lab\LabDisposal::class, 'lab_id'); }
    public function evaluations(): HasMany { return $this->hasMany(\App\Models\Lab\LabEvaluation::class, 'lab_id'); }
}
