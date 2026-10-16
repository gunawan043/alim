<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisiPenilaian extends Model
{
    use HasUuids;

    protected $table = 'supervisi_penilaian';

    protected $fillable = [
        'supervisi_id', 'item_id', 'skor', 'kondisi', 'catatan',
    ];

    protected $casts = [
        'skor' => 'decimal:2',
        'kondisi' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SupervisiInstrumenItem::class, 'item_id');
    }

    public function supervisi(): BelongsTo
    {
        return $this->belongsTo(Supervisi::class, 'supervisi_id');
    }
}
