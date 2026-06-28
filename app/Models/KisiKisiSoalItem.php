<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KisiKisiSoalItem extends Model
{
    use HasFactory;

    protected $table = 'kisi_kisi_soal_items';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'kisi_kisi_soal_id',
        'tp_id',
        'level_kognitif',
        'jumlah_soal',
        'bobot_per_soal',
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
