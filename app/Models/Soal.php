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
        'wacana_id',
        'materi',
        'derived_from_soal_id',
        'tipe_soal',
        'bahasa',
        'pertanyaan',
        'pembahasan',
        'gambar_path',
        'gambar_keterangan',
        'audio_path',
        'bobot_default',
        'tingkat_kesulitan_estimasi',
        'level_kognitif',
        'level_kognitif_rumpun',
        'bloom_confidence',
        'bloom_classified_at',
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
        'similarity_ack_note',
        'similarity_ack_by',
        'similarity_ack_at',
        'times_used',
    ];

    const WORKFLOW_DRAFT = 'draft';

    const WORKFLOW_REVIEW = 'review';

    const WORKFLOW_REVISI = 'revisi';

    const WORKFLOW_APPROVED = 'approved';

    /** Bahasa soal menentukan arah tata letak lembar soal. */
    public const BAHASA_LATIN = 'latin';

    public const BAHASA_ARAB = 'arab';

    public const BAHASA_GABUNGAN = 'gabungan';

    public const BAHASA_OPTIONS = [
        self::BAHASA_LATIN => 'Latin (LTR)',
        self::BAHASA_ARAB => 'Arab (RTL)',
        self::BAHASA_GABUNGAN => 'Gabungan (Latin + Arab, LTR)',
    ];

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
        'level_kognitif' => 'string',
        'level_kognitif_rumpun' => 'string',
        'bloom_confidence' => 'integer',
        'bloom_classified_at' => 'datetime',
        'times_used' => 'integer',
        'approved_at' => 'datetime',
        'similarity_checked_at' => 'datetime',
        'similarity_ack_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());

        // Level kognitif dihitung dari pertanyaan + indikator/TP, bukan dari
        // tingkat kesukaran. Kesulitan tetap menjadi pilihan guru.
        static::saving(function (Soal $soal) {
            // Guru boleh melakukan override manual. Pilihan "auto" atau kosong
            // berarti server menghitung ulang dari teks soal + indikator TP.
            $manualLevel = $soal->isDirty('level_kognitif')
                && filled($soal->level_kognitif)
                && $soal->level_kognitif !== 'auto'
                    ? $soal->level_kognitif
                    : null;

            if ($manualLevel) {
                $soal->level_kognitif = $manualLevel;
                $soal->level_kognitif_rumpun = \App\Services\Evaluasi\BloomTaxonomyService::rumpunForLevel($manualLevel);
                $soal->bloom_confidence = null;
                $soal->bloom_classified_at = now();

                return;
            }

            if (! $soal->isDirty(['pertanyaan', 'tp_id', 'materi']) && filled($soal->level_kognitif)) {
                return;
            }

            try {
                $indicator = null;

                if ($soal->tp_id) {
                    $indicator = \App\Models\TujuanPembelajaran::query()
                        ->whereKey($soal->tp_id)
                        ->value('deskripsi');
                }

                $result = app(\App\Services\Evaluasi\BloomTaxonomyService::class)
                    ->classify($soal->pertanyaan, $indicator, $soal->materi);

                $soal->level_kognitif = $result['level'];
                $soal->level_kognitif_rumpun = $result['rumpun'];
                $soal->bloom_confidence = $result['confidence'];
                $soal->bloom_classified_at = now();
            } catch (\Throwable $e) {
                // Klasifikasi tidak boleh menggagalkan penyimpanan soal.
                logger()->warning('Bloom taxonomy classification failed: '.$e->getMessage());
            }
        });
    }

    public function getLevelKognitifLabelAttribute(): string
    {
        return \App\Services\Evaluasi\BloomTaxonomyService::levelLabel($this->level_kognitif);
    }

    public function getLevelKognitifRumpunLabelAttribute(): string
    {
        return \App\Services\Evaluasi\BloomTaxonomyService::rumpunLabel($this->level_kognitif_rumpun);
    }

    public function getLevelKognitifCodeAttribute(): string
    {
        return (string) (data_get(\App\Services\Evaluasi\BloomTaxonomyService::LEVELS, $this->level_kognitif.'.code') ?? '—');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class);
    }

    public function wacana(): BelongsTo
    {
        return $this->belongsTo(WacanaSoal::class, 'wacana_id');
    }

    /**
     * Wacana untuk lembar soal: judul + teks (null bila tanpa wacana).
     *
     * @return array{id: string, judul: ?string, teks: string, sumber: ?string}|null
     */
    public function wacanaPayload(): ?array
    {
        $wacana = $this->relationLoaded('wacana') ? $this->wacana : $this->wacana()->first();

        if (! $wacana) {
            return null;
        }

        return [
            'id' => (string) $wacana->id,
            'judul' => $wacana->judul,
            'teks' => (string) $wacana->teks,
            'sumber' => $wacana->sumber,
            'rentang_nomor' => $wacana->rentang_nomor,
            'posisi_setelah_soal' => (int) $wacana->posisi_setelah_soal,
        ];
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

    /**
     * Apakah pemeriksaan kemiripan menemukan kandidat untuk ditinjau.
     */
    public function hasSimilarityWarnings(): bool
    {
        return (int) ($this->similarity_summary['total'] ?? 0) > 0;
    }

    /**
     * Mode bahasa efektif: nilai tersimpan, atau deteksi otomatis untuk soal lama.
     */
    public function bahasaEfektif(): string
    {
        $bahasa = (string) ($this->bahasa ?? '');

        if (in_array($bahasa, [self::BAHASA_LATIN, self::BAHASA_ARAB, self::BAHASA_GABUNGAN], true)) {
            return $bahasa;
        }

        return \App\Services\Ljk\LjkArabicText::hasArabic($this->pertanyaan)
            ? self::BAHASA_ARAB
            : self::BAHASA_LATIN;
    }

    public function isRtl(): bool
    {
        return $this->bahasaEfektif() === self::BAHASA_ARAB;
    }

    public function getIsObjectivelyGradableAttribute(): bool
    {
        return in_array($this->tipe_soal, ['pg', 'bs', 'jodoh']);
    }
}
