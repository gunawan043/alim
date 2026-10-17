<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Hasil pemeriksaan kemiripan soal — warning/quality control,
 * bukan keputusan otomatis untuk menolak soal.
 */
class SoalSimilarity extends Model
{
    protected $table = 'soal_similarities';

    protected $keyType = 'string';

    public $incrementing = false;

    const LEVEL_EXACT = 'exact';

    const LEVEL_TEXT = 'text';

    const LEVEL_TOKEN = 'token';

    /** Level tampilan untuk perbandingan yang di bawah semua ambang. */
    const LEVEL_DIFFERENT = 'different';

    const LEVEL_OPTIONS = [
        self::LEVEL_EXACT => 'Duplikat Persis',
        self::LEVEL_TEXT => 'Teks Sangat Mirip (fuzzy)',
        self::LEVEL_TOKEN => 'Kata Kunci Mirip (token overlap)',
        self::LEVEL_DIFFERENT => 'Tidak Mirip (di bawah ambang)',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'soal_id',
        'compared_soal_id',
        'score',
        'level',
        'context',
        'checked_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }

    public function comparedSoal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'compared_soal_id');
    }

    public function levelLabel(): string
    {
        return self::LEVEL_OPTIONS[$this->level]
            ?? (($this->level === 'semantic') ? 'Kata Kunci Mirip (token overlap)' : ucfirst((string) $this->level));
    }
}
