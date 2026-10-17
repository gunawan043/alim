<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Alokasi JP per pekan untuk satu item PROSEM (hasil otomatis / penyesuaian guru).
 */
class ProsemItemWeek extends Model
{
    protected $table = 'prosem_item_weeks';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->id = $m->id ?: (string) Str::uuid());
    }

    protected $fillable = [
        'id',
        'prosem_item_id',
        'pekan_ke',
        'jp',
    ];

    protected $casts = [
        'pekan_ke' => 'integer',
        'jp' => 'integer',
    ];

    public function prosemItem(): BelongsTo
    {
        return $this->belongsTo(ProsemItem::class, 'prosem_item_id');
    }
}
