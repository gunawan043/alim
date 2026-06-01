<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KinerjaSkor extends Model
{
    use HasUuids;
    protected $table = 'kinerja_skor';

    protected $fillable = ['kinerja_penilaian_id', 'kinerja_indikator_id', 'skor', 'catatan'];

    protected $casts = ['skor' => 'decimal:2'];

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(KinerjaPenilaian::class, 'kinerja_penilaian_id');
    }

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(KinerjaIndikator::class, 'kinerja_indikator_id');
    }
}
