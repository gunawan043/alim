<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PelatihanPeserta extends Model
{
    use HasUuids;

    protected $table = 'pelatihan_peserta';

    protected $fillable = ['pelatihan_id', 'user_id', 'status_kehadiran', 'catatan'];

    /**
     * @return BelongsTo<Pelatihan, PelatihanPeserta>
     */
    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    /**
     * @return BelongsTo<User, PelatihanPeserta>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<PelatihanSertifikat>
     */
    public function sertifikat(): HasOne
    {
        return $this->hasOne(PelatihanSertifikat::class);
    }
}
