<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelatihanEvaluasi extends Model
{
    use HasUuids;

    protected $table = 'pelatihan_evaluasi';

    protected $fillable = ['pelatihan_id', 'user_id', 'skor_pelatihan', 'feedback', 'dokumentasi_uploaded', 'catatan'];

    protected $casts = ['skor_pelatihan' => 'integer', 'dokumentasi_uploaded' => 'boolean'];

    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
