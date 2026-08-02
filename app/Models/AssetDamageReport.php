<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDamageReport extends Model
{
    protected $table = 'asset_damage_reports';

    protected $fillable = [
        'asset_id',
        'reported_by',
        'report_number',
        'damage_level',
        'description',
        'reporter_notes',
        'photos',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'school_id',
        'work_unit_id',
    ];

    protected $casts = [
        'photos' => 'array',
        'reviewed_at' => 'datetime',
    ];

    const STATUS_OPTIONS = ['pending', 'reviewed', 'scheduled', 'in_progress', 'completed', 'rejected'];

    const DAMAGE_LEVEL_OPTIONS = ['ringan', 'sedang', 'berat'];

    public static function generateReportNumber(): string
    {
        return 'DMR-'.date('Ymd').'-'.strtoupper(Str::random(5));
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
