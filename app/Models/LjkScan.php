<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Satu pemindaian (satu gambar) untuk sebuah lembar.
 *
 * `quality` menyimpan metrik citra (ketajaman, kecerahan, marker, skew)
 * agar keputusan tolak/terima dapat diaudit.
 */
class LjkScan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ljk_scans';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'ljk_exam_id', 'sheet_id', 'participant_id', 'uploaded_by', 'source',
        'image_path', 'image_hash', 'image_width', 'image_height', 'file_size_kb',
        'quality', 'status', 'error_code', 'error_message', 'engine_version',
        'processing_ms', 'scanned_at',
    ];

    protected $casts = [
        'image_width' => 'integer',
        'image_height' => 'integer',
        'file_size_kb' => 'integer',
        'quality' => 'array',
        'processing_ms' => 'integer',
        'scanned_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(LjkExam::class, 'ljk_exam_id');
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(LjkSheet::class, 'sheet_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(LjkParticipant::class, 'participant_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LjkScanAnswer::class, 'ljk_scan_id')->orderBy('nomor');
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }
}
