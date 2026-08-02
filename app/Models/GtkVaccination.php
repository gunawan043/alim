<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GtkVaccination extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $table = 'gtk_vaccinations';

    protected $fillable = [
        'user_id',
        'administered_by',
        'vaccine_name',
        'given_at',
        'batch_number',
        'next_due_date',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'string',
        'administered_by' => 'string',
        'given_at' => 'datetime',
        'next_due_date' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
