<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Item PROTA — baris BAB/Materi dengan alokasi JP (dari TP/ATP).
 */
class ProtaItem extends Model
{
    protected $table = 'prota_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'prota_id',
        'tujuan_pembelajaran_id',
        'bab',
        'materi',
        'alokasi_jp',
        'keterangan',
        'urutan',
    ];

    protected $casts = [
        'alokasi_jp' => 'integer',
        'urutan' => 'integer',
    ];

    public function prota(): BelongsTo
    {
        return $this->belongsTo(Prota::class, 'prota_id');
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }
}
