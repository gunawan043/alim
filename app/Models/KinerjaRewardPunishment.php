<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KinerjaRewardPunishment extends Model
{
    use HasUuids;

    protected $table = 'kinerja_reward_punishment';

    protected $fillable = [
        'user_id', 'kinerja_periode_id', 'jenis', 'kategori',
        'nama', 'deskripsi', 'tanggal', 'diberikan_oleh', 'dokumen_path',
    ];

    protected $casts = ['tanggal' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(KinerjaPeriode::class, 'kinerja_periode_id');
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diberikan_oleh');
    }
}
