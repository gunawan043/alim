<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KinerjaIndikator extends Model
{
    use HasUuids;
    protected $table = 'kinerja_indikator';

    protected $fillable = ['kinerja_komponen_id', 'nama', 'deskripsi', 'bobot_persen', 'urutan', 'is_active'];

    protected $casts = ['bobot_persen' => 'decimal:2', 'is_active' => 'boolean'];

    public function komponen(): BelongsTo
    {
        return $this->belongsTo(KinerjaKomponen::class, 'kinerja_komponen_id');
    }
}