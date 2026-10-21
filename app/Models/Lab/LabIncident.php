<?php

namespace App\Models\Lab;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabIncident extends LabModel
{
    use SoftDeletes;

    protected $casts = ['incident_date' => 'date', 'is_confidential' => 'boolean'];

    protected $table = 'lab_incidents';

    public function lab(): BelongsTo { return $this->belongsTo(\App\Models\Lab\LabProfile::class, 'lab_id'); }
    public function school(): BelongsTo { return $this->belongsTo(\App\Models\School::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'reporter_id'); }
}
