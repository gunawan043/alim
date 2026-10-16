<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisiInstrumenBagian extends Model
{
    use HasUuids;

    protected $table = 'supervisi_instrumen_bagian';

    protected $fillable = [
        'instrumen_id', 'kode', 'nama', 'urutan', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function instrumen(): BelongsTo
    {
        return $this->belongsTo(SupervisiInstrumen::class, 'instrumen_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupervisiInstrumenItem::class, 'bagian_id')->whereNull('parent_item_id')->orderBy('urutan');
    }
}
