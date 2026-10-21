<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabCalibration extends LabModel
{
    use SoftDeletes;

    protected $casts = ['last_calibration_date' => 'date', 'next_calibration_date' => 'date'];

    protected $table = 'lab_calibrations';
    public function getStatusAttribute($value): string
    {
        if ($this->next_calibration_date) {
            return $this->next_calibration_date->lt(today()) ? 'OD' : 'OS';
        }

        return $value ?: 'OS';
    }


    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(\App\Models\Asset::class); }
    public function performer(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'performed_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
