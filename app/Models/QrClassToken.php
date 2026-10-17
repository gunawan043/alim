<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QrClassToken extends Model
{
    protected $table = 'qr_class_tokens';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
        });
        static::creating(function ($token) {
            if (empty($token->token_hash)) {
                $token->token_hash = hash('sha256', $token->id.time().Str::random(32));
            }
        });
    }

    protected $fillable = [
        'study_group_id',
        'school_id',
        'academic_year_id',
        'token_hash',
        'qr_url_expires_at',
        'last_regenerated_at',
        'scan_count',
        'last_scan_at',
    ];

    protected $casts = [
        'qr_url_expires_at' => 'datetime',
        'last_regenerated_at' => 'datetime',
        'scan_count' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('qr_url_expires_at')
                ->orWhere('qr_url_expires_at', '>', now());
        });
    }

    public function scopeForStudyGroup($query, string $studyGroupId)
    {
        return $query
            ->where('study_group_id', $studyGroupId)
            ->whereNotNull('study_group_id')
            ->active()
            ->orderByDesc('last_regenerated_at');
    }

    // ── Actions ───────────────────────────────────────────────────

    /**
     * Regenerate QR untuk study group ini.
     * Satu kelas + tahun ajaran hanya punya SATU baris token (unique constraint),
     * sehingga regenerate memperbarui token_hash & status pada baris yang sama.
     * Token hash baru membuat QR lama tidak dikenali sistem.
     */
    public function regenerate(): self
    {
        $this->token_hash = hash('sha256', $this->id.time().Str::random(32));
        $this->qr_url_expires_at = null;
        $this->last_regenerated_at = now();
        $this->scan_count = 0;
        $this->last_scan_at = null;
        $this->save();

        return $this;
    }

    /**
     * Increment scan count and update last scan timestamp.
     */
    public function incrementScanCount(): void
    {
        $this->increment('scan_count');
        $this->update(['last_scan_at' => now()]);
    }

    /**
     * Check if this token is valid for scanning.
     */
    public function isValid(): bool
    {
        return $this->qr_url_expires_at === null
            || $this->qr_url_expires_at > now();
    }
}
