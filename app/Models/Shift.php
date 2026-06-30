<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasUuids;
    protected $table = 'shifts';

    protected $fillable = [
        'nama', 'jam_kerja_id', 'tanggal_mulai', 'tanggal_selesai', 'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'jam_kerja_id');
    }
}