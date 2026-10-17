<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Item PROSEM — TP/materi dengan rentang pekan efektif dan JP.
 */
class ProsemItem extends Model
{
    protected $table = 'prosem_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'prosem_id',
        'prota_item_id',
        'tujuan_pembelajaran_id',
        'urutan',
        'mulai_minggu_ke',
        'selesai_minggu_ke',
        'jp',
        'sumber',
        'keterangan',
    ];

    const SUMBER_OTOMATIS = 'otomatis';

    const SUMBER_MANUAL = 'manual';

    const SUMBER_OPTIONS = [
        self::SUMBER_OTOMATIS => 'Otomatis',
        self::SUMBER_MANUAL => 'Disesuaikan',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'mulai_minggu_ke' => 'integer',
        'selesai_minggu_ke' => 'integer',
        'jp' => 'integer',
    ];

    public function prosem(): BelongsTo
    {
        return $this->belongsTo(Prosem::class, 'prosem_id');
    }

    public function protaItem(): BelongsTo
    {
        return $this->belongsTo(ProtaItem::class, 'prota_item_id');
    }

    public function tujuanPembelajaran(): BelongsTo
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tujuan_pembelajaran_id');
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(ProsemItemWeek::class, 'prosem_item_id')->orderBy('pekan_ke');
    }

    public function isManual(): bool
    {
        return $this->sumber === self::SUMBER_MANUAL;
    }
}
