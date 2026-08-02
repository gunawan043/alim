<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UksMedicationLog extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_medication_logs';

    protected $fillable = [
        'patient_id',
        'administered_by',
        'medicine_name',
        'dosage',
        'route',
        'given_at',
        'notes',
        'is_scheduled',
        'schedule',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'administered_by' => 'string',
        'given_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'schedule' => 'array',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
