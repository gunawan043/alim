<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JamKerja extends Model
{
    use HasUuids;
    protected $table = 'jam_kerja';

    protected $fillable = [
        'nama', 'is_active', 'jam_masuk', 'jam_pulang',
        'istirahat_menit', 'istirahat_mulai', 'keterangan', 'dibuat_oleh',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'istirahat_menit' => 'integer',
        'jam_masuk' => 'string',
        'jam_pulang' => 'string',
        'istirahat_mulai' => 'string',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}