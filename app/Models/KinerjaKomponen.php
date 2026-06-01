<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KinerjaKomponen extends Model
{
    use HasUuids;
    protected $table = 'kinerja_komponen';

    protected $fillable = ['nama', 'deskripsi', 'bobot_persen', 'urutan', 'is_active'];

    protected $casts = ['bobot_persen' => 'decimal:2', 'is_active' => 'boolean'];

    public function indikator(): HasMany
    {
        return $this->hasMany(KinerjaIndikator::class, 'kinerja_komponen_id')->where('is_active', true)->orderBy('urutan');
    }

    public function semuaIndikator(): HasMany
    {
        return $this->hasMany(KinerjaIndikator::class, 'kinerja_komponen_id')->orderBy('urutan');
    }
}