<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KisiKisiItem extends Model
{
    protected $table = 'kisi_kisi_soal_items';

    public $timestamps = true;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'kisi_kisi_soal_id', 'tp_id', 'level_kognitif',
        'jumlah_soal', 'bobot_per_soal',
    ];

    protected $casts = [
        'jumlah_soal' => 'integer',
        'bobot_per_soal' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    public function kisiKisi(): BelongsTo
    {
        return $this->belongsTo(KisiKisiSoal::class, 'kisi_kisi_soal_id');
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_id');
    }
}
