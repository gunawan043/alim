<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemAnalysis extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'item_analysis';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'paket_soal_id',
        'soal_id',
        'total_peserta',
        'total_benar',
        'total_salah',
        'difficulty_index',
        'difficulty_category',
        'discrimination_index',
        'discrimination_category',
        'point_biserial',
        'distractor_analysis',
        'mean_score',
        'sd_score',
        'alpha_if_deleted',
        'rekomendasi',
        'generated_at',
        'regenerated_count',
    ];

    protected $casts = [
        'total_peserta' => 'integer',
        'total_benar' => 'integer',
        'total_salah' => 'integer',
        'difficulty_index' => 'decimal:4',
        'discrimination_index' => 'decimal:4',
        'point_biserial' => 'decimal:4',
        'distractor_analysis' => 'array',
        'mean_score' => 'decimal:2',
        'sd_score' => 'decimal:2',
        'alpha_if_deleted' => 'decimal:4',
        'generated_at' => 'datetime',
        'regenerated_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
