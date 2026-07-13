<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditSession extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'session_code',
        'auditor_id',
        'audit_type',
        'scope',
        'target_room_id',
        'target_category_id',
        'started_at',
        'ended_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scope' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AssetAudit::class, 'audit_session_id');
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(AuditDiscrepancy::class, 'audit_session_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'target_room_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }

    /** Generate a unique session code */
    public static function generateCode(): string
    {
        do {
            $code = 'AUD-' . strtoupper(Str::random(8));
        } while (self::where('session_code', $code)->exists());

        return $code;
    }
}
