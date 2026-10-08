<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaketSoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paket_soal';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'kisi_kisi_soal_id', 'judul', 'kode_paket', 'versi',
        'is_acak_urutan_soal', 'is_acak_opsi',
        'jumlah_soal_aktual', 'total_bobot_aktual',
        'waktu_pengerjaan_menit', 'instruksi_umum',
        'is_published', 'published_by', 'published_at',
        'workflow_status', 'approved_at', 'approved_by',
        'similarity_checked_at', 'similarity_summary', 'distributed_at',
        'shared_scope', 'kkm',
    ];

    const WORKFLOW_DRAFT = 'draft';

    const WORKFLOW_REVIEW = 'review';

    const WORKFLOW_REVISI = 'revisi';

    const WORKFLOW_APPROVED = 'approved';

    const WORKFLOW_PUBLISHED = 'published';

    const WORKFLOW_OPTIONS = [
        self::WORKFLOW_DRAFT => 'Draft',
        self::WORKFLOW_REVIEW => 'Review',
        self::WORKFLOW_REVISI => 'Perlu Perbaikan',
        self::WORKFLOW_APPROVED => 'Approved',
        self::WORKFLOW_PUBLISHED => 'Didistribusikan',
    ];

    protected $appends = [];

    protected $casts = [
        'is_acak_urutan_soal' => 'boolean',
        'is_acak_opsi' => 'boolean',
        'is_published' => 'boolean',
        'shared_scope' => 'string',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'similarity_checked_at' => 'datetime',
        'similarity_summary' => 'array',
        'distributed_at' => 'datetime',
        'jumlah_soal_aktual' => 'integer',
        'total_bobot_aktual' => 'decimal:2',
        'waktu_pengerjaan_menit' => 'integer',
        'versi' => 'integer',
        'kkm' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            $m->id = $m->id ?: (string) Str::uuid();
            if (empty($m->kode_paket)) {
                $m->kode_paket = static::generateKodePaket();
            }
        });
    }

    protected static function generateKodePaket(): string
    {
        return 'PKT-'.strtoupper(Str::random(8)).'-'.now()->format('ymd');
    }

    public function kisiKisi(): BelongsTo
    {
        return $this->belongsTo(KisiKisiSoal::class, 'kisi_kisi_soal_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaketSoalItem::class, 'paket_soal_id')->orderBy('urutan');
    }

    /**
     * Direct many-to-many access to soal (used by ItemAnalysisEngine etc.)
     */
    public function soals(): BelongsToMany
    {
        return $this->belongsToMany(Soal::class, 'paket_soal_items', 'paket_soal_id', 'soal_id')
            ->withPivot(['urutan', 'bobot_override'])
            ->orderBy('paket_soal_items.urutan');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'paket_soal_id');
    }

    public function itemAnalyses(): HasMany
    {
        return $this->hasMany(ItemAnalysis::class, 'paket_soal_id');
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewable_id')
            ->where('reviewable_type', self::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(PaketSoalDistribution::class, 'paket_soal_id');
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PaketSoalPrintJob::class, 'paket_soal_id');
    }

    /**
     * Paket siap dipakai asesmen final:
     * sudah disetujui seluruh validator + dipublikasikan.
     */
    public function isFinal(): bool
    {
        return in_array($this->workflow_status, [self::WORKFLOW_APPROVED, self::WORKFLOW_PUBLISHED], true)
            && $this->is_published;
    }

    /**
     * Publish the paket soal.
     */
    public function publish(?string $userId = null): void
    {
        $this->update([
            'is_published' => true,
            'published_by' => $userId,
            'published_at' => now(),
            'workflow_status' => self::WORKFLOW_PUBLISHED,
        ]);
    }

    /**
     * Sync actual counts from items (used by PaketSoalService).
     * Alias for recomputeTotals().
     */
    public function syncActualCounts(): void
    {
        $this->recomputeTotals();
    }

    /**
     * Recompute jumlah_soal_aktual and total_bobot_aktual from items
     * Called on item attach/detach via observer
     */
    public function recomputeTotals(): void
    {
        $items = $this->items()->with('soal')->get();
        $this->jumlah_soal_aktual = $items->count();
        $this->total_bobot_aktual = $items->sum(function ($item) {
            return $item->bobot_override ?? $item->soal->bobot_default ?? 0;
        });
        $this->save();
    }
}
