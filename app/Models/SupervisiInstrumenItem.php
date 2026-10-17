<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisiInstrumenItem extends Model
{
    use HasUuids;

    protected $table = 'supervisi_instrumen_item';

    protected $fillable = [
        'bagian_id', 'parent_item_id', 'teks', 'urutan', 'bobot', 'butuh_kondisi', 'is_active',
    ];

    protected $casts = [
        'bobot' => 'decimal:2',
        'butuh_kondisi' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function bagian(): BelongsTo
    {
        return $this->belongsTo(SupervisiInstrumenBagian::class, 'bagian_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_item_id');
    }

    public function subItems(): HasMany
    {
        return $this->hasMany(self::class, 'parent_item_id')->orderBy('urutan');
    }
}
