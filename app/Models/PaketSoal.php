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
        'komposisi', 'snapshot_at',
        'is_published', 'published_by', 'published_at',
        'workflow_status', 'approved_at', 'approved_by',
        'similarity_checked_at', 'similarity_summary', 'distributed_at',
        'shared_scope', 'kkm', 'layout_settings',
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
        'komposisi' => 'array',
        'snapshot_at' => 'datetime',
        'distributed_at' => 'datetime',
        'jumlah_soal_aktual' => 'integer',
        'total_bobot_aktual' => 'decimal:2',
        'waktu_pengerjaan_menit' => 'integer',
        'versi' => 'integer',
        'kkm' => 'decimal:2',
        'layout_settings' => 'array',
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

    public static function defaultLayout(): array
    {
        return [
            'margin_top' => 15.0,
            'margin_right' => 15.0,
            'margin_bottom' => 15.0,
            'margin_left' => 20.0,
            'font_size' => 11.0,
            'title_font_size' => 14.0,
            'info_font_size' => 10.0,
            'line_height' => 1.4,
            'paper_size' => 'a4',
        ];
    }

    public function layoutSettings(): array
    {
        $defaults = static::defaultLayout();
        $saved = is_array($this->layout_settings) ? $this->layout_settings : [];

        return [
            'margin_top' => (float) ($saved['margin_top'] ?? $defaults['margin_top']),
            'margin_right' => (float) ($saved['margin_right'] ?? $defaults['margin_right']),
            'margin_bottom' => (float) ($saved['margin_bottom'] ?? $defaults['margin_bottom']),
            'margin_left' => (float) ($saved['margin_left'] ?? $defaults['margin_left']),
            'font_size' => (float) ($saved['font_size'] ?? $defaults['font_size']),
            'title_font_size' => (float) ($saved['title_font_size'] ?? $defaults['title_font_size']),
            'info_font_size' => (float) ($saved['info_font_size'] ?? $defaults['info_font_size']),
            'line_height' => (float) ($saved['line_height'] ?? $defaults['line_height']),
            'paper_size' => in_array(($saved['paper_size'] ?? $defaults['paper_size']), ['a4', 'folio', 'letter'], true)
                ? ($saved['paper_size'] ?? $defaults['paper_size'])
                : 'a4',
        ];
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
     * Butir paket sudah dibekukan menjadi naskah.
     */
    public function hasSnapshot(): bool
    {
        return $this->snapshot_at !== null;
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
