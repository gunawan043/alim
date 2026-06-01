<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPelatihan extends Model
{
    use HasUuids;
    protected $table = 'jenis_pelatihan';

    protected $fillable = ['nama', 'deskripsi', 'is_active', 'urutan'];
    protected $casts = ['is_active' => 'boolean'];

    public function pelathan(): HasMany
    {
        return $this->hasMany(Pelatihan::class, 'jenis_pelatihan_id');
    }
}