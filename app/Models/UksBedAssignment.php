<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UksBedAssignment extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_bed_assignments';

    protected $fillable = [
        'bed_id',
        'patient_id',
        'assigned_at',
        'released_at',
        'status',
        'reason',
    ];

    protected $casts = [
        'bed_id' => 'string',
        'patient_id' => 'string',
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function bed(): BelongsTo
    {
        return $this->belongsTo(UksBed::class, 'bed_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeCurrentlyAssigned($query)
    {
        return $query->where('status', 'assigned')
            ->whereNull('released_at');
    }

    /**
     * Assign a bed to a patient, releasing any previous assignments.
     */
    public static function assign(
        string $bedId,
        string $patientId,
        ?string $reason = null,
        ?\DateTimeInterface $assignedAt = null
    ): self {
        // Release any existing assignment for this bed
        static::where('bed_id', $bedId)
            ->where('status', 'assigned')
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

        // Also release any existing assignment for this patient
        static::where('patient_id', $patientId)
            ->where('status', 'assigned')
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

        return static::create([
            'bed_id' => $bedId,
            'patient_id' => $patientId,
            'assigned_at' => $assignedAt ?? now(),
            'status' => 'assigned',
            'reason' => $reason,
            'released_at' => null,
        ]);
    }
}
