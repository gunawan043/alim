<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jejak sinkronisasi hasil LJK ke modul nilai resmi (admin_nilai_sumatif).
 */
class LjkSyncLog extends Model
{
    use HasFactory;

    protected $table = 'ljk_sync_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public const ACTION_SYNC = 'sync';

    public const ACTION_RESYNC = 'resync';

    protected $fillable = [
        'ljk_exam_id', 'admin_book_id', 'actor_id', 'action',
        'students_count', 'success_count', 'skipped_count',
        'payload_hash', 'meta',
    ];

    protected $casts = [
        'students_count' => 'integer',
        'success_count' => 'integer',
        'skipped_count' => 'integer',
        'meta' => 'array',
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

    public function adminBook(): BelongsTo
    {
        return $this->belongsTo(TeacherAdminBook::class, 'admin_book_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
