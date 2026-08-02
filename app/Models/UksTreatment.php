<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UksTreatment extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'uks_treatments';

    protected $fillable = [
        'patient_id',
        'performed_by',
        'chief_complaint',
        'symptoms',
        'vitals',
        'diagnosis',
        'treatment',
        'notes',
    ];

    protected $casts = [
        'patient_id' => 'string',
        'performed_by' => 'string',
        'symptoms' => 'array',
        'vitals' => 'array',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Uks\UksPatient::class, 'patient_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
