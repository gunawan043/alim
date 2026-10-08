<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Item ATP — satu Tujuan Pembelajaran di dalam Alur Tujuan Pembelajaran
 * beserta urutan dan alokasi JP-nya.
 */
class AlurTujuanPembelajaranItem extends Model
{
    protected $table = 'alur_tujuan_pembelajaran_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'alur_tujuan_pembelajaran_id',
        'tujuan_pembelajaran_id',
        'urutan',
        'jp_alokasi',
        'catatan',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'jp_alokasi' => 'integer',
    ];

    public function alur(): BelongsTo
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'alur_tujuan_pembelajaran_id');
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}
