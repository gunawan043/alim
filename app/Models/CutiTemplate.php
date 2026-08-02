<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CutiTemplate extends Model
{
    use HasUuids;

    protected $table = 'cuti_templates';

    protected $fillable = [
        'nama',
        'jenis',
        'jumlah_hari',
        'paid',
        'deskripsi',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'jumlah_hari' => 'integer',
        'paid' => 'boolean',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public const JENIS_TAHUNAN = 'TAHUNAN';

    public const JENIS_SAKIT = 'SAKIT';

    public const JENIS_BESAR = 'BESAR';

    public const JENIS_LAINNYA = 'LAINNYA';

    public function balances(): HasMany
    {
        return $this->hasMany(CutiBalance::class, 'cuti_template_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CutiRequest::class, 'cuti_template_id');
    }
}
