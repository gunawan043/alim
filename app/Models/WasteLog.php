<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WasteLog extends Model
{
    protected $table = 'waste_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'source_id', 'log_date', 'waste_type', 'weight_kg', 'sacks_count',
        'volume_m3', 'sorting_status', 'destination', 'photo_path', 'photo_url',
        'notes', 'recorded_by', 'status', 'verified_by', 'verified_at', 'coordinator_note',
    ];

    protected $casts = [
        'log_date' => 'date', 'weight_kg' => 'decimal:2', 'volume_m3' => 'decimal:2',
        'sacks_count' => 'integer', 'verified_at' => 'datetime',
    ];

    public const TYPE_OPTIONS = ['organik', 'anorganik', 'b3', 'residu'];
    public const SORTING_OPTIONS = ['terpilah', 'tercampur'];
    public const DESTINATION_OPTIONS = ['kompos', 'bank_sampah', 'tps', 'dlh', 'lainnya'];
    public const STATUS_OPTIONS = ['submitted', 'verified', 'rejected'];

    public function source(): BelongsTo { return $this->belongsTo(WasteSource::class, 'source_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
