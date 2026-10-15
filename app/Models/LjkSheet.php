<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Lembar jawaban tercetak milik satu peserta.
 *
 * `qr_token` = payload tertandatangan HMAC berisi id lembar + kode peserta.
 * Server memverifikasi saat memindai; payload tidak pernah memuat kunci.
 */
class LjkSheet extends Model
{
    use HasFactory;

    protected $table = 'ljk_sheets';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_SCANNED = 'scanned';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'ljk_exam_id', 'participant_id', 'template_id', 'template_version',
        'sheet_code', 'qr_token', 'pages', 'status', 'generated_at', 'printed_at',
    ];

    protected $casts = [
        'template_version' => 'integer',
        'pages' => 'integer',
        'generated_at' => 'datetime',
        'printed_at' => 'datetime',
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

    public function participant(): BelongsTo
    {
        return $this->belongsTo(LjkParticipant::class, 'participant_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LjkTemplate::class, 'template_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(LjkScan::class, 'sheet_id');
    }

    public function markPrinted(): void
    {
        if ($this->status === self::STATUS_GENERATED) {
            $this->forceFill([
                'status' => self::STATUS_PRINTED,
                'printed_at' => $this->printed_at ?: now(),
            ])->save();
        }
    }
}
