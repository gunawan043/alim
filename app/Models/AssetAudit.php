<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAudit extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'audit_session_id',
        'asset_id',
        'physical_found',
        'physical_location_id',
        'condition_notes',
        'photo_urls',
        'remarks',
    ];

    protected $casts = [
        'physical_found' => 'boolean',
        'photo_urls' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function physicalRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'physical_location_id');
    }
}
