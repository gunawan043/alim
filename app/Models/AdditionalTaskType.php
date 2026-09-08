<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalTaskType extends Model
{
    use HasUuids;

    protected $table = 'additional_task_types';

    protected $fillable = [
        'jenis_gtk_id',
        'nama',
        'kode',
        'deskripsi',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function jenisGtk(): BelongsTo
    {
        return $this->belongsTo(JenisGtk::class, 'jenis_gtk_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
