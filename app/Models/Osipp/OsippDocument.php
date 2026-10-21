<?php

namespace App\Models\Osipp;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OsippDocument extends OsippModel
{
    use SoftDeletes;

    protected $table = 'osipp_documents';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REVISION = 'revision';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_OPTIONS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Menunggu Verifikasi',
        self::STATUS_VERIFIED => 'Terverifikasi',
        self::STATUS_REVISION => 'Perlu Revisi',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    public const TYPE_OPTIONS = [
        'proposal' => 'Proposal',
        'lpj' => 'Laporan Pertanggungjawaban',
        'surat' => 'Surat / Undangan',
        'notulen' => 'Notulen Rapat',
        'daftar_hadir' => 'Daftar Hadir',
        'other' => 'Dokumen Lain',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(OsippPeriod::class, 'period_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(OsippProgram::class, 'program_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(OsippActivity::class, 'activity_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'success',
            self::STATUS_SUBMITTED => 'info',
            self::STATUS_REVISION => 'warning',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->document_type] ?? ucfirst((string) $this->document_type);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVISION], true);
    }
}
