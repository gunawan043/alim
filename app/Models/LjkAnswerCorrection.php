<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Log koreksi guru — append-only, tidak boleh diubah setelah dibuat.
 */
class LjkAnswerCorrection extends Model
{
    use HasFactory;

    protected $table = 'ljk_answer_corrections';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ljk_scan_answer_id', 'ljk_exam_id', 'participant_id', 'nomor',
        'before', 'after', 'reason', 'corrected_by', 'corrected_at',
    ];

    protected $casts = [
        'nomor' => 'integer',
        'before' => 'array',
        'after' => 'array',
        'corrected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
            $m->corrected_at = $m->corrected_at ?: now();
        });

        // Append-only: blokir update & delete.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function scanAnswer(): BelongsTo
    {
        return $this->belongsTo(LjkScanAnswer::class, 'ljk_scan_answer_id');
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
