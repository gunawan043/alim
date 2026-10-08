<?php

namespace App\Models;

use App\Models\Traits\LogsDeletion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Soal extends Model
{
    use HasFactory, LogsDeletion, SoftDeletes;

    protected $table = 'soal';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'bank_soal_id',
        'tp_id',
        'materi',
        'derived_from_soal_id',
        'tipe_soal',
        'pertanyaan',
        'pembahasan',
        'gambar_path',
        'audio_path',
        'bobot_default',
        'tingkat_kesulitan_estimasi',
        'waktu_estimasi_menit',
        'status',
        'workflow_status',
        'dibuat_oleh',
        'direview_oleh',
        'approved_by',
        'approved_at',
        'tags',
        'content_hash',
        'shingles_hash',
        'similarity_checked_at',
        'similarity_summary',
        'times_used',
    ];

    const WORKFLOW_DRAFT = 'draft';

    const WORKFLOW_REVIEW = 'review';

    const WORKFLOW_REVISI = 'revisi';

    const WORKFLOW_APPROVED = 'approved';

    const WORKFLOW_OPTIONS = [
        self::WORKFLOW_DRAFT => 'Draft',
        self::WORKFLOW_REVIEW => 'Review',
        self::WORKFLOW_REVISI => 'Perlu Perbaikan',
        self::WORKFLOW_APPROVED => 'Approved',
    ];

    protected $casts = [
        'tags' => 'array',
        'shingles_hash' => 'array',
        'similarity_summary' => 'array',
        'bobot_default' => 'decimal:2',
        'waktu_estimasi_menit' => 'integer',
        'times_used' => 'integer',
        'approved_at' => 'datetime',
        'similarity_checked_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SoalOption::class)->orderBy('urutan');
    }

    public function correctOptions(): HasMany
    {
        return $this->hasMany(SoalOption::class)->where('is_correct', true);
    }

    public function paketSoalItems(): HasMany
    {
        return $this->hasMany(PaketSoalItem::class);
    }

    public function analysis(): HasMany
    {
        return $this->hasMany(ItemAnalysis::class);
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function derivedFrom(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'derived_from_soal_id');
    }

    public function derivatives(): HasMany
    {
        return $this->hasMany(Soal::class, 'derived_from_soal_id');
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewable_id')
            ->where('reviewable_type', self::class);
    }

    public function similarities(): HasMany
    {
        return $this->hasMany(SoalSimilarity::class, 'soal_id')->orderByDesc('score');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('tipe_soal', $type);
    }

    public function scopeWorkflow($query, string $status)
    {
        return $query->where('workflow_status', $status);
    }

    /**
     * Sinkronkan workflow_status (sumber tampilan) dengan enum status legacy.
     */
    public function syncWorkflowStatus(string $workflow): void
    {
        $legacy = match ($workflow) {
            self::WORKFLOW_APPROVED => 'approved',
            self::WORKFLOW_REVISI => 'review',
            self::WORKFLOW_REVIEW => 'review',
            default => 'draft',
        };

        $this->forceFill([
            'workflow_status' => $workflow,
            'status' => $legacy,
            'approved_at' => $workflow === self::WORKFLOW_APPROVED ? ($this->approved_at ?: now()) : null,
        ])->save();
    }

    public function isApproved(): bool
    {
        return $this->workflow_status === self::WORKFLOW_APPROVED || $this->status === 'approved';
    }

    public function getIsObjectivelyGradableAttribute(): bool
    {
        return in_array($this->tipe_soal, ['pg', 'bs', 'jodoh']);
    }
}
