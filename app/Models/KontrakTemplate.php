<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KontrakTemplate extends Model
{
    use HasUuids;

    protected $table = 'kontrak_templates';

    protected $fillable = ['nama', 'jenis', 'isi_template', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
