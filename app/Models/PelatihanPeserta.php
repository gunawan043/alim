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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Pelatihan, PelatihanPeserta>
     */
    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, PelatihanPeserta>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<PelatihanSertifikat>
     */
    public function sertifikat(): HasOne
    {
        return $this->hasOne(PelatihanSertifikat::class);
    }
}
